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

// Galeri Tablosu Oluştur (Eğer yoksa)
try {
    if (local_mi()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS galeri (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            resim_yolu TEXT NOT NULL,
            thumb_yolu TEXT,
            siralama INTEGER DEFAULT 0,
            aciklama TEXT,
            eklenme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS galeri (
            id INT AUTO_INCREMENT PRIMARY KEY,
            resim_yolu VARCHAR(255) NOT NULL,
            thumb_yolu VARCHAR(255),
            siralama INT DEFAULT 0,
            aciklama VARCHAR(255),
            eklenme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
} catch (Exception $e) {
    // Hata durumunda sessizce devam edilebilir veya loglanabilir
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// CSRF Doğrulaması (Sadece POST istekleri için)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eğer POST isteği yapılmış ama $_POST boşsa, sunucu dosya boyutu limitine takılmış olabilir
    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'Dosya boyutu sunucu limitini aşıyor. Lütfen daha küçük bir dosya seçin veya sunucu ayarlarınızı (post_max_size) kontrol edin.']);
        exit;
    }

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
    case 'arrived':
        durumGuncelle($pdo, 'geldi');
        break;
    case 'no-show':
        durumGuncelle($pdo, 'gelmedi');
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
    case 'gallery_list':
        galeriListele($pdo);
        break;
    case 'gallery_upload':
        galeriYukle($pdo);
        break;
    case 'gallery_delete':
        galeriSil($pdo);
        break;
    case 'gallery_reorder':
        galeriSirala($pdo);
        break;
    case 'get_emails':
        musteriMailleriniGetir($pdo);
        break;
    case 'send_bulk_email':
        topluMailGonder($pdo);
        break;
    case 'export_excel':
        exportReservations($pdo);
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
    
    $sql = "SELECT r.*, 
            (SELECT COUNT(*) FROM rezervasyonlar r2 WHERE r2.telefon = r.telefon) as toplam_ziyaret
            FROM rezervasyonlar r WHERE 1=1";
    $count_sql = "SELECT COUNT(*) FROM rezervasyonlar WHERE 1=1";
    $params = [];
    
    if (!empty($durum) && in_array($durum, ['beklemede', 'onaylandi', 'iptal'])) {
        $sql .= " AND r.durum = ?";
        $count_sql .= " AND durum = ?";
        $params[] = $durum;
    }
    
    if (!empty($tarih_bas)) {
        $sql .= " AND r.tarih >= ?";
        $count_sql .= " AND tarih >= ?";
        $params[] = $tarih_bas;
    }
    
    if (!empty($tarih_son)) {
        $sql .= " AND r.tarih <= ?";
        $count_sql .= " AND tarih <= ?";
        $params[] = $tarih_son;
    }
    
    if (!empty($search)) {
        $sql .= " AND (r.ad_soyad LIKE ? OR r.telefon LIKE ?)";
        $count_sql .= " AND (ad_soyad LIKE ? OR telefon LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Toplam sayfa hesaplama
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = (int)$stmt->fetchColumn();
    $total_pages = max(1, ceil($total_records / $limit));
    
    $sql .= " ORDER BY r.tarih ASC, r.saat ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
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
    
    $durum_metinleri = [
        'onaylandi' => 'onaylandı',
        'iptal' => 'iptal edildi',
        'geldi' => 'geldi olarak işaretlendi',
        'gelmedi' => 'gelmedi olarak işaretlendi'
    ];
    $metin = $durum_metinleri[$yeni_durum] ?? $yeni_durum;
    echo json_encode(['success' => true, 'message' => "Rezervasyon {$metin}."]);
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
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        $mail->setFrom('info@sintesi.com.tr', 'Sintesi');
        $mail->addAddress($rez['email'], $rez['ad_soyad']);
        
        $is_en = (isset($rez['dil']) && $rez['dil'] === 'en');
        $tarih_format = $is_en ? date('F d, Y', strtotime($rez['tarih'])) : date('d.m.Y', strtotime($rez['tarih']));
        
        $mail->isHTML(true);
        $mail->Subject = $is_en ? "✅ Your Reservation is Confirmed - Sintesi" : "✅ Rezervasyonunuz Onaylandı - Sintesi";
        
        if ($is_en) {
            $mail->Body = "
                <div style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 15px; overflow: hidden; background: #000 url('https://sintesi.com.tr/background.png') no-repeat center center; background-size: cover; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);\">
                    <div style='padding: 40px 20px; text-align: center;'>
                        <img src='https://sintesi.com.tr/sintesi.webp' alt='Sintesi' style='max-width: 180px; margin-bottom: 20px;'>
                        <h1 style='margin: 0; font-size: 26px; font-family: Georgia, serif; color: #fff;'>Reservation Confirmed</h1>
                        <div style='width: 50px; height: 2px; background: #9D432C; margin: 20px auto;'></div>
                    </div>
                    <div style='padding: 0 40px 40px 40px;'>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Dear <strong>{$rez['ad_soyad']}</strong>,</p>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Your reservation request has been confirmed. We look forward to welcoming you to the Sintesi atmosphere.</p>
                        
                        <div style='background: rgba(255,255,255,0.05); padding: 25px; border-radius: 12px; margin: 30px 0; border: 1px solid rgba(255,255,255,0.1);'>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 8px 0; color: #888; font-size: 14px;'>Date</td>
                                    <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$tarih_format}</strong></td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #888; font-size: 14px;'>Time</td>
                                    <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$rez['saat']}</strong></td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #888; font-size: 14px;'>Guests</td>
                                    <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$rez['kisi_sayisi']} Person</strong></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style='text-align: center; margin: 40px 0;'>
                            <a href='https://sintesi.com.tr/iptal.php?kod={$rez['iptal_kodu']}' style='background: #9D432C; color: #fff; padding: 15px 35px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; display: inline-block;'>Cancel Reservation</a>
                        </div>
                        
                        <p style='font-size: 13px; color: #666; text-align: center;'>Note: Reservations may be automatically cancelled if not arrived 15 minutes after the reservation time.</p>
                    </div>
                    <div style='background: rgba(0,0,0,0.4); padding: 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.05);'>
                        <p style='margin: 0; color: #888; font-size: 13px;'>Metropol Istanbul Mall, B2 Floor, Atasehir/Istanbul</p>
                        <p style='margin: 10px 0 0 0; color: #888; font-size: 13px;'>+90 (216) XXX XX XX</p>
                    </div>
                </div>
            ";
        } else {
            $mail->Body = "
                <div style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 15px; overflow: hidden; background: #000 url('https://sintesi.com.tr/background.png') no-repeat center center; background-size: cover; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);\">
                    <div style='padding: 40px 20px; text-align: center;'>
                        <img src='https://sintesi.com.tr/sintesi.webp' alt='Sintesi' style='max-width: 180px; margin-bottom: 20px;'>
                        <h1 style='margin: 0; font-size: 26px; font-family: Georgia, serif; color: #fff;'>Rezervasyonunuz Onaylandı</h1>
                        <div style='width: 50px; height: 2px; background: #9D432C; margin: 20px auto;'></div>
                    </div>
                    <div style='padding: 0 40px 40px 40px;'>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Sayın <strong>{$rez['ad_soyad']}</strong>,</p>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Rezervasyon talebiniz onaylanmıştır. Sizi Sintesi atmosferinde ağırlamak için sabırsızlanıyoruz.</p>
                        
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
                        
                        <div style='text-align: center; margin: 40px 0;'>
                            <a href='https://sintesi.com.tr/iptal.php?kod={$rez['iptal_kodu']}' style='background: #9D432C; color: #fff; padding: 15px 35px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 14px; display: inline-block;'>Rezervasyonu İptal Et</a>
                        </div>
                        
                        <p style='font-size: 13px; color: #666; text-align: center;'>Not: Rezervasyon saatinden 15 dakika sonra gelinmediği takdirde rezervasyon otomatik olarak iptal edilebilir.</p>
                    </div>
                    <div style='background: rgba(0,0,0,0.4); padding: 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.05);'>
                        <p style='margin: 0; color: #888; font-size: 13px;'>Metropol İstanbul AVM, B2 Katı, Ataşehir/İstanbul</p>
                        <p style='margin: 10px 0 0 0; color: #888; font-size: 13px;'>+90 (216) XXX XX XX</p>
                    </div>
                </div>
            ";
        }
        
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
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        $mail->setFrom('info@sintesi.com.tr', 'Sintesi');
        $mail->addAddress($rez['email'], $rez['ad_soyad']);
        
        $is_en = (isset($rez['dil']) && $rez['dil'] === 'en');
        $tarih_format = $is_en ? date('F d, Y', strtotime($rez['tarih'])) : date('d.m.Y', strtotime($rez['tarih']));
        
        $mail->isHTML(true);
        $mail->Subject = $is_en ? "❌ Your Reservation is Cancelled - Sintesi" : "❌ Rezervasyonunuz İptal Edildi - Sintesi";
        
        if ($is_en) {
            $mail->Body = "
                <div style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 15px; overflow: hidden; background: #000 url('https://sintesi.com.tr/background.png') no-repeat center center; background-size: cover; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);\">
                    <div style='padding: 40px 20px; text-align: center;'>
                        <img src='https://sintesi.com.tr/sintesi.webp' alt='Sintesi' style='max-width: 180px; margin-bottom: 20px;'>
                        <h1 style='margin: 0; font-size: 26px; font-family: Georgia, serif; color: #fff;'>Reservation Cancellation</h1>
                        <div style='width: 50px; height: 2px; background: #9D432C; margin: 20px auto;'></div>
                    </div>
                    <div style='padding: 0 40px 40px 40px;'>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Dear <strong>{$rez['ad_soyad']}</strong>,</p>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Your reservation for {$tarih_format} at {$rez['saat']} has been cancelled.</p>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>We hope to see you another time.</p>
                        <p style='margin-top: 40px; font-size: 14px; color: #aaa; text-align: center;'>Thank you for your understanding,<br><strong>Sintesi Team</strong></p>
                    </div>
                    <div style='background: rgba(0,0,0,0.4); padding: 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.05);'>
                        <p style='margin: 0; color: #888; font-size: 13px;'>Metropol Istanbul Mall, B2 Floor, Atasehir/Istanbul</p>
                    </div>
                </div>
            ";
        } else {
            $mail->Body = "
                <div style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 15px; overflow: hidden; background: #000 url('https://sintesi.com.tr/background.png') no-repeat center center; background-size: cover; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);\">
                    <div style='padding: 40px 20px; text-align: center;'>
                        <img src='https://sintesi.com.tr/sintesi.webp' alt='Sintesi' style='max-width: 180px; margin-bottom: 20px;'>
                        <h1 style='margin: 0; font-size: 26px; font-family: Georgia, serif; color: #fff;'>Rezervasyon İptali</h1>
                        <div style='width: 50px; height: 2px; background: #9D432C; margin: 20px auto;'></div>
                    </div>
                    <div style='padding: 0 40px 40px 40px;'>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Sayın <strong>{$rez['ad_soyad']}</strong>,</p>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>{$tarih_format} saat {$rez['saat']} için olan rezervasyonunuz iptal edilmiştir.</p>
                        <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Sizi başka bir zaman aramızda görmeyi umuyoruz.</p>
                        <p style='margin-top: 40px; font-size: 14px; color: #aaa; text-align: center;'>Anlayışınız için teşekkür ederiz,<br><strong>Sintesi Ekibi</strong></p>
                    </div>
                    <div style='background: rgba(0,0,0,0.4); padding: 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.05);'>
                        <p style='margin: 0; color: #888; font-size: 13px;'>Metropol İstanbul AVM, B2 Katı, Ataşehir/İstanbul</p>
                    </div>
                </div>
            ";
        }
        
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
    global $local_ortam;
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

    // En popüler saatler (Tüm zamanlar, iptaller hariç)
    $stmt = $pdo->query("SELECT saat, COUNT(*) as count FROM rezervasyonlar WHERE durum != 'iptal' GROUP BY saat ORDER BY count DESC LIMIT 8");
    $saat_dagilimi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Durum dağılımı (Oranlar için)
    $stmt = $pdo->query("SELECT durum, COUNT(*) as count FROM rezervasyonlar GROUP BY durum");
    $durum_dagilimi = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Aylık Trend (Son 12 ay)
    $ay_sql = $local_ortam ? "strftime('%Y-%m', tarih)" : "DATE_FORMAT(tarih, '%Y-%m')";
    $stmt = $pdo->query("SELECT $ay_sql as ay, COUNT(*) as count FROM rezervasyonlar WHERE durum != 'iptal' GROUP BY ay ORDER BY ay DESC LIMIT 12");
    $aylik_trend = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'bugunki' => (int)$bugunki,
            'haftalik' => (int)$haftalik,
            'bekleyen' => (int)$bekleyen,
            'toplam' => (int)$toplam,
            'saat_dagilimi' => $saat_dagilimi,
            'durum_dagilimi' => $durum_dagilimi,
            'aylik_trend' => $aylik_trend
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
    try {
        // Tablonun varlığından emin ol
        if (local_mi()) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS ayarlar (ayar_anahtari TEXT PRIMARY KEY, ayar_degeri TEXT)");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS ayarlar (ayar_anahtari VARCHAR(50) PRIMARY KEY, ayar_degeri TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $stmt = $pdo->prepare("SELECT ayar_anahtari, ayar_degeri FROM ayarlar");
        $stmt->execute();
        $ayarlar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $varsayilan_saatler = [
            "1" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
            "2" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
            "3" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
            "4" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
            "5" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
            "6" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
            "0" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"]
        ];

        echo json_encode([
            'success' => true,
            'data' => [
                'kapasite' => isset($ayarlar['kapasite']) ? (int)$ayarlar['kapasite'] : 16,
                'kapali_gunler' => isset($ayarlar['kapali_gunler']) ? json_decode($ayarlar['kapali_gunler'], true) : new stdClass(),
                'calisma_saatleri' => isset($ayarlar['calisma_saatleri']) ? json_decode($ayarlar['calisma_saatleri'], true) : $varsayilan_saatler,
                'menu_yemek' => $ayarlar['menu_yemek'] ?? '',
                'menu_alkol' => $ayarlar['menu_alkol'] ?? '',
                'menu_yemek_en' => $ayarlar['menu_yemek_en'] ?? '',
                'menu_alkol_en' => $ayarlar['menu_alkol_en'] ?? ''
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'data' => [
                'kapasite' => 16,
                'kapali_gunler' => new stdClass(),
                'calisma_saatleri' => $varsayilan_saatler,
                'menu_yemek' => '',
                'menu_alkol' => '',
                'menu_yemek_en' => '',
                'menu_alkol_en' => ''
            ]
        ]);
    }
}

