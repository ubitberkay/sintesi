<?php
require_once __DIR__ . '/config.php';

$pdo = veritabani_baglantisi();

try {
    // Önce tabloları temizleyelim (taze bir kurulum için)
    $pdo->exec("DELETE FROM menu_items");
    $pdo->exec("DELETE FROM menu_categories");
    
    // SQLite için AI sıfırlama
    if (local_mi()) {
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='menu_items'");
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='menu_categories'");
    } else {
        $pdo->exec("ALTER TABLE menu_items AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE menu_categories AUTO_INCREMENT = 1");
    }

    echo "Veritabanı temizlendi. Seeding işlemi başlıyor...<br>";

    // --- YEMEKLER ---
    $food_data = [
        ['Başlangıçlar', 'Starters', [
            ['Yer Elması Velouté', 'Jerusalem Artichoke Velouté', 'Kavrulmuş kabak çekirdeği, kızarmış adaçayı, ekşi krema', 'Roasted pumpkin seeds, fried sage, sour cream', '100 TL'],
            ['Izgara Kuşkonmaz', 'Grilled Asparagus', 'Şalot indirgemesi, Kars eski kaşar', 'Shallot reduction, Kars aged kashar cheese', '100 TL'],
            ['Fırınlanmış Pancar', 'Roasted Beetroot', 'Keçi peyniri kreması, fındık, taze otlar', 'Goat cheese cream, hazelnuts, fresh herbs', '100 TL'],
            ['Burrata', 'Burrata', 'Konfit domates, fesleğen pesto, çıtır focaccia', 'Confit tomatoes, basil pesto, crispy focaccia', '100 TL']
        ]],
        ['Salatalar', 'Salads', [
            ['Sintesi Bahçe Salatası', 'Sintesi Garden Salad', 'Yedikule marul, taze otlar, hardal sos', 'Yedikule lettuce, fresh herbs, mustard dressing', '100 TL'],
            ['Keçi Peynirli Salata', 'Goat Cheese Salad', 'Roka, ceviz, nar ekşisi sos', 'Arugula, walnuts, pomegranate dressing', '100 TL']
        ]],
        ['Makarna & Risotto', 'Pasta & Risotto', [
            ['Yabani Mantarlı Risotto', 'Wild Mushroom Risotto', 'Trüf yağı, taze kekik, parmesan', 'Truffle oil, fresh thyme, parmesan', '100 TL'],
            ['Balkabağı Tortellini', 'Pumpkin Tortellini', 'Adaçayı tereyağı, kavrulmuş badem', 'Sage butter, roasted almonds', '100 TL'],
            ['Deniz Mahsüllü Linguine', 'Seafood Linguine', 'Karides, kalamar, sarımsak, beyaz şarap sos', 'Shrimp, calamari, garlic, white wine sauce', '100 TL']
        ]],
        ['Ana Yemekler', 'Main Courses', [
            ['Ağır Ateşte Pişmiş Kuzu İncik', 'Slow Roasted Lamb Shank', 'Kök sebzeler, patates püresi', 'Root vegetables, potato puree', '100 TL'],
            ['Tavada Levrek', 'Pan-Seared Sea Bass', 'Sote pazı, limonlu tereyağı sos', 'Sauteed chard, lemon butter sauce', '100 TL'],
            ['Dana Bonfile', 'Beef Tenderloin', 'Izgara sebzeler, kırmızı şarap sos', 'Grilled vegetables, red wine reduction', '100 TL'],
            ['Fırınlanmış Karnabahar', 'Roasted Cauliflower (Vegan)', 'Tahini sos, nar, antep fıstığı', 'Tahini sauce, pomegranate, pistachios', '100 TL']
        ]],
        ['Tatlılar', 'Desserts', [
            ['Tiramisu', 'Tiramisu', 'Mascarpone, espresso, savoiardi', 'Mascarpone, espresso, savoiardi', '100 TL'],
            ['Çikolatalı Fondan', 'Chocolate Fondant', 'Vanilyalı dondurma ile', 'With vanilla ice cream', '100 TL'],
            ['Limon Sorbe', 'Lemon Sorbet', 'Taze nane ile', 'With fresh mint', '100 TL']
        ]],
        ['Peynir Seçkileri', 'Cheese Selection', [
            ['Yerel ve İthal Peynir Tabağı', 'Local & International Cheese Plate', 'Kuru meyveler ve kraker eşliğinde', 'With dried fruits and crackers', '100 TL']
        ]],
        ['Kahveler', 'Coffee', [
            ['Espresso', 'Espresso', '', '', '100 TL'],
            ['Americano', 'Americano', '', '', '100 TL'],
            ['Latte', 'Latte', '', '', '100 TL'],
            ['Türk Kahvesi', 'Turkish Coffee', '', '', '100 TL']
        ]],
        ['Çaylar', 'Tea', [
            ['Demleme Çay', 'Brewed Tea', '', '', '100 TL'],
            ['Ihlamur', 'Linden Tea', '', '', '100 TL'],
            ['Papatya', 'Chamomile Tea', '', '', '100 TL'],
            ['Yasemin', 'Jasmine Tea', '', '', '100 TL']
        ]],
        ['Meşrubatlar', 'Soft Drinks', [
            ['Uludağ Premium Su 33 cl', 'Uludağ Premium Still Water 33 cl', '', '', '100 TL'],
            ['Uludağ Premium Su 75 cl', 'Uludağ Premium Still Water 75 cl', '', '', '100 TL'],
            ['Uludağ Premium Maden Suyu 25 cl', 'Uludağ Premium Sparkling Water 25 cl', '', '', '100 TL'],
            ['Uludağ Premium Maden Suyu 75 cl', 'Uludağ Premium Sparkling Water 75 cl', '', '', '100 TL'],
            ['Coca-Cola', 'Coca-Cola', '', '', '100 TL'],
            ['Coca-Cola Zero', 'Coca-Cola Zero', '', '', '100 TL'],
            ['Fanta', 'Fanta', '', '', '100 TL'],
            ['Sprite', 'Sprite', '', '', '100 TL'],
            ['Perrier 33 cl', 'Perrier 33 cl', '', '', '100 TL'],
            ['Perrier 75 cl', 'Perrier 75 cl', '', '', '100 TL'],
            ['San Pellegrino 33 cl', 'San Pellegrino 33 cl', '', '', '100 TL'],
            ['San Pellegrino 75 cl', 'San Pellegrino 75 cl', '', '', '100 TL']
        ]]
    ];

    $order_num_cat = 1;
    foreach ($food_data as $cat_data) {
        $pdo->prepare("INSERT INTO menu_categories (type, name_tr, name_en, order_num) VALUES ('food', ?, ?, ?)")
            ->execute([$cat_data[0], $cat_data[1], $order_num_cat++]);
        $cat_id = $pdo->lastInsertId();

        $order_num_item = 1;
        foreach ($cat_data[2] as $item) {
            $pdo->prepare("INSERT INTO menu_items (category_id, name_tr, name_en, description_tr, description_en, price, order_num) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$cat_id, $item[0], $item[1], $item[2], $item[3], $item[4], $order_num_item++]);
        }
    }

    // --- ALKOL ---
    $alc_data = [
        ['Şampanya', 'Champagne', [
            ['Veuve Clicquot Brut', 'Veuve Clicquot Brut', '', '', '100 TL'],
            ['Dom Pérignon Vintage 2012', 'Dom Pérignon Vintage 2012', '', '', '100 TL']
        ]],
        ['Prosecco', 'Prosecco', [
            ['La Gioiosa Prosecco', 'La Gioiosa Prosecco', '', '', '100 TL'],
            ['Piccini Prosecco', 'Piccini Prosecco', '', '', '100 TL'],
            ['Yaşasın Brut', 'Yaşasın Brut', '', '', '100 TL']
        ]],
        ['İmza Kokteyller', 'Signature Cocktails', [
            ['Sage Affair', 'Sage Affair', 'Gin Mare, adaçayı, lime, mürver çiçeği', 'Gin Mare, sage, lime, elderflower', '100 TL'],
            ['Smoked Negroni', 'Smoked Negroni', 'Cin, Campari, tatlı vermut', 'Gin, Campari, sweet vermouth', '100 TL'],
            ['Fig Old Fashioned', 'Fig Old Fashioned', 'Bourbon, incir kordiyal, portakal bitter', 'Bourbon, fig cordial, orange bitters', '100 TL'],
            ['Rakı Highball', 'Raki Highball', 'Efe Gold, narenciye, maden suyu', 'Efe Gold, citrus, sparkling water', '100 TL'],
            ['Pear & Thyme Martini', 'Pear & Thyme Martini', 'Votka, armut kordiyal, kekik', 'Vodka, pear cordial, thyme', '100 TL'],
            ['After Eight', 'After Eight', 'Espresso, Kahlúa, Baileys, bitter çikolata', 'Espresso, Kahlúa, Baileys, dark cacao', '100 TL'],
            ['Basil Whisper', 'Basil Whisper', 'Votka, fesleğen, yeşil elma, narenciye', 'Vodka, basil, green apple, citrus', '100 TL'],
            ['Midnight Fig', 'Midnight Fig', 'Koyu rom, incir, kakao, kahve bitteri', 'Dark rum, fig, cacao, coffee bitters', '100 TL'],
            ['Citrus Smoke', 'Citrus Smoke', 'Mezcal, mandalina, lime, isli tuz', 'Mezcal, mandarin, lime, smoked salt', '100 TL'],
            ['Golden Hour', 'Golden Hour', 'Viski, kayısı, bal, kekik', 'Whisky, apricot, honey, thyme', '100 TL']
        ]],
        ['Beyaz Şaraplar', 'White Wines', [
            ['Nodus Fumé Blanc — Kadeh', 'Nodus Fumé Blanc — Glass', '', '', '100 TL'],
            ['Cloudy Bay Sauvignon Blanc — Kadeh', 'Cloudy Bay Sauvignon Blanc — Glass', '', '', '100 TL'],
            ['Nodus Fumé Blanc', 'Nodus Fumé Blanc', '', '', '100 TL'],
            ['Sarafin Sauvignon Blanc', 'Sarafin Sauvignon Blanc', '', '', '100 TL'],
            ['Cloudy Bay Sauvignon Blanc', 'Cloudy Bay Sauvignon Blanc', '', '', '100 TL'],
            ['Chablis Jean-Marc Brocard', 'Chablis Jean-Marc Brocard', '', '', '100 TL'],
            ['Château de Tracy Pouilly-Fumé', 'Château de Tracy Pouilly-Fumé', '', '', '100 TL'],
            ['La Moussière Sancerre', 'La Moussière Sancerre', '', '', '100 TL']
        ]],
        ['Rosé Şaraplar', 'Rosé Wines', [
            ['Fleurs de Prairie Rosé — Kadeh', 'Fleurs de Prairie Rosé — Glass', '', '', '100 TL'],
            ['Fleurs de Prairie Rosé', 'Fleurs de Prairie Rosé', '', '', '100 TL'],
            ['Sarafin Rosé', 'Sarafin Rosé', '', '', '100 TL']
        ]],
        ['Kırmızı Şaraplar', 'Red Wines', [
            ['DLC Öküzgözü Boğazkere — Kadeh', 'DLC Öküzgözü Boğazkere — Glass', '', '', '100 TL'],
            ['Valpolicella — Kadeh', 'Valpolicella — Glass', '', '', '100 TL'],
            ['DLC Öküzgözü Boğazkere', 'DLC Öküzgözü Boğazkere', '', '', '100 TL'],
            ['Valpolicella', 'Valpolicella', '', '', '100 TL'],
            ['Pendore Syrah', 'Pendore Syrah', '', '', '100 TL'],
            ['Penfolds Koonunga Hill Shiraz Cabernet', 'Penfolds Koonunga Hill Shiraz Cabernet', '', '', '100 TL'],
            ['Shiraz Grand Reserve', 'Shiraz Grand Reserve', '', '', '100 TL'],
            ['Braccale', 'Braccale', '', '', '100 TL'],
            ['Biondi-Santi', 'Biondi-Santi', '', '', '100 TL']
        ]],
        ['Rakı Seçkisi', 'Raki Selection', [
            ['Beylerbeyi Göbek 5 cl / 35 cl / 70 cl', 'Beylerbeyi Göbek 5 cl / 35 cl / 70 cl', '', '', '100 TL'],
            ['Efe Gold 5 cl / 35 cl / 70 cl', 'Efe Gold 5 cl / 35 cl / 70 cl', '', '', '100 TL'],
            ['Yeni Rakı 5 cl / 35 cl / 70 cl', 'Yeni Rakı 5 cl / 35 cl / 70 cl', '', '', '100 TL']
        ]],
        ['Biralar', 'Beers', [
            ['Carlsberg', 'Carlsberg', '', '', '100 TL'],
            ['Corona', 'Corona', '', '', '100 TL'],
            ['Heineken', 'Heineken', '', '', '100 TL']
        ]],
        ['Cin', 'Gin', [
            ['Hendrick\'s', 'Hendrick\'s', '', '', '100 TL'],
            ['Tanqueray No. Ten', 'Tanqueray No. Ten', '', '', '100 TL'],
            ['Gin Mare', 'Gin Mare', '', '', '100 TL']
        ]],
        ['Votka', 'Vodka', [
            ['Beluga Gold Line', 'Beluga Gold Line', '', '', '100 TL']
        ]],
        ['Tekila', 'Tequila', [
            ['1800 Silver', '1800 Silver', '', '', '100 TL'],
            ['1800 Reposado', '1800 Reposado', '', '', '100 TL'],
            ['1800 Añejo', '1800 Añejo', '', '', '100 TL']
        ]],
        ['Mezcal', 'Mezcal', [
            ['Ilegal Joven', 'Ilegal Joven', '', '', '100 TL']
        ]],
        ['Viski', 'Whiskey', [
            ['Chivas Regal 12', 'Chivas Regal 12', '', '', '100 TL'],
            ['Monkey Shoulder', 'Monkey Shoulder', '', '', '100 TL'],
            ['Lagavulin 16', 'Lagavulin 16', '', '', '100 TL'],
            ['Macallan 15 Double Cask', 'Macallan 15 Double Cask', '', '', '100 TL'],
            ['Hibiki Japanese Harmony', 'Hibiki Japanese Harmony', '', '', '100 TL']
        ]],
        ['Rum', 'Rum', [
            ['Bacardi Carta Blanca', 'Bacardi Carta Blanca', '', '', '100 TL'],
            ['Zacapa 23', 'Zacapa 23', '', '', '100 TL']
        ]],
        ['Konyak', 'Cognac', [
            ['Hennessy VSOP', 'Hennessy VSOP', '', '', '100 TL']
        ]],
        ['Sake', 'Sake', [
            ['Ozeki Sake', 'Ozeki Sake', '', '', '100 TL']
        ]],
        ['Digestif & Amaro', 'Digestif & Amaro', [
            ['Amaro Montenegro', 'Amaro Montenegro', '', '', '100 TL'],
            ['Amaro Nonino', 'Amaro Nonino', '', '', '100 TL'],
            ['Fernet-Branca', 'Fernet-Branca', '', '', '100 TL']
        ]]
    ];

    $order_num_cat = 1;
    foreach ($alc_data as $cat_data) {
        $pdo->prepare("INSERT INTO menu_categories (type, name_tr, name_en, order_num) VALUES ('alcohol', ?, ?, ?)")
            ->execute([$cat_data[0], $cat_data[1], $order_num_cat++]);
        $cat_id = $pdo->lastInsertId();

        $order_num_item = 1;
        foreach ($cat_data[2] as $item) {
            $pdo->prepare("INSERT INTO menu_items (category_id, name_tr, name_en, description_tr, description_en, price, order_num) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$cat_id, $item[0], $item[1], $item[2], $item[3], $item[4], $order_num_item++]);
        }
    }

    echo "Tüm veriler başarıyla eklendi! Toplam " . ($order_num_cat - 1) . " kategori oluşturuldu.";

} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
?>
