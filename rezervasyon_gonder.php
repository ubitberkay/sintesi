<?php
/**
 * Sintesi - Rezervasyon Form İşleyici
 * 
 * Müşteriden gelen rezervasyon formunu işler,
 * veritabanına kaydeder ve e-posta bildirimi gönderir.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

// Form verilerini al ve temizle
$ad_soyad   = isset($_POST['ad_soyad']) ? htmlspecialchars(strip_tags(trim($_POST['ad_soyad']))) : '';
$email      = isset($_POST['email']) ? htmlspecialchars(strip_tags(trim($_POST['email']))) : '';
$telefon    = isset($_POST['telefon']) ? htmlspecialchars(strip_tags(trim($_POST['telefon']))) : '';
$tarih      = isset($_POST['tarih']) ? htmlspecialchars(strip_tags(trim($_POST['tarih']))) : '';
$saat       = isset($_POST['saat']) ? htmlspecialchars(strip_tags(trim($_POST['saat']))) : '';
$kisi       = isset($_POST['kisi_sayisi']) ? intval($_POST['kisi_sayisi']) : 2;
$ozel       = isset($_POST['ozel_istekler']) ? htmlspecialchars(strip_tags(trim($_POST['ozel_istekler']))) : '';
$dil        = isset($_POST['dil']) ? htmlspecialchars(strip_tags(trim($_POST['dil']))) : 'tr';

// Doğrulama
if (empty($ad_soyad) || empty($telefon) || empty($tarih) || empty($saat)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen zorunlu alanları doldurun.']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta adresi giriniz.']);
    exit;
}

// Tarih kontrolü (geçmiş tarih seçilemez)
$bugun = date('Y-m-d');
if ($tarih < $bugun) {
    echo json_encode(['success' => false, 'message' => 'Geçmiş bir tarih seçemezsiniz.']);
    exit;
}

// Bugün için geçmiş saat kontrolü
if ($tarih === $bugun) {
    $suan_saat = date('H:i');
    if ($saat < $suan_saat) {
        echo json_encode(['success' => false, 'message' => 'Geçmiş bir saat dilimi seçemezsiniz.']);
        exit;
    }
}

// Bugün için geçmiş saat kontrolü
if ($tarih === $bugun) {
    $suan_saat = date('H:i');
    if ($saat < $suan_saat) {
        echo json_encode(['success' => false, 'message' => 'Geçmiş bir saat dilimi seçemezsiniz.']);
        exit;
    }
}


// Kişi sayısı kontrolü
if ($kisi < 1 || $kisi > 20) {
    echo json_encode(['success' => false, 'message' => 'Kişi sayısı 1 ile 20 arasında olmalıdır.']);
    exit;
}

try {
    $pdo = veritabani_baglantisi();
    
    // Eksik sütun kontrolü (Canlıya geçişte dil desteği için)
    try {
        $pdo->query("SELECT dil FROM rezervasyonlar LIMIT 1");
    } catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE rezervasyonlar ADD COLUMN dil VARCHAR(10) DEFAULT 'tr'");
        } catch (Exception $e2) {
            // Sütun zaten eklenmiş olabilir veya başka bir hata, sessizce devam et
        }
    }
    
    // Ayarları çek
    $stmt_ayarlar = $pdo->prepare("SELECT ayar_anahtari, ayar_degeri FROM ayarlar");
    $stmt_ayarlar->execute();
    $ayarlar = $stmt_ayarlar->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $maksimum_kapasite = isset($ayarlar['kapasite']) ? (int)$ayarlar['kapasite'] : 16;
    $kapali_gunler = isset($ayarlar['kapali_gunler']) ? json_decode($ayarlar['kapali_gunler'], true) : [];
    
    // Kapalı gün kontrolü
    if (is_array($kapali_gunler) && in_array($tarih, $kapali_gunler)) {
        echo json_encode(['success' => false, 'message' => 'Seçtiğiniz tarih restoranımız kapalıdır. Lütfen başka bir tarih seçin.']);
        exit;
    }

    // Saatlik kapasite kontrolü
    $stmt = $pdo->prepare("SELECT SUM(kisi_sayisi) FROM rezervasyonlar WHERE tarih = ? AND saat = ? AND durum != 'iptal'");
    $stmt->execute([$tarih, $saat]);
    $mevcut_kisi = (int)$stmt->fetchColumn();
    
    if (($mevcut_kisi + $kisi) > $maksimum_kapasite) {
        $kalan_yer = $maksimum_kapasite - $mevcut_kisi;
        $mesaj = $kalan_yer > 0 
            ? "Üzgünüz, bu saat dilimi için sadece {$kalan_yer} kişilik yerimiz kalmıştır. Lütfen kişi sayısını azaltın veya başka bir saat/gün seçin."
            : "Üzgünüz, bu saat dilimi tamamen dolmuştur. Lütfen başka bir saat veya gün seçiniz.";
        
        echo json_encode(['success' => false, 'message' => $mesaj]);
        exit;
    }
    
    $iptal_kodu = bin2hex(random_bytes(16));

    // Veritabanına kaydet
    $stmt = $pdo->prepare("
        INSERT INTO rezervasyonlar (ad_soyad, email, telefon, tarih, saat, kisi_sayisi, ozel_istekler, durum, iptal_kodu, dil)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'beklemede', ?, ?)
    ");
    $stmt->execute([$ad_soyad, $email, $telefon, $tarih, $saat, $kisi, $ozel, $iptal_kodu, $dil]);
    
    // E-posta bildirimi gönder (sadece sunucuda, local'de atla)
    if (!local_mi()) {
        gonderBildirimMaili($ad_soyad, $email, $telefon, $tarih, $saat, $kisi, $ozel);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Rezervasyon talebiniz başarıyla alındı! En kısa sürede sizinle iletişime geçeceğiz.'
    ]);
    
} catch (Exception $e) {
    // Hatanın detayını loglara kaydet
    error_log("Rezervasyon Hatası: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.'
    ]);
}

/**
 * Rezervasyon bildirim e-postası gönderir
 */