/**
 * Ayarları kaydet
 */
function ayarlariKaydet($pdo) {
        $kapasite = intval($_POST['kapasite'] ?? 16);
        $kapali_gunler = $_POST['kapali_gunler'] ?? '{}';
        $calisma_saatleri = $_POST['calisma_saatleri'] ?? '';
        
        // Güvenlik: JSON geçerli mi kontrol et
        // Güvenlik: JSON geçerli mi kontrol et
        $decoded = json_decode($kapali_gunler, true);
        if ($decoded === null) {
            $kapali_gunler = '{}';
        } else {
            $kapali_gunler = json_encode($decoded);
        }
    
        if (!empty($calisma_saatleri)) {
            $decoded_saatler = json_decode($calisma_saatleri, true);
            if ($decoded_saatler === null) {
                $calisma_saatleri = '';
            } else {
                $calisma_saatleri = json_encode($decoded_saatler);
            }
        }
    
        try {
            // Tablonun varlığından emin ol (SQLite/MySQL uyumlu)
            if (local_mi()) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS ayarlar (ayar_anahtari TEXT PRIMARY KEY, ayar_degeri TEXT)");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS ayarlar (ayar_anahtari VARCHAR(50) PRIMARY KEY, ayar_degeri TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
    
            // Ayarları Kaydet
            $stmt = $pdo->prepare("REPLACE INTO ayarlar (ayar_anahtari, ayar_degeri) VALUES ('kapasite', ?)");
            $stmt->execute([(string)$kapasite]);
            
            $stmt = $pdo->prepare("REPLACE INTO ayarlar (ayar_anahtari, ayar_degeri) VALUES ('kapali_gunler', ?)");
            $stmt->execute([$kapali_gunler]);

            if (!empty($calisma_saatleri)) {
                $stmt = $pdo->prepare("REPLACE INTO ayarlar (ayar_anahtari, ayar_degeri) VALUES ('calisma_saatleri', ?)");
                $stmt->execute([$calisma_saatleri]);
            }

            // Menü PDF Yüklemeleri
            $menu_keys = ['menu_yemek', 'menu_alkol', 'menu_yemek_en', 'menu_alkol_en'];
            foreach ($menu_keys as $key) {
                if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES[$key]['tmp_name'];
                    $name = basename($_FILES[$key]['name']);
                    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    
                    if ($extension === 'pdf') {
                        $new_name = $key . '_' . time() . '.pdf';
                        $upload_dir = __DIR__ . '/../uploads/menu/';
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                        
                        $destination = $upload_dir . $new_name;
                        
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $stmt = $pdo->prepare("REPLACE INTO ayarlar (ayar_anahtari, ayar_degeri) VALUES (?, ?)");
                            $stmt->execute([$key, 'uploads/menu/' . $new_name]);
                        }
                    }
                }
            }
        
        echo json_encode(['success' => true, 'message' => 'Ayarlar başarıyla kaydedildi.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    }
}

/**
 * Benzersiz müşteri maillerini getir
 */
function musteriMailleriniGetir($pdo) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT ad_soyad, email 
        FROM rezervasyonlar 
        WHERE email IS NOT NULL AND email != '' 
        ORDER BY ad_soyad ASC
    ");
    $stmt->execute();
    $emails = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $emails]);
}

