<?php
/**
 * Sintesi - Rezervasyon Hatırlatma Cron Scripti
 * Bu dosya her 15 dakikada bir çalıştırılmalıdır.
 * 2 saat kalmış rezervasyonlara hatırlatma e-postası gönderir.
 */

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Log yazar
 */
function cron_log($mesaj) {
    $log_dosyasi = __DIR__ . '/data/cron_log.txt';
    $tarih = date('Y-m-d H:i:s');
    $log_mesaji = "[$tarih] $mesaj" . PHP_EOL;
    file_put_contents($log_dosyasi, $log_mesaji, FILE_APPEND);
}

// PHPMailer dosyalarını dahil et
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

try {
    cron_log("--- Cron Başladı ---");
    $pdo = veritabani_baglantisi();
    
    $bugun = date('Y-m-d');
    $suan = time();
    $iki_saat_sonra = $suan + (2 * 3600);
    
    cron_log("Zaman: " . date('H:i:s', $suan) . " | Limit: " . date('H:i:s', $iki_saat_sonra));

    // Basitlik ve hem SQLite hem MySQL uyumu için PHP tarafında filtreleme yapıyoruz
    $stmt = $pdo->prepare("
        SELECT * FROM rezervasyonlar 
        WHERE durum = 'onaylandi' 
        AND (hatirlatma_gonderildi = 0 OR hatirlatma_gonderildi IS NULL)
        AND email IS NOT NULL 
        AND email != ''
        AND tarih >= ?
    ");
    $stmt->execute([$bugun]);
    $rezervasyonlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $gonderilen_sayisi = 0;
    
    foreach ($rezervasyonlar as $rez) {
        $rez_zamani = strtotime($rez['tarih'] . ' ' . $rez['saat']);
        
        cron_log("Kontrol: Rez#{$rez['id']} ({$rez['ad_soyad']}) - Saat: {$rez['saat']} - TS: " . date('H:i:s', $rez_zamani));

        // Eğer rezervasyona 2 saatten az kalmışsa ve henüz geçmemişse
        if ($rez_zamani <= $iki_saat_sonra && $rez_zamani > $suan) {
            cron_log("Mail gönderiliyor: Rez#{$rez['id']}");
            if (gonderHatirlatmaMaili($rez)) {
                // Gönderildi olarak işaretle
                $update = $pdo->prepare("UPDATE rezervasyonlar SET hatirlatma_gonderildi = 1 WHERE id = ?");
                $update->execute([$rez['id']]);
                $gonderilen_sayisi++;
                cron_log("Başarılı: Rez#{$rez['id']}");
            } else {
                cron_log("HATA: Rez#{$rez['id']} mail gönderilemedi.");
            }
        }
    }
    
    echo "İşlem tamamlandı. Toplam " . $gonderilen_sayisi . " hatırlatma gönderildi.\n";
    cron_log("Bitti: $gonderilen_sayisi hatırlatma gönderildi.");

    // --- OTOMATİK GELMEDİ İŞARETLEME ---
    // Rezervasyon saati üzerinden 1 saat geçmiş ve hala 'onaylandi' olanları 'gelmedi' yap
    $bir_saat_once = $suan - 3600;
    
    // Sadece bugün veya daha eski onaylıları kontrol et
    $stmt = $pdo->prepare("
        SELECT id, tarih, saat FROM rezervasyonlar 
        WHERE durum = 'onaylandi' 
        AND tarih <= ?
    ");
    $stmt->execute([$bugun]);
    $onaylilar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $gelmedi_sayisi = 0;
    foreach ($onaylilar as $rez) {
        $rez_zamani = strtotime($rez['tarih'] . ' ' . $rez['saat']);
        if ($rez_zamani < $bir_saat_once) {
            $update = $pdo->prepare("UPDATE rezervasyonlar SET durum = 'gelmedi' WHERE id = ?");
            $update->execute([$rez['id']]);
            $gelmedi_sayisi++;
        }
    }
    
    if ($gelmedi_sayisi > 0) {
        echo $gelmedi_sayisi . " adet geçmiş rezervasyon 'gelmedi' olarak işaretlendi.\n";
    }

} catch (\Throwable $e) {
    $hata_mesaji = "KRİTİK HATA: " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")";
    cron_log($hata_mesaji);
    die($hata_mesaji);
}

/**
 * Müşteriye hatırlatma maili gönderir
 */
function gonderHatirlatmaMaili($rez) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        $mail->setFrom('info@sintesi.com.tr', 'Sintesi');
        $mail->addAddress($rez['email'], $rez['ad_soyad']);
        
        $tarih_format = date('d.m.Y', strtotime($rez['tarih']));
        
        $mail->isHTML(true);
        $mail->Subject = "🔔 Hatırlatma: Rezervasyonunuz Yaklaşıyor - Sintesi";
        $mail->Body = "
            <div style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 15px; overflow: hidden; background: #000 url('https://sintesi.com.tr/background.png') no-repeat center center; background-size: cover; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);\">
                <div style='padding: 40px 20px; text-align: center;'>
                    <img src='https://sintesi.com.tr/sintesi.webp' alt='Sintesi' style='max-width: 180px; margin-bottom: 20px;'>
                    <h1 style='margin: 0; font-size: 26px; font-family: Georgia, serif; color: #fff;'>Rezervasyon Hatırlatması</h1>
                    <div style='width: 50px; height: 2px; background: #9D432C; margin: 20px auto;'></div>
                </div>
                <div style='padding: 0 40px 40px 40px;'>
                    <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Sayın <strong>{$rez['ad_soyad']}</strong>,</p>
                    <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Bugün için olan rezervasyonunuzu hatırlatmak istedik. Sizi ağırlamak için hazırlıklarımızı tamamladık.</p>
                    
                    <div style='background: rgba(255,255,255,0.05); padding: 25px; border-radius: 12px; margin: 30px 0; border: 1px solid rgba(255,255,255,0.1);'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; color: #888; font-size: 14px;'>Tarih</td>
                                <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$tarih_format}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #888; font-size: 14px;'>Saat</td>
                                <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$rez['saat']}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #888; font-size: 14px;'>Kişi Sayısı</td>
                                <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$rez['kisi_sayisi']} Kişi</strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <p style='font-size: 14px; color: #aaa; line-height: 1.6;'>Geç kalma durumunuzda lütfen bize <strong>+90 (216) XXX XX XX</strong> numaralı telefondan bilgi veriniz. Rezervasyonlar beklenen saatten 15 dakika sonra otomatik olarak iptal edilebilmektedir.</p>
                    <p style='margin-top: 40px; font-size: 14px; color: #aaa; text-align: center;'>Görüşmek üzere,<br><strong>Sintesi Ekibi</strong></p>
                </div>
                <div style='background: rgba(0,0,0,0.4); padding: 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.05);'>
                    <p style='margin: 0; color: #888; font-size: 13px;'>Metropol İstanbul AVM, B2 Katı, Ataşehir/İstanbul</p>
                </div>
            </div>
        ";
        
        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('Hatırlatma maili PHPMailer hatası: ' . $e->getMessage());
        cron_log("SMTP Hatası: " . $e->getMessage());
        return false;
    } catch (\Exception $e) {
        error_log('Hatırlatma maili genel hata: ' . $e->getMessage());
        cron_log("Mail Genel Hatası: " . $e->getMessage());
        return false;
    }
}
