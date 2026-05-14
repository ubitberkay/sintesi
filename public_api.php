<?php
/**
 * Sintesi - Public API
 * Frontend (rezervasyon.html) tarafına kapalı günler ve kapasite gibi
 * herkese açık (halka açık) ayarları JSON formatında döner.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

try {
    $pdo = veritabani_baglantisi();
    
    // İşlem Belirle
    $action = $_GET['action'] ?? 'settings';
    
    if ($action === 'check_availability') {
        $tarih = $_GET['date'] ?? '';
        if (empty($tarih)) {
            echo json_encode(['success' => false, 'message' => 'Tarih gerekli.']);
            exit;
        }

        // Seçilen tarihteki saatlik dolulukları getir
        $stmt = $pdo->prepare("SELECT saat, SUM(kisi_sayisi) as toplam_kisi FROM rezervasyonlar WHERE tarih = ? AND durum != 'iptal' GROUP BY saat");
        $stmt->execute([$tarih]);
        $doluluk = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['19:00' => 5, '19:30' => 12]

        echo json_encode([
            'success' => true,
            'data' => $doluluk
        ]);
        exit;
    }

    // Varsayılan: Ayarları çek
    $kapasite = 16;
    $kapali_gunler = new stdClass();
    $calisma_saatleri = [
        "1" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
        "2" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
        "3" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
        "4" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
        "5" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
        "6" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"],
        "0" => ["acilis" => "15:00", "kapanis" => "00:00", "durum" => "acik"]
    ];
    
    try {
        if (local_mi()) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS ayarlar (ayar_anahtari TEXT PRIMARY KEY, ayar_degeri TEXT)");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS ayarlar (ayar_anahtari VARCHAR(50) PRIMARY KEY, ayar_degeri TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $stmt = $pdo->prepare("SELECT ayar_anahtari, ayar_degeri FROM ayarlar");
        $stmt->execute();
        $ayarlar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (isset($ayarlar['kapasite'])) {
            $kapasite = (int)$ayarlar['kapasite'];
        }
        
        if (isset($ayarlar['kapali_gunler'])) {
            $kapali_gunler = json_decode($ayarlar['kapali_gunler'], true) ?: new stdClass();
        }

        if (isset($ayarlar['calisma_saatleri'])) {
            $calisma_saatleri = json_decode($ayarlar['calisma_saatleri'], true) ?: $calisma_saatleri;
        }

        $menu_yemek = $ayarlar['menu_yemek'] ?? '';
        $menu_alkol = $ayarlar['menu_alkol'] ?? '';
        $menu_tatli = $ayarlar['menu_tatli'] ?? '';
        $menu_yemek_en = $ayarlar['menu_yemek_en'] ?? '';
        $menu_alkol_en = $ayarlar['menu_alkol_en'] ?? '';
        $menu_tatli_en = $ayarlar['menu_tatli_en'] ?? '';
    } catch (Exception $e) {}
    
    echo json_encode([
        'success' => true,
        'data' => [
            'kapasite' => $kapasite,
            'kapali_gunler' => $kapali_gunler,
            'calisma_saatleri' => $calisma_saatleri,
            'menu_yemek' => $menu_yemek ?? '',
            'menu_alkol' => $menu_alkol ?? '',
            'menu_tatli' => $menu_tatli ?? '',
            'menu_yemek_en' => $menu_yemek_en ?? '',
            'menu_alkol_en' => $menu_alkol_en ?? '',
            'menu_tatli_en' => $menu_tatli_en ?? ''
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