/**
 * Toplu mail gönder
 */
function topluMailGonder($pdo) {
    $emails = $_POST['emails'] ?? [];
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($emails) || empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Lütfen alıcıları, başlığı ve mesajı doldurun.']);
        return;
    }

    if (!is_array($emails)) {
        $emails = explode(',', $emails);
    }

    require_once __DIR__ . '/../phpmailer/Exception.php';
    require_once __DIR__ . '/../phpmailer/PHPMailer.php';
    require_once __DIR__ . '/../phpmailer/SMTP.php';

    $success_count = 0;
    $error_count = 0;

    foreach ($emails as $email) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            
            $mail->setFrom('info@sintesi.com.tr', 'Sintesi');
            $mail->addAddress(trim($email));
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            
            // Mail şablonu (Onay maili ile benzer stil)
            $mail->Body = "
                <div style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 15px; overflow: hidden; background: #000 url('https://sintesi.com.tr/background.png') no-repeat center center; background-size: cover; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);\">
                    <div style='padding: 40px 20px; text-align: center;'>
                        <img src='https://sintesi.com.tr/sintesi.webp' alt='Sintesi' style='max-width: 150px; margin-bottom: 20px;'>
                        <div style='width: 50px; height: 2px; background: #9D432C; margin: 20px auto;'></div>
                    </div>
                    <div style='padding: 0 40px 40px 40px;'>
                        <h2 style='color: #9D432C; font-family: Georgia, serif; margin-bottom: 20px;'>{$subject}</h2>
                        <div style='font-size: 16px; line-height: 1.8; color: #e0e0e0; white-space: pre-wrap;'>{$message}</div>
                    </div>
                    <div style='background: rgba(0,0,0,0.4); padding: 30px; text-align: center; border-top: 1px solid rgba(255,255,255,0.05);'>
                        <p style='margin: 0; color: #888; font-size: 13px;'>Sintesi Restaurant</p>
                        <p style='margin: 10px 0 0 0; color: #888; font-size: 13px;'>Metropol İstanbul AVM, B2 Katı, Ataşehir/İstanbul</p>
                    </div>
                </div>
            ";
            
            if ($mail->send()) {
                $success_count++;
            } else {
                $error_count++;
            }
        } catch (Exception $e) {
            $error_count++;
            error_log('Özel mail gönderilemedi (' . $email . '): ' . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => "İşlem tamamlandı. {$success_count} mail başarıyla gönderildi, {$error_count} hata oluştu."
    ]);
}

