<?php
/**
 * Sintesi - Müşteri Rezervasyon İptal Sayfası
 * Müşterinin e-postasına giden iptal linki ile çalışır.
 */

require_once __DIR__ . '/config.php';

$kod = $_GET['kod'] ?? $_POST['kod'] ?? '';
$mesaj = '';
$hata = false;
$onay_gerekli = false;

if (empty($kod)) {
    $hata = true;
    $mesaj = 'Geçersiz iptal bağlantısı.';
} else {
    try {
        $pdo = veritabani_baglantisi();
        
        // Kodu kontrol et
        $stmt = $pdo->prepare("SELECT id, ad_soyad, telefon, email, tarih, saat, durum FROM rezervasyonlar WHERE iptal_kodu = ? LIMIT 1");
        $stmt->execute([$kod]);
        $rezervasyon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rezervasyon) {
            $hata = true;
            $mesaj = 'Bu iptal bağlantısı geçersiz veya daha önce kullanılmış.';
        } else if ($rezervasyon['durum'] === 'iptal') {
            $hata = true;
            $mesaj = 'Rezervasyonunuz zaten iptal edilmiş.';
        } else {
            // Eğer POST isteği gelmişse iptal et (Botların/Antivirüslerin otomatik tıklamasını engellemek için)
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['onay'])) {
                // İptal işlemini yap
                $stmt = $pdo->prepare("UPDATE rezervasyonlar SET durum = 'iptal' WHERE id = ?");
                $stmt->execute([$rezervasyon['id']]);
                
                // Yöneticilere bildirim gönder
                if (!local_mi()) {
                    gonderIptalBildirimi($rezervasyon);
                }
                
                $mesaj = "Sayın {$rezervasyon['ad_soyad']}, " . date('d.m.Y', strtotime($rezervasyon['tarih'])) . " saat {$rezervasyon['saat']} için yaptığınız rezervasyonunuz başarıyla iptal edilmiştir.";
            } else {
                // Henüz iptal edilmedi, onay formu göster
                $onay_gerekli = true;
                $mesaj = "Sayın <strong>{$rezervasyon['ad_soyad']}</strong>, <br>" . date('d.m.Y', strtotime($rezervasyon['tarih'])) . " saat <strong>{$rezervasyon['saat']}</strong> için olan rezervasyonunuzu iptal etmek istediğinizden emin misiniz?";
            }
        }
    } catch (Exception $e) {
        $hata = true;
        $mesaj = 'Sistemde bir hata oluştu, lütfen daha sonra tekrar deneyin.';
    }
}

/**
 * Yöneticilere iptal bildirimi gönderir
 */
function gonderIptalBildirimi($rez) {
    require_once __DIR__ . '/phpmailer/Exception.php';
    require_once __DIR__ . '/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/phpmailer/SMTP.php';
    
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
        
        $mail->setFrom('info@sintesi.com.tr', 'Sintesi İptal Bildirimi');
        $mail->addAddress('info@sintesi.com.tr');
        $mail->addAddress('cagla@sintesi.com.tr');
        $mail->addAddress('bugra@sintesi.com.tr');
        $mail->addAddress('ersinavsar@sintesi.com.tr');
        
        $tarih_format = date('d.m.Y', strtotime($rez['tarih']));
        
        $mail->isHTML(true);
        $mail->Subject = "❌ Rezervasyon İptal Edildi - {$rez['ad_soyad']} ({$tarih_format})";
        $mail->Body = "
            <div style=\"font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border-radius: 15px; overflow: hidden; background: #000 url('https://sintesi.com.tr/background.png') no-repeat center center; background-size: cover; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);\">
                <div style='padding: 40px 20px; text-align: center;'>
                    <img src='https://sintesi.com.tr/sintesi.webp' alt='Sintesi' style='max-width: 180px; margin-bottom: 20px;'>
                    <h1 style='margin: 0; font-size: 26px; font-family: Georgia, serif; color: #fff;'>Rezervasyon İptal Edildi</h1>
                    <div style='width: 50px; height: 2px; background: #9D432C; margin: 20px auto;'></div>
                </div>
                <div style='padding: 0 40px 40px 40px;'>
                    <p style='font-size: 16px; line-height: 1.6; color: #e0e0e0;'>Aşağıdaki rezervasyon müşteri tarafından <strong>iptal edilmiştir</strong>:</p>
                    
                    <div style='background: rgba(255,255,255,0.05); padding: 25px; border-radius: 12px; margin: 30px 0; border: 1px solid rgba(255,255,255,0.1);'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; color: #888; font-size: 14px;'>Müşteri</td>
                                <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$rez['ad_soyad']}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #888; font-size: 14px;'>Telefon</td>
                                <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$rez['telefon']}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #888; font-size: 14px;'>E-posta</td>
                                <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>" . ($rez['email'] ?: 'Belirtilmedi') . "</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #888; font-size: 14px;'>Tarih</td>
                                <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$tarih_format}</strong></td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #888; font-size: 14px;'>Saat</td>
                                <td style='padding: 8px 0; color: #fff; font-size: 16px; text-align: right;'><strong>{$rez['saat']}</strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <p style='font-size: 13px; color: #666; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;'>
                        Bu işlem müşteri tarafından e-posta yoluyla yapılmıştır.
                    </p>
                </div>
                <div style='background: rgba(0,0,0,0.4); padding: 30px; text-align: center;'>
                    <p style='margin: 0; color: #888; font-size: 13px;'>Sintesi Yönetim Paneli Bildirim Sistemi</p>
                </div>
            </div>
        ";
        
        $mail->send();
    } catch (Exception $e) {
        error_log('İptal bildirim maili hatası: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sintesi - Rezervasyon İptali</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: #0c0c0c;
            color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: #151515;
            padding: 3rem;
            border-radius: 12px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            border: 1px solid rgba(157,67,44,0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.5rem;
            color: #9D432C;
            margin-bottom: 2rem;
            display: block;
            text-decoration: none;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .message {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            background: #9D432C;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #b85436;
        }
        .error-text { color: #ef4444; }
        .success-text { color: #22c55e; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.html" class="logo">Sintesi</a>
        
        <?php if ($hata): ?>
            <div class="icon">⚠️</div>
            <p class="message error-text"><?= $mesaj ?></p>
        <?php elseif ($onay_gerekli): ?>
            <div class="icon">❓</div>
            <p class="message"><?= $mesaj ?></p>
            <form method="POST" style="margin-bottom: 2rem;">
                <input type="hidden" name="kod" value="<?= htmlspecialchars($kod) ?>">
                <button type="submit" name="onay" class="btn" style="background: #ef4444;">Rezervasyonu İptal Et</button>
            </form>
        <?php else: ?>
            <div class="icon">✅</div>
            <p class="message success-text"><?= $mesaj ?></p>
        <?php endif; ?>
        
        <a href="index.html" class="btn">Ana Sayfaya Dön</a>
    </div>
</body>
</html>
