<?php
/**
 * Sintesi - Veritabanı Kurulum Scripti
 * 
 * Bu dosyayı tarayıcıdan bir kez çalıştırın:
 *   Local: http://localhost:8000/kurulum.php
 *   Sunucu: https://sintesi.com.tr/kurulum.php
 * 
 * ⚠️ Kurulumdan sonra bu dosyayı SİLİN!
 */

require_once __DIR__ . '/config.php';

$mesajlar = [];
$hata = false;

try {
    $pdo = veritabani_baglantisi();
    
    if (local_mi()) {
        // SQLite tabloları
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rezervasyonlar (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ad_soyad TEXT NOT NULL,
                email TEXT,
                telefon TEXT NOT NULL,
                tarih TEXT NOT NULL,
                saat TEXT NOT NULL,
                kisi_sayisi INTEGER NOT NULL DEFAULT 2,
                ozel_istekler TEXT,
                durum TEXT DEFAULT 'beklemede',
                olusturma_tarihi DATETIME DEFAULT (datetime('now','localtime'))
            )
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_kullanicilar (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                kullanici_adi TEXT UNIQUE NOT NULL,
                sifre_hash TEXT NOT NULL
            )
        ");
    } else {
        // MySQL tabloları
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rezervasyonlar (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ad_soyad VARCHAR(100) NOT NULL,
                email VARCHAR(150),
                telefon VARCHAR(20) NOT NULL,
                tarih DATE NOT NULL,
                saat VARCHAR(10) NOT NULL,
                kisi_sayisi INT NOT NULL DEFAULT 2,
                ozel_istekler TEXT,
                durum ENUM('beklemede','onaylandi','iptal') DEFAULT 'beklemede',
                olusturma_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_kullanicilar (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kullanici_adi VARCHAR(50) UNIQUE NOT NULL,
                sifre_hash VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
    
    $mesajlar[] = '✅ Tablolar başarıyla oluşturuldu.';
    
    // Varsayılan admin kullanıcısı oluştur
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_kullanicilar WHERE kullanici_adi = ?");
    $stmt->execute(['admin']);
    $admin_var = $stmt->fetchColumn();
    
    if (!$admin_var) {
        $sifre_hash = password_hash('Sintesi2026!', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_kullanicilar (kullanici_adi, sifre_hash) VALUES (?, ?)");
        $stmt->execute(['admin', $sifre_hash]);
        $mesajlar[] = '✅ Varsayılan admin kullanıcısı oluşturuldu.';
        $mesajlar[] = '👤 Kullanıcı Adı: admin';
        $mesajlar[] = '🔑 Şifre: Sintesi2026!';
    } else {
        $mesajlar[] = 'ℹ️ Admin kullanıcısı zaten mevcut.';
    }
    
    $ortam = local_mi() ? 'SQLite (Local)' : 'MySQL (Production)';
    $mesajlar[] = "📦 Veritabanı: {$ortam}";
    
} catch (Exception $e) {
    $hata = true;
    $mesajlar[] = '❌ Hata: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sintesi - Kurulum</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Montserrat', sans-serif;
            background: #0c0c0c;
            color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            background: #151515;
            border: 1px solid rgba(157, 67, 44, 0.3);
            border-radius: 10px;
            padding: 3rem;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            font-family: 'Cormorant Garamond', serif;
            color: #9D432C;
            margin-bottom: 2rem;
            font-size: 2rem;
        }
        .mesaj {
            padding: 0.8rem 1rem;
            margin-bottom: 0.8rem;
            border-radius: 5px;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .basarili { background: rgba(34, 197, 94, 0.1); border-left: 3px solid #22c55e; }
        .hatali { background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; }
        .uyari {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(234, 179, 8, 0.1);
            border: 1px solid rgba(234, 179, 8, 0.3);
            border-radius: 5px;
            font-size: 0.85rem;
            color: #eab308;
        }
        a {
            display: inline-block;
            margin-top: 1.5rem;
            color: #9D432C;
            text-decoration: none;
            border: 1px solid #9D432C;
            padding: 0.7rem 1.5rem;
            border-radius: 5px;
            transition: 0.3s;
        }
        a:hover { background: #9D432C; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍽️ Sintesi Kurulum</h1>
        <?php foreach ($mesajlar as $m): ?>
            <div class="mesaj <?= $hata ? 'hatali' : 'basarili' ?>"><?= $m ?></div>
        <?php endforeach; ?>
        
        <?php if (!$hata): ?>
            <div class="uyari">
                ⚠️ <strong>Güvenlik Uyarısı:</strong> Kurulum tamamlandıktan sonra bu dosyayı (kurulum.php) sunucudan silin!
            </div>
            <a href="admin/">Admin Paneline Git →</a>
        <?php endif; ?>
    </div>
</body>
</html>
