<?php
/**
 * Sintesi - Veritabanı Güncelleme Scripti
 * Yeni özellikler için gerekli veritabanı değişikliklerini yapar.
 * Bu dosyayı bir kez çalıştırdıktan sonra silebilirsiniz.
 */

require_once __DIR__ . '/config.php';

try {
    $pdo = veritabani_baglantisi();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Veritabanı Güncelleme İşlemi Başladı</h2>";
    
    // 1. İptal Kodu Sütununun Eklenmesi
    try {
        // Sütun var mı kontrol et
        if (local_mi()) {
            // SQLite
            $result = $pdo->query("PRAGMA table_info(rezervasyonlar)");
            $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array('iptal_kodu', $columns)) {
                $pdo->exec("ALTER TABLE rezervasyonlar ADD COLUMN iptal_kodu VARCHAR(64)");
                echo "<p>✅ 'iptal_kodu' sütunu eklendi.</p>";
            } else {
                echo "<p>ℹ️ 'iptal_kodu' sütunu zaten mevcut.</p>";
            }
        } else {
            // MySQL
            $stmt = $pdo->prepare("SHOW COLUMNS FROM rezervasyonlar LIKE 'iptal_kodu'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE rezervasyonlar ADD COLUMN iptal_kodu VARCHAR(64) DEFAULT NULL");
                echo "<p>✅ 'iptal_kodu' sütunu eklendi.</p>";
            } else {
                echo "<p>ℹ️ 'iptal_kodu' sütunu zaten mevcut.</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ İptal kodu sütunu eklenirken hata oluştu: " . $e->getMessage() . "</p>";
    }
    
    // 2. Ayarlar Tablosunun Oluşturulması
    try {
        if (local_mi()) {
            // SQLite
            $pdo->exec("CREATE TABLE IF NOT EXISTS ayarlar (
                ayar_anahtari VARCHAR(50) PRIMARY KEY,
                ayar_degeri TEXT
            )");
        } else {
            // MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS ayarlar (
                ayar_anahtari VARCHAR(50) PRIMARY KEY,
                ayar_degeri TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        echo "<p>✅ 'ayarlar' tablosu kontrol edildi/oluşturuldu.</p>";
        
        // Varsayılan Ayarları Ekle
        $ayarlar = [
            'kapasite' => '16',
            'kapali_gunler' => '[]'
        ];
        
        foreach ($ayarlar as $anahtar => $deger) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ayarlar WHERE ayar_anahtari = ?");
            $stmt->execute([$anahtar]);
            if ($stmt->fetchColumn() == 0) {
                $stmt = $pdo->prepare("INSERT INTO ayarlar (ayar_anahtari, ayar_degeri) VALUES (?, ?)");
                $stmt->execute([$anahtar, $deger]);
                echo "<p>✅ Varsayılan ayar eklendi: {$anahtar}</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Ayarlar tablosu oluşturulurken hata oluştu: " . $e->getMessage() . "</p>";
    }
    
    // 3. Hatırlatma Sütununun Eklenmesi
    try {
        if (local_mi()) {
            $result = $pdo->query("PRAGMA table_info(rezervasyonlar)");
            $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array('hatirlatma_gonderildi', $columns)) {
                $pdo->exec("ALTER TABLE rezervasyonlar ADD COLUMN hatirlatma_gonderildi INTEGER DEFAULT 0");
                echo "<p>✅ 'hatirlatma_gonderildi' sütunu eklendi.</p>";
            } else {
                echo "<p>ℹ️ 'hatirlatma_gonderildi' sütunu zaten mevcut.</p>";
            }
        } else {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM rezervasyonlar LIKE 'hatirlatma_gonderildi'");
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE rezervasyonlar ADD COLUMN hatirlatma_gonderildi TINYINT(1) DEFAULT 0");
                echo "<p>✅ 'hatirlatma_gonderildi' sütunu eklendi.</p>";
            } else {
                echo "<p>ℹ️ 'hatirlatma_gonderildi' sütunu zaten mevcut.</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p>❌ Hatırlatma sütunu eklenirken hata oluştu: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Güncelleme Tamamlandı!</h3>";
    echo "<p style='color:red'>Güvenliğiniz için lütfen bu dosyayı (db_update.php) sunucudan siliniz.</p>";
    echo "<p><a href='/admin'>Admin Paneline Git</a></p>";

} catch (Exception $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>