/**
 * Tüm rezervasyonları Excel (XLS) olarak dışa aktar
 */
function exportReservations($pdo) {
    $filename = "sintesi_rezervasyonlar_" . date('Y-m-d_H-i') . ".xls";
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // UTF-8 için BOM ve HTML yapısı
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>td, th { border: 0.5pt solid #ccc; font-family: Calibri, sans-serif; }</style></head>';
    echo '<body>';
    echo '<table>';
    
    // Başlık satırı
    echo '<tr style="background-color: #9D432C; color: #ffffff; font-weight: bold;">';
    echo '<th>ID</th>';
    echo '<th>Ad Soyad</th>';
    echo '<th>E-posta</th>';
    echo '<th>Telefon</th>';
    echo '<th>Tarih</th>';
    echo '<th>Saat</th>';
    echo '<th>Kişi Sayısı</th>';
    echo '<th>Durum</th>';
    echo '<th>Özel İstekler</th>';
    echo '<th>Oluşturma Tarihi</th>';
    echo '<th>Hatırlatma</th>';
    echo '<th>Ziyaret Sayısı</th>';
    echo '</tr>';
    
    // Verileri çek (Ziyaret sayısıyla beraber)
    $stmt = $pdo->prepare("SELECT r.*, (SELECT COUNT(*) FROM rezervasyonlar r2 WHERE r2.telefon = r.telefon) as toplam_ziyaret FROM rezervasyonlar r ORDER BY r.tarih DESC, r.saat DESC");
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $durum_tr = [
            'beklemede' => 'Beklemede',
            'onaylandi' => 'Onaylandı',
            'iptal' => 'İptal',
            'geldi' => 'Geldi',
            'gelmedi' => 'Gelmedi'
        ];
        $durum = $durum_tr[$row['durum']] ?? ucfirst($row['durum']);
        $hatirlatma = (isset($row['hatirlatma_gonderildi']) && $row['hatirlatma_gonderildi']) ? 'Gönderildi' : 'Gönderilmedi';
        $tarih = date('d.m.Y', strtotime($row['tarih']));
        $ziyaret_metni = $row['toplam_ziyaret'] . " kez";

        echo '<tr>';
        echo '<td>' . $row['id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['ad_soyad']) . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td>' . htmlspecialchars($row['telefon']) . '</td>';
        echo '<td>' . $tarih . '</td>';
        echo '<td>' . $row['saat'] . '</td>';
        echo '<td>' . $row['kisi_sayisi'] . '</td>';
        echo '<td>' . $durum . '</td>';
        echo '<td>' . htmlspecialchars($row['ozel_istekler']) . '</td>';
        echo '<td>' . $row['olusturma_tarihi'] . '</td>';
        echo '<td>' . $hatirlatma . '</td>';
        echo '<td>' . $ziyaret_metni . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body></html>';
    exit;
}
/**
 * Galeri listesini getir
 */
function galeriListele($pdo) {
    $stmt = $pdo->query("SELECT * FROM galeri ORDER BY siralama ASC, id DESC");
    $items = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $items]);
}

