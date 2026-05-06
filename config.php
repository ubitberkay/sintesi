<?php
/**
 * Sintesi - Veritabanı Konfigürasyon Dosyası
 */
date_default_timezone_set('Europe/Istanbul');

// ============================================================
// ORTAM TESPİTİ
// ============================================================
// Sunucu adresine bakarak local mı production mı belirliyoruz
$sunucu_adi = $_SERVER['SERVER_NAME'] ?? 'localhost';
$local_ortam = in_array($sunucu_adi, ['localhost', '127.0.0.1', '::1']);

// ============================================================
// MySQL AYARLARI (cPanel / Production)
// ============================================================
// cPanel'den veritabanı oluşturup bilgileri buraya yazın
define('DB_HOST', 'localhost');
define('DB_NAME', 'sintesic_rezervasyon');    // cPanel'de oluşturduğunuz veritabanı adı
define('DB_USER', 'sintesic_admin');          // cPanel'de oluşturduğunuz veritabanı kullanıcısı
define('DB_PASS', 'qwe12ASD?');     // Veritabanı kullanıcı şifresi

// ============================================================
// VERİTABANI BAĞLANTISI
// ============================================================
function veritabani_baglantisi() {
    global $local_ortam;
    
    if ($local_ortam) {
        // LOCAL: SQLite kullan (MySQL kurmanıza gerek yok)
        $db_dosyasi = __DIR__ . '/data/sintesi.db';
        $db_klasor = __DIR__ . '/data';
        
        if (!is_dir($db_klasor)) {
            mkdir($db_klasor, 0755, true);
        }
        
        try {
            $pdo = new PDO('sqlite:' . $db_dosyasi);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA journal_mode=WAL;');
            return $pdo;
        } catch (PDOException $e) {
            die('SQLite bağlantı hatası: ' . $e->getMessage());
        }
    } else {
        // PRODUCTION: MySQL kullan
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            die('MySQL bağlantı hatası: ' . $e->getMessage());
        }
    }
}

// ============================================================
// YARDIMCI FONKSİYONLAR
// ============================================================
function local_mi() {
    global $local_ortam;
    return $local_ortam;
}

// Oturum başlat (her sayfada kullanılacak)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token oluştur (varsa yeniden oluşturma)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