function gonderBildirimMaili($ad, $email, $telefon, $tarih, $saat, $kisi, $ozel) {
    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
    
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
        
        $mail->setFrom('info@sintesi.com.tr', 'Sintesi Rezervasyon');
        $mail->addAddress('bugra@sintesi.com.tr');
        $mail->addAddress('cagla@sintesi.com.tr');
        $mail->addAddress('ersinavsar@sintesi.com.tr');
        $mail->addAddress('iletisim@sintesi.com.tr');
        $mail->addAddress('info@sintesi.com.tr');
        
        if (!empty($email)) {
            $mail->addReplyTo($email, $ad);
        }
        
        // Tarih formatla
        $tarih_format = date('d.m.Y', strtotime($tarih));
        
        $mail->isHTML(true);
        $mail->Subject = "🍽️ Yeni Rezervasyon Talebi - {$ad} ({$tarih_format})";
        $mail->Body = "
            <div style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 15px; overflow: hidden; background: #000 url('https://sintesi.com.tr/background.png') no-repeat center center; background-size: cover; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);\">
                <div style='padding: 40px 20px; text-align: center;'>
                    <img src='https://sintesi.com.tr/sintesi.webp' alt='Sintesi' style='max-width: 180px; margin-bottom: 20px;'>
                    <h1 style='margin: 0; font-size: 26px; font-family: Georgia, serif; color: #fff;'>Yeni Rezervasyon Talebi</h1>
                    <div style='width: 50px; height: 2px; background: #9D432C; margin: 20px auto;'></div>
                </div>
                <div style='padding: 0 40px 40px 40px;'>
                    <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Web sitesi üzerinden yeni bir rezervasyon talebi alındı:</p>
                    
                    <div style='background: rgba(255,255,255,0.05); padding: 25px; border-radius: 12px; margin: 30px 0; border: 1px solid rgba(255,255,255,0.1);'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Müşteri</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$ad}</strong></td></tr>
                            <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Telefon</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$telefon}</strong></td></tr>
                            <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>E-posta</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>" . ($email ?: 'Belirtilmedi') . "</strong></td></tr>
                            <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Tarih</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$tarih_format}</strong></td></tr>
                            <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Saat</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$saat}</strong></td></tr>
                            <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Kişi</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$kisi} Kişi</strong></td></tr>
                        </table>
                        " . ($ozel ? "
                        <div style='margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05);'>
                            <p style='margin: 0; color: #888; font-size: 14px;'>Özel İstek:</p>
                            <p style='margin: 5px 0 0 0; color: #fff; font-size: 14px; line-height: 1.5;'>{$ozel}</p>
                        </div>" : "") . "
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='https://www.sintesi.com.tr/admin' style='background: #333; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; display: inline-block; border: 1px solid rgba(255,255,255,0.1);'>Admin Paneline Git</a>
                    </div>
                </div>
            </div>
        ";
        
        $mail->AltBody = "Yeni Rezervasyon: {$ad}, {$tarih_format} {$saat}, {$kisi} kişi, Tel: {$telefon}";
        $mail->send();
    } catch (Exception $e) {
        // E-posta hatası rezervasyonu engellemez, sessizce devam et
        error_log('Rezervasyon e-posta hatası: ' . $e->getMessage());
    }
}
?>
