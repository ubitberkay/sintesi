<?php
/**
 * Sintesi - Veritabanı Güncelleme Scripti
 * 
 * Bu dosyayı tarayıcıdan çalıştırın: https://sintesi.com.tr/db_update.php
 */

require_once __DIR__ . '/config.php';


try {
    $pdo = veritabani_baglantisi();
    
    // 1. Durum Sütununu Güncelle (MySQL için ENUM kısıtlamasını kaldır)
    if (!local_mi()) {
        try {
            $pdo->exec("ALTER TABLE rezervasyonlar MODIFY COLUMN durum VARCHAR(30) DEFAULT 'beklemede'");
            echo "<p>✅ 'durum' sütunu güncellendi (MySQL ENUM kaldırıldı).</p>";
        } catch (Exception $e) {
            echo "<p>❌ Durum sütunu güncellenirken hata: " . $e->getMessage() . "</p>";
        }
    }

    // 2. Hatırlatma Sütununun Eklenmesi
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
    echo "<p><a href='/admin'>Admin Paneline Git</a></p>";

} catch (Exception $e) {
    die("Hata: " . $e->getMessage());
}
