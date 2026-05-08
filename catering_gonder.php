<?php
/**
 * Sintesi - Catering Form İşleyici
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Sadece POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$ad_soyad     = isset($_POST['ad_soyad']) ? htmlspecialchars(strip_tags(trim($_POST['ad_soyad']))) : '';
$telefon      = isset($_POST['telefon']) ? htmlspecialchars(strip_tags(trim($_POST['telefon']))) : '';
$email        = isset($_POST['email']) ? htmlspecialchars(strip_tags(trim($_POST['email']))) : '';
$etkinlik_tip = isset($_POST['etkinlik_tipi']) ? htmlspecialchars(strip_tags(trim($_POST['etkinlik_tipi']))) : '';
$kisi_sayisi  = isset($_POST['kisi_sayisi']) ? htmlspecialchars(strip_tags(trim($_POST['kisi_sayisi']))) : '';
$tarih        = isset($_POST['tarih']) ? htmlspecialchars(strip_tags(trim($_POST['tarih']))) : '';
$lokasyon     = isset($_POST['lokasyon']) ? htmlspecialchars(strip_tags(trim($_POST['lokasyon']))) : '';
$not          = isset($_POST['notlar']) ? htmlspecialchars(strip_tags(trim($_POST['notlar']))) : '';

if (empty($ad_soyad) || empty($telefon) || empty($etkinlik_tip) || empty($tarih)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen zorunlu alanları doldurun.']);
    exit;
}

// E-posta Bildirimi Gönder
try {
    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
    
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('info@sintesi.com.tr', 'Sintesi Catering');
    $mail->addAddress('bugra@sintesi.com.tr');
    $mail->addAddress('cagla@sintesi.com.tr');
    $mail->addAddress('ersinavsar@sintesi.com.tr');
    $mail->addAddress('iletisim@sintesi.com.tr');
    $mail->addAddress('info@sintesi.com.tr');

    if (!empty($email)) {
        $mail->addReplyTo($email, $ad_soyad);
    }

    $mail->isHTML(true);
    $mail->Subject = "🥂 Yeni Catering Teklif Talebi - {$ad_soyad}";
    
    $mail->Body = "
        <div style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 15px; overflow: hidden; background: #000 url('https://sintesi.com.tr/background.png') no-repeat center center; background-size: cover; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);\">
            <div style='padding: 40px 20px; text-align: center;'>
                <img src='https://sintesi.com.tr/sintesi.webp' alt='Sintesi' style='max-width: 180px; margin-bottom: 20px;'>
                <h1 style='margin: 0; font-size: 26px; font-family: Georgia, serif; color: #fff;'>Catering Teklif Talebi</h1>
                <div style='width: 50px; height: 2px; background: #9D432C; margin: 20px auto;'></div>
            </div>
            <div style='padding: 0 40px 40px 40px;'>
                <div style='background: rgba(255,255,255,0.05); padding: 25px; border-radius: 12px; margin: 20px 0; border: 1px solid rgba(255,255,255,0.1);'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Müşteri</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$ad_soyad}</strong></td></tr>
                        <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Telefon</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$telefon}</strong></td></tr>
                        <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>E-posta</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$email}</strong></td></tr>
                        <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Etkinlik Tipi</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$etkinlik_tip}</strong></td></tr>
                        <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Kişi Sayısı</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$kisi_sayisi}</strong></td></tr>
                        <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Tarih</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$tarih}</strong></td></tr>
                        <tr><td style='padding: 8px 0; color: #888; font-size: 14px;'>Lokasyon</td><td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$lokasyon}</strong></td></tr>
                    </table>
                    " . ($not ? "
                    <div style='margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05);'>
                        <p style='margin: 0; color: #888; font-size: 14px;'>Ek Notlar:</p>
                        <p style='margin: 5px 0 0 0; color: #fff; font-size: 14px; line-height: 1.5;'>{$not}</p>
                    </div>" : "") . "
                </div>
            </div>
        </div>
    ";

    $mail->send();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'E-posta gönderilemedi: ' . $e->getMessage()]);
}
