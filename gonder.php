<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

// Form verilerini al
$ad = isset($_POST['ad']) ? htmlspecialchars(strip_tags(trim($_POST['ad']))) : '';
$eposta = isset($_POST['eposta']) ? htmlspecialchars(strip_tags(trim($_POST['eposta']))) : '';
$telefon = isset($_POST['telefon']) ? htmlspecialchars(strip_tags(trim($_POST['telefon']))) : '';
$mesaj = isset($_POST['mesaj']) ? htmlspecialchars(strip_tags(trim($_POST['mesaj']))) : '';

// Boş alan kontrolü
if(empty($ad) || empty($eposta) || empty($mesaj)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen zorunlu alanları doldurun (Ad, E-posta, Mesaj).']);
    exit;
}

if (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta adresi giriniz.']);
    exit;
}

$mail = new PHPMailer(true);

try {
    // Sunucu Ayarları
    $mail->isSMTP();                                            // SMTP kullan
    $mail->Host       = 'mail.sintesi.com.tr';                  // SMTP sunucusu
    $mail->SMTPAuth   = true;                                   // SMTP doğrulama
    $mail->Username   = 'info@sintesi.com.tr';                  // SMTP kullanıcı adı
    $mail->Password   = 'qwe12ASD?';               // SMTP şifresi
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Güvenlik (SSL)
    $mail->Port       = 465;                                    // TCP port

    // Karakter seti
    $mail->CharSet = 'UTF-8';

    // Alıcı ve Gönderici Ayarları
    $mail->setFrom('info@sintesi.com.tr', 'Sintesi İletişim Formu');
    // Gidecek adresler (Birden fazla alıcı)
    $mail->addAddress('bugra@sintesi.com.tr');
    $mail->addAddress('cagla@sintesi.com.tr');
    $mail->addAddress('ersinavsar@sintesi.com.tr');
    $mail->addAddress('iletisim@sintesi.com.tr');
    $mail->addAddress('info@sintesi.com.tr');
    $mail->addReplyTo($eposta, $ad);                            // Yanıtla butonu için gönderenin e-postası

    // İçerik
    $mail->isHTML(true);                                        // E-posta formatı HTML
    $mail->Subject = 'Sintesi Web Sitesinden Yeni Mesaj - ' . $ad;
    $mail->Body    = "
        <h3>Sintesi Web Sitesinden Yeni Bir Mesaj Aldınız</h3>
        <p><strong>Ad Soyad:</strong> {$ad}</p>
        <p><strong>E-posta:</strong> {$eposta}</p>
        <p><strong>Telefon:</strong> {$telefon}</p>
        <p><strong>Mesaj:</strong><br/>" . nl2br($mesaj) . "</p>
    ";
    
    // Sadece düz metin destekleyen istemciler için
    $mail->AltBody = "Ad Soyad: {$ad}\nE-posta: {$eposta}\nTelefon: {$telefon}\nMesaj:\n{$mesaj}";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Mesajınız başarıyla gönderildi!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Mesajınız gönderilemedi. Hata: {$mail->ErrorInfo}"]);
}
?>