/**
 * Galeriye resim yükle ve optimize et
 */
function galeriYukle($pdo) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Resim dosyası yüklenemedi.']);
        return;
    }

    $tmp_name = $_FILES['image']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Sadece JPG, PNG ve WebP formatları desteklenir.']);
        return;
    }

    $upload_dir = __DIR__ . '/../uploads/gallery/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.webp';
    $target_path = $upload_dir . $filename;

    // Görseli işle ve optimize et (WebP'ye dönüştür)
    try {
        if ($ext === 'png') {
            $source = imagecreatefrompng($tmp_name);
        } elseif ($ext === 'webp') {
            $source = imagecreatefromwebp($tmp_name);
        } else {
            $source = imagecreatefromjpeg($tmp_name);
        }

        if (!$source) throw new Exception('Resim işlenemedi.');

        $width = imagesx($source);
        $height = imagesy($source);
        $max_width = 1200;

        // Boyutlandırma gerekliyse (En boy oranını koru)
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = floor($height * ($max_width / $width));
            $target = imagecreatetruecolor($new_width, $new_height);
            
            // Saydamlık koruma (PNG -> WebP geçişi için)
            imagealphablending($target, false);
            imagesavealpha($target, true);
            
            imagecopyresampled($target, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagewebp($target, $target_path, 80); // %80 kalite ile WebP kaydet
        } else {
            imagewebp($source, $target_path, 80);
        }

        // Veritabanına kaydet
        $stmt = $pdo->prepare("INSERT INTO galeri (resim_yolu, siralama) VALUES (?, 0)");
        $stmt->execute(['uploads/gallery/' . $filename]);

        echo json_encode(['success' => true, 'message' => 'Resim optimize edilerek başarıyla yüklendi.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Görsel işleme hatası: ' . $e->getMessage()]);
    }
}

/**
 * Galeri öğesini sil
 */
function galeriSil($pdo) {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) return;

    $stmt = $pdo->prepare("SELECT resim_yolu FROM galeri WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item) {
        $file_path = __DIR__ . '/../' . $item['resim_yolu'];
        if (file_exists($file_path)) unlink($file_path);
        
        $stmt = $pdo->prepare("DELETE FROM galeri WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Resim silindi.']);
    }
}

/**
 * Galeri sıralamasını güncelle
 */
function galeriSirala($pdo) {
    $order = json_decode($_POST['order'] ?? '[]', true);
    if (!empty($order)) {
        foreach ($order as $index => $id) {
            $stmt = $pdo->prepare("UPDATE galeri SET siralama = ? WHERE id = ?");
            $stmt->execute([$index, $id]);
        }
        echo json_encode(['success' => true, 'message' => 'Sıralama güncellendi.']);
    }
}
?>
