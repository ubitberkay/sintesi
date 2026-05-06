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

// CSRF Doğrulaması (Sadece POST istekleri için)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Geçersiz güvenlik jetonu (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.']);
        exit;
    }
}

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
    case 'create':
        manuelEkle($pdo);
        break;
    case 'settings_get':
        ayarlariGetir($pdo);
        break;
    case 'settings_save':
        ayarlariKaydet($pdo);
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
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT * FROM rezervasyonlar WHERE 1=1";
    $count_sql = "SELECT COUNT(*) FROM rezervasyonlar WHERE 1=1";
    $params = [];
    
    if (!empty($durum) && in_array($durum, ['beklemede', 'onaylandi', 'iptal'])) {
        $sql .= " AND durum = ?";
        $count_sql .= " AND durum = ?";
        $params[] = $durum;
    }
    
    if (!empty($tarih_bas)) {
        $sql .= " AND tarih >= ?";
        $count_sql .= " AND tarih >= ?";
        $params[] = $tarih_bas;
    }
    
    if (!empty($tarih_son)) {
        $sql .= " AND tarih <= ?";
        $count_sql .= " AND tarih <= ?";
        $params[] = $tarih_son;
    }
    
    if (!empty($search)) {
        $sql .= " AND (ad_soyad LIKE ? OR telefon LIKE ?)";
        $count_sql .= " AND (ad_soyad LIKE ? OR telefon LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Toplam sayfa hesaplama
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = (int)$stmt->fetchColumn();
    $total_pages = max(1, ceil($total_records / $limit));
    
    $sql .= " ORDER BY tarih ASC, saat ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rezervasyonlar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'data' => $rezervasyonlar,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records
        ]
    ]);
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
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
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
        
        $iptal_butonu = !empty($rez['iptal_kodu']) 
            ? "<a href='https://sintesi.com.tr/iptal.php?kod={$rez['iptal_kodu']}' style='display:inline-block; margin-bottom:15px; padding: 10px 20px; background: #ef4444; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;'>Rezervasyonu İptal Et</a><br>" 
            : "";

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
                    {$iptal_butonu}
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
    
    // Bugünkü rezervasyonlar (İptaller hariç)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rezervasyonlar WHERE tarih = ? AND durum != 'iptal'");
    $stmt->execute([$bugun]);
    $bugunki = $stmt->fetchColumn();
    
    // Bu haftaki (İptaller hariç)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rezervasyonlar WHERE tarih >= ? AND tarih <= ? AND durum != 'iptal'");
    $stmt->execute([$hafta_bas, $hafta_son]);
    $haftalik = $stmt->fetchColumn();
    
    // Bekleyen
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rezervasyonlar WHERE durum = 'beklemede'");
    $stmt->execute();
    $bekleyen = $stmt->fetchColumn();
    
    // Toplam (Tüm kayıtlar)
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

/**
 * Manuel rezervasyon ekle
 */
function manuelEkle($pdo) {
    $ad_soyad = $_POST['ad_soyad'] ?? '';
    $telefon = $_POST['telefon'] ?? '';
    $email = $_POST['email'] ?? '';
    $tarih = $_POST['tarih'] ?? '';
    $saat = $_POST['saat'] ?? '';
    $kisi = intval($_POST['kisi_sayisi'] ?? 2);
    $ozel = $_POST['ozel_istekler'] ?? '';
    
    if (empty($ad_soyad) || empty($telefon) || empty($tarih) || empty($saat)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen zorunlu alanları doldurun.']);
        return;
    }
    
    $iptal_kodu = bin2hex(random_bytes(16));
    
    $stmt = $pdo->prepare("
        INSERT INTO rezervasyonlar (ad_soyad, telefon, email, tarih, saat, kisi_sayisi, ozel_istekler, durum, iptal_kodu)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'onaylandi', ?)
    ");
    $stmt->execute([$ad_soyad, $telefon, $email, $tarih, $saat, $kisi, $ozel, $iptal_kodu]);
    
    // Onay maili gönder (Eğer e-posta adresi varsa)
    if (!empty($email)) {
        gonderOnayMaili([
            'ad_soyad' => $ad_soyad,
            'email' => $email,
            'tarih' => $tarih,
            'saat' => $saat,
            'kisi_sayisi' => $kisi,
            'iptal_kodu' => $iptal_kodu
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Rezervasyon başarıyla eklendi ve onay maili gönderildi.']);
}

/**
 * Ayarları getir
 */
function ayarlariGetir($pdo) {
    $stmt = $pdo->prepare("SELECT ayar_anahtari, ayar_degeri FROM ayarlar");
    $stmt->execute();
    $ayarlar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'kapasite' => isset($ayarlar['kapasite']) ? (int)$ayarlar['kapasite'] : 16,
            'kapali_gunler' => isset($ayarlar['kapali_gunler']) ? json_decode($ayarlar['kapali_gunler'], true) : []
        ]
    ]);
}

/**
 * Ayarları kaydet
 */
function ayarlariKaydet($pdo) {
    $kapasite = intval($_POST['kapasite'] ?? 16);
    $kapali_gunler = $_POST['kapali_gunler'] ?? '[]';
    
    $stmt = $pdo->prepare("UPDATE ayarlar SET ayar_degeri = ? WHERE ayar_anahtari = 'kapasite'");
    $stmt->execute([(string)$kapasite]);
    
    $stmt = $pdo->prepare("UPDATE ayarlar SET ayar_degeri = ? WHERE ayar_anahtari = 'kapali_gunler'");
    $stmt->execute([$kapali_gunler]);
    
    echo json_encode(['success' => true, 'message' => 'Ayarlar başarıyla kaydedildi.']);
}
?>
