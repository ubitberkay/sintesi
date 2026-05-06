<?php
/**
 * Sintesi - Rezervasyon Hatırlatma Cron Scripti
 * Bu dosya her 15 dakikada bir çalıştırılmalıdır.
 * 2 saat kalmış rezervasyonlara hatırlatma e-postası gönderir.
 */

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer dosyalarını dahil et
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

try {
    $pdo = veritabani_baglantisi();
    
    // Hatırlatma gönderilecek rezervasyonları bul:
    // - Onaylanmış
    // - Hatırlatma gönderilmemiş
    // - E-posta adresi olan
    // - Rezervasyon zamanına 2 saatten az kalmış (ve geçmiş değil)
    
    $bugun = date('Y-m-d');
    $suan = time();
    $iki_saat_sonra = $suan + (2 * 3600);
    
    // Basitlik ve hem SQLite hem MySQL uyumu için PHP tarafında filtreleme yapıyoruz
    $stmt = $pdo->prepare("
        SELECT * FROM rezervasyonlar 
        WHERE durum = 'onaylandi' 
        AND hatirlatma_gonderildi = 0 
        AND email IS NOT NULL 
        AND email != ''
        AND tarih >= ?
    ");
    $stmt->execute([$bugun]);
    $rezervasyonlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $gonderilen_sayisi = 0;
    
    foreach ($rezervasyonlar as $rez) {
        $rez_zamani = strtotime($rez['tarih'] . ' ' . $rez['saat']);
        
        // Eğer rezervasyona 2 saatten az kalmışsa ve henüz geçmemişse
        if ($rez_zamani <= $iki_saat_sonra && $rez_zamani > $suan) {
            if (gonderHatirlatmaMaili($rez)) {
                // Gönderildi olarak işaretle
                $update = $pdo->prepare("UPDATE rezervasyonlar SET hatirlatma_gonderildi = 1 WHERE id = ?");
                $update->execute([$rez['id']]);
                $gonderilen_sayisi++;
            }
        }
    }
    
    echo "İşlem tamamlandı. Toplam " . $gonderilen_sayisi . " hatırlatma gönderildi.\n";

} catch (Exception $e) {
    die("Hata: " . $e->getMessage());
}

/**
 * Müşteriye hatırlatma maili gönderir
 */
function gonderHatirlatmaMaili($rez) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'mail.sintesi.com.tr';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@sintesi.com.tr';
        $mail->Password   = 'qwe12ASD?';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        $mail->setFrom('info@sintesi.com.tr', 'Sintesi');
        $mail->addAddress($rez['email'], $rez['ad_soyad']);
        
        $tarih_format = date('d.m.Y', strtotime($rez['tarih']));
        
        $mail->isHTML(true);
        $mail->Subject = "🔔 Hatırlatma: Rezervasyonunuz Yaklaşıyor - Sintesi";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
                <div style='background: #9D432C; color: white; padding: 30px; text-align: center;'>
                    <h1 style='margin: 0; font-size: 24px;'>Rezervasyonunuz Yaklaşıyor!</h1>
                </div>
                <div style='padding: 30px; background: #fff; color: #333;'>
                    <p>Sayın <strong>{$rez['ad_soyad']}</strong>,</p>
                    <p>Bugün için olan rezervasyonunuzu hatırlatmak istedik. Sizi ağırlamak için hazırlıklarımızı tamamladık.</p>
                    
                    <div style='background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Tarih:</strong> {$tarih_format}</p>
                        <p style='margin: 5px 0;'><strong>Saat:</strong> {$rez['saat']}</p>
                        <p style='margin: 5px 0;'><strong>Kişi Sayısı:</strong> {$rez['kisi_sayisi']} Kişi</p>
                    </div>
                    
                    <p>Geç kalma durumunuzda lütfen bize <strong>+90 (216) XXX XX XX</strong> numaralı telefondan bilgi veriniz. Rezervasyonlar beklenen saatten 15 dakika sonra otomatik olarak iptal edilebilmektedir.</p>
                    <p style='margin-top: 30px;'>Görüşmek üzere,<br><strong>Sintesi Ekibi</strong></p>
                </div>
                <div style='background: #f4f4f4; padding: 20px; text-align: center; color: #888; font-size: 12px;'>
                    Metropol İstanbul AVM, B2 Katı, Ataşehir/İstanbul
                </div>
            </div>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Hatırlatma maili gönderilemedi: ' . $e->getMessage());
        return false;
    }
}
