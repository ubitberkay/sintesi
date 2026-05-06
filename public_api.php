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
    
    // Varsayılan değerler
    $kapasite = 16;
    $kapali_gunler = [];
    
    // Ayarları çek
    $stmt = $pdo->prepare("SELECT ayar_anahtari, ayar_degeri FROM ayarlar");
    $stmt->execute();
    $ayarlar = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($ayarlar['kapasite'])) {
        $kapasite = (int)$ayarlar['kapasite'];
    }
    
    if (isset($ayarlar['kapali_gunler'])) {
        $kapali_gunler = json_decode($ayarlar['kapali_gunler'], true) ?: [];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'kapasite' => $kapasite,
            'kapali_gunler' => $kapali_gunler
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'data' => [
            'kapasite' => 16,
            'kapali_gunler' => []
        ]
    ]);
}
?>
