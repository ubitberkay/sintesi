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
        $type = $_GET['type'] ?? 'normal';
        if (empty($tarih)) {
            echo json_encode(['success' => false, 'message' => 'Tarih gerekli.']);
            exit;
        }

        // Seçilen tarihteki saatlik dolulukları getir (rezervasyon tipine göre filtrele)
        if ($type === 'chefs_table') {
            $stmt = $pdo->prepare("SELECT saat, SUM(kisi_sayisi) as toplam_kisi FROM rezervasyonlar WHERE tarih = ? AND rezervasyon_tipi = 'chefs_table' AND durum != 'iptal' GROUP BY saat");
        } else {
            $stmt = $pdo->prepare("SELECT saat, SUM(kisi_sayisi) as toplam_kisi FROM rezervasyonlar WHERE tarih = ? AND (rezervasyon_tipi = 'normal' OR rezervasyon_tipi IS NULL OR rezervasyon_tipi = '') AND durum != 'iptal' GROUP BY saat");
        }
        $stmt->execute([$tarih]);
        $doluluk = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['19:00' => 5, '19:30' => 12]

        echo json_encode([
            'success' => true,
            'data' => $doluluk
        ]);
        exit;
    }

    if ($action === 'gallery_list') {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
        $sql = "SELECT resim_yolu, aciklama FROM galeri ORDER BY siralama ASC, id DESC";
        if ($limit > 0) $sql .= " LIMIT " . $limit;
        
        $stmt = $pdo->query($sql);
        $gallery = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $gallery]);
        exit;
    }

    if ($action === 'menu_list') {
        $type = $_GET['type'] ?? 'food';
        
        // Kategorileri çek
        $stmt = $pdo->prepare("SELECT * FROM menu_categories WHERE type = ? ORDER BY order_num ASC");
        $stmt->execute([$type]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categories as &$cat) {
            $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? AND status = 1 ORDER BY order_num ASC");
            $stmt->execute([$cat['id']]);
            $cat['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'data' => $categories]);
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

    $chefs_table_kapasite = 8;
    $chefs_table_kapali_gunler = new stdClass();
    $chefs_table_calisma_saatleri = [
        "1" => ["acilis" => "19:00", "kapanis" => "00:00", "durum" => "acik"],
        "2" => ["acilis" => "19:00", "kapanis" => "00:00", "durum" => "acik"],
        "3" => ["acilis" => "19:00", "kapanis" => "00:00", "durum" => "acik"],
        "4" => ["acilis" => "19:00", "kapanis" => "00:00", "durum" => "acik"],
        "5" => ["acilis" => "19:00", "kapanis" => "00:00", "durum" => "acik"],
        "6" => ["acilis" => "19:00", "kapanis" => "00:00", "durum" => "acik"],
        "0" => ["acilis" => "19:00", "kapanis" => "00:00", "durum" => "acik"]
    ];
    
    $varsayilan_chefs_table_details = [
        [
            'title_tr' => 'Kapasite',
            'title_en' => 'Capacity',
            'desc_tr' => 'En fazla 8 kişilik özel masa düzeni',
            'desc_en' => 'Private seating for up to 8 guests'
        ],
        [
            'title_tr' => 'Menü',
            'title_en' => 'Menu',
            'desc_tr' => 'Şefin o güne özel hazırladığı 7 aşamalı tadım menüsü',
            'desc_en' => '7-course tasting menu curated specially for the evening'
        ],
        [
            'title_tr' => 'Eşleşme',
            'title_en' => 'Pairing',
            'desc_tr' => 'Opsiyonel sommelier eşliğinde şarap uyumu',
            'desc_en' => 'Optional sommelier-guided wine pairing'
        ],
        [
            'title_tr' => 'Rezervasyon',
            'title_en' => 'Reservation',
            'desc_tr' => 'En az 48 saat önceden rezervasyon gereklidir',
            'desc_en' => 'At least 48 hours advance booking required'
        ]
    ];

    $varsayilan_chefs_table_menu = [
        [
            'num' => 1,
            'type_tr' => 'Soğuk Başlangıç',
            'type_en' => 'Cold Starter',
            'title_tr' => 'Ege Otları Jeli ve Marine Levrek',
            'title_en' => 'Aegean Herbs Jelly and Marinated Sea Bass',
            'desc_tr' => 'Limon otu yağı ve kurutulmuş havyar ile.',
            'desc_en' => 'With lemongrass oil and cured bottarga.'
        ],
        [
            'num' => 2,
            'type_tr' => 'Sıcak Başlangıç',
            'type_en' => 'Warm Starter',
            'title_tr' => 'Çıtır Kabak Çiçeği',
            'title_en' => 'Crispy Zucchini Flower',
            'desc_tr' => 'Köz patlıcan dolgulu kabak çiçeği, naneli süzme yoğurt sos ile.',
            'desc_en' => 'Zucchini flower filled with roasted eggplant, served with mint strained yogurt sauce.'
        ],
        [
            'num' => 3,
            'type_tr' => 'Şefin Makarnası',
            'type_en' => 'Chef\'s Pasta',
            'title_tr' => 'Adaçaylı Tagliolini',
            'title_en' => 'Sage Tagliolini',
            'desc_tr' => 'Taze trüf mantarı ve ev yapımı adaçaylı tereyağlı tagliolini.',
            'desc_en' => 'Fresh truffle and homemade tagliolini with sage butter.'
        ],
        [
            'num' => 4,
            'type_tr' => 'Deniz Mahsulü',
            'type_en' => 'Seafood',
            'title_tr' => 'Tava Fener Balığı',
            'title_en' => 'Pan-Seared Monkfish',
            'desc_tr' => 'Safranlı patates püresi ve yaban mersini sos eşliğinde.',
            'desc_en' => 'With saffron potato purée and wild berry sauce.'
        ],
        [
            'num' => 5,
            'type_tr' => 'Ana Yemek',
            'type_en' => 'Main Course',
            'title_tr' => 'Ağır Ateşte Pişmiş Dana Yanağı',
            'title_en' => 'Slow-Braised Beef Cheek',
            'desc_tr' => 'Fırınlanmış kök sebzeler ve kemik iliği sosu ile.',
            'desc_en' => 'With roasted root vegetables and bone marrow reduction.'
        ],
        [
            'num' => 6,
            'type_tr' => 'Damak Temizleyici',
            'type_en' => 'Pre-Dessert',
            'title_tr' => 'Fesleğenli ve Limonlu Sorbe',
            'title_en' => 'Basil and Lemon Sorbet',
            'desc_tr' => 'Ev yapımı ferahlatıcı sorbe.',
            'desc_en' => 'Refreshing homemade sorbet.'
        ],
        [
            'num' => 7,
            'type_tr' => 'İmza Tatlı',
            'type_en' => 'Signature Dessert',
            'title_tr' => 'Çikolatalı ve İncirli Ganaj',
            'title_en' => 'Chocolate and Fig Ganache',
            'desc_tr' => 'Bitter çikolata ganaj, karamelize incir ve kakule esanslı dondurma ile.',
            'desc_en' => 'Dark chocolate ganache, caramelized figs, and cardamom-infused ice cream.'
        ]
    ];

    $chefs_table_details = $varsayilan_chefs_table_details;
    $chefs_table_menu = $varsayilan_chefs_table_menu;
    
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

        if (isset($ayarlar['chefs_table_details'])) {
            $chefs_table_details = json_decode($ayarlar['chefs_table_details'], true) ?: $varsayilan_chefs_table_details;
        }

        if (isset($ayarlar['chefs_table_menu'])) {
            $chefs_table_menu = json_decode($ayarlar['chefs_table_menu'], true) ?: $varsayilan_chefs_table_menu;
        }

        if (isset($ayarlar['chefs_table_kapasite'])) {
            $chefs_table_kapasite = (int)$ayarlar['chefs_table_kapasite'];
        }

        if (isset($ayarlar['chefs_table_kapali_gunler'])) {
            $chefs_table_kapali_gunler = json_decode($ayarlar['chefs_table_kapali_gunler'], true) ?: new stdClass();
        }

        if (isset($ayarlar['chefs_table_calisma_saatleri'])) {
            $chefs_table_calisma_saatleri = json_decode($ayarlar['chefs_table_calisma_saatleri'], true) ?: $chefs_table_calisma_saatleri;
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
            'menu_tatli_en' => $menu_tatli_en ?? '',
            'chefs_table_details' => $chefs_table_details,
            'chefs_table_menu' => $chefs_table_menu,
            'chefs_table_kapasite' => $chefs_table_kapasite,
            'chefs_table_kapali_gunler' => $chefs_table_kapali_gunler,
            'chefs_table_calisma_saatleri' => $chefs_table_calisma_saatleri,
            'kapali_gun_mesaji_tr' => $ayarlar['kapali_gun_mesaji_tr'] ?? 'Restoranımız bugün kapalıdır.',
            'kapali_gun_mesaji_en' => $ayarlar['kapali_gun_mesaji_en'] ?? 'Our restaurant is closed today.',
            'chefs_table_kapali_gun_mesaji_tr' => $ayarlar['chefs_table_kapali_gun_mesaji_tr'] ?? "Chef's Table bugün hizmet vermemektedir.",
            'chefs_table_kapali_gun_mesaji_en' => $ayarlar['chefs_table_kapali_gun_mesaji_en'] ?? 'Chef\'s Table is not serving today.'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
