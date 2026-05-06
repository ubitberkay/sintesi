<?php
/**
 * Sintesi - Admin API
 * 
 * Rezervasyon yönetim işlemleri (listeleme, onaylama, iptal, silme)
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Oturum kontrolü
if (!isset($_SESSION['admin_giris']) || $_SESSION['admin_giris'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$pdo = veritabani_baglantisi();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        listeleRezervasyonlar($pdo);
        break;
    case 'approve':
        durumGuncelle($pdo, 'onaylandi');
        break;
    case 'reject':
        durumGuncelle($pdo, 'iptal');
        break;
    case 'delete':
        silRezervasyonu($pdo);
        break;
    case 'stats':
        istatistikler($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
}

/**
 * Rezervasyonları listele
 */
function listeleRezervasyonlar($pdo) {
    $durum = $_GET['durum'] ?? '';
    $tarih_bas = $_GET['tarih_bas'] ?? '';
    $tarih_son = $_GET['tarih_son'] ?? '';
    
    $sql = "SELECT * FROM rezervasyonlar WHERE 1=1";
    $params = [];
    
    if (!empty($durum) && in_array($durum, ['beklemede', 'onaylandi', 'iptal'])) {
        $sql .= " AND durum = ?";
        $params[] = $durum;
    }
    
    if (!empty($tarih_bas)) {
        $sql .= " AND tarih >= ?";
        $params[] = $tarih_bas;
    }
    
    if (!empty($tarih_son)) {
        $sql .= " AND tarih <= ?";
        $params[] = $tarih_son;
    }
    
    $sql .= " ORDER BY tarih ASC, saat ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rezervasyonlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $rezervasyonlar]);
}

/**
 * Rezervasyon durumunu güncelle
 */
function durumGuncelle($pdo, $yeni_durum) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz rezervasyon ID.']);
        return;
    }
    
    // Önce rezervasyon bilgilerini al (mail göndermek için)
    $stmt = $pdo->prepare("SELECT * FROM rezervasyonlar WHERE id = ?");
    $stmt->execute([$id]);
    $rezervasyon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rezervasyon) {
        echo json_encode(['success' => false, 'message' => 'Rezervasyon bulunamadı.']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE rezervasyonlar SET durum = ? WHERE id = ?");
    $stmt->execute([$yeni_durum, $id]);
    
    // Onay veya İptal maili gönder (Eğer e-posta adresi varsa ve localde değilsek)
    if (!empty($rezervasyon['email']) && !local_mi()) {
        if ($yeni_durum === 'onaylandi') {
            gonderOnayMaili($rezervasyon);
        } elseif ($yeni_durum === 'iptal') {
            gonderIptalMaili($rezervasyon);
        }
    }
    
    $durum_metin = $yeni_durum === 'onaylandi' ? 'onaylandı' : 'iptal edildi';
    echo json_encode(['success' => true, 'message' => "Rezervasyon {$durum_metin}."]);
}

/**
 * Müşteriye onay maili gönderir
 */
function gonderOnayMaili($rez) {
    require_once __DIR__ . '/../phpmailer/Exception.php';
    require_once __DIR__ . '/../phpmailer/PHPMailer.php';
    require_once __DIR__ . '/../phpmailer/SMTP.php';
    
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
        $mail->Subject = "✅ Rezervasyonunuz Onaylandı - Sintesi";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
                <div style='background: #9D432C; color: white; padding: 30px; text-align: center;'>
                    <h1 style='margin: 0; font-size: 24px;'>Rezervasyonunuz Onaylandı!</h1>
                </div>
                <div style='padding: 30px; background: #fff; color: #333;'>
                    <p>Sayın <strong>{$rez['ad_soyad']}</strong>,</p>
                    <p>Sintesi'de yapmış olduğunuz rezervasyon talebiniz başarıyla onaylanmıştır. Sizi ağırlamaktan mutluluk duyacağız.</p>
                    
                    <div style='background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Tarih:</strong> {$tarih_format}</p>
                        <p style='margin: 5px 0;'><strong>Saat:</strong> {$rez['saat']}</p>
                        <p style='margin: 5px 0;'><strong>Kişi Sayısı:</strong> {$rez['kisi_sayisi']} Kişi</p>
                    </div>
                    
                    <p>Herhangi bir değişiklik durumunda bize <strong>+90 (216) XXX XX XX</strong> numaralı telefondan ulaşabilirsiniz.</p>
                    <p style='margin-top: 30px;'>Görüşmek üzere,<br><strong>Sintesi Ekibi</strong></p>
                </div>
                <div style='background: #f4f4f4; padding: 20px; text-align: center; color: #888; font-size: 12px;'>
                    Metropol İstanbul AVM, B2 Katı, Ataşehir/İstanbul
                </div>
            </div>
        ";
        
        $mail->send();
    } catch (Exception $e) {
        error_log('Onay maili gönderilemedi: ' . $e->getMessage());
    }
}

/**
 * Müşteriye iptal maili gönderir
 */
function gonderIptalMaili($rez) {
    require_once __DIR__ . '/../phpmailer/Exception.php';
    require_once __DIR__ . '/../phpmailer/PHPMailer.php';
    require_once __DIR__ . '/../phpmailer/SMTP.php';
    
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'mail.sintesi.com.tr';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@sintesi.com.tr';
        $mail->Password   = 'qwe12ASD?';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        $mail->setFrom('info@sintesi.com.tr', 'Sintesi');
        $mail->addAddress($rez['email'], $rez['ad_soyad']);
        
        $tarih_format = date('d.m.Y', strtotime($rez['tarih']));
        
        $mail->isHTML(true);
        $mail->Subject = "❌ Rezervasyonunuz İptal Edildi - Sintesi";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #eee; border-radius: 10px; overflow: hidden;'>
                <div style='background: #ef4444; color: white; padding: 30px; text-align: center;'>
                    <h1 style='margin: 0; font-size: 24px;'>Rezervasyonunuz İptal Edildi</h1>
                </div>
                <div style='padding: 30px; background: #fff; color: #333;'>
                    <p>Sayın <strong>{$rez['ad_soyad']}</strong>,</p>
                    <p>Üzülerek bildiririz ki, Sintesi'de yapmış olduğunuz rezervasyon talebiniz iptal edilmiştir. İptal sebebi genellikle yoğunluk, kapasite doluluğu veya özel bir durum olabilmektedir.</p>
                    
                    <div style='background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Tarih:</strong> {$tarih_format}</p>
                        <p style='margin: 5px 0;'><strong>Saat:</strong> {$rez['saat']}</p>
                        <p style='margin: 5px 0;'><strong>Kişi Sayısı:</strong> {$rez['kisi_sayisi']} Kişi</p>
                    </div>
                    
                    <p>Yeni bir tarih planlamak veya detaylı bilgi almak isterseniz bize <strong>+90 (216) XXX XX XX</strong> numaralı telefondan ulaşabilirsiniz.</p>
                    <p style='margin-top: 30px;'>Anlayışınız için teşekkür ederiz,<br><strong>Sintesi Ekibi</strong></p>
                </div>
                <div style='background: #f4f4f4; padding: 20px; text-align: center; color: #888; font-size: 12px;'>
                    Metropol İstanbul AVM, B2 Katı, Ataşehir/İstanbul
                </div>
            </div>
        ";
        
        $mail->send();
    } catch (Exception $e) {
        error_log('İptal maili gönderilemedi: ' . $e->getMessage());
    }
}

/**
 * Rezervasyonu sil
 */
function silRezervasyonu($pdo) {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz rezervasyon ID.']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM rezervasyonlar WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'Rezervasyon silindi.']);
}

/**
 * İstatistikleri getir
 */
function istatistikler($pdo) {
    $bugun = date('Y-m-d');
    $hafta_bas = date('Y-m-d', strtotime('monday this week'));
    $hafta_son = date('Y-m-d', strtotime('sunday this week'));
    
    // Bugünkü rezervasyonlar
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rezervasyonlar WHERE tarih = ?");
    $stmt->execute([$bugun]);
    $bugunki = $stmt->fetchColumn();
    
    // Bu haftaki
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rezervasyonlar WHERE tarih >= ? AND tarih <= ?");
    $stmt->execute([$hafta_bas, $hafta_son]);
    $haftalik = $stmt->fetchColumn();
    
    // Bekleyen
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rezervasyonlar WHERE durum = 'beklemede'");
    $stmt->execute();
    $bekleyen = $stmt->fetchColumn();
    
    // Toplam
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rezervasyonlar");
    $stmt->execute();
    $toplam = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'bugunki' => (int)$bugunki,
            'haftalik' => (int)$haftalik,
            'bekleyen' => (int)$bekleyen,
            'toplam' => (int)$toplam
        ]
    ]);
}
?>
