<?php
/**
 * Sintesi - Admin Giriş Sayfası
 */
require_once __DIR__ . '/../config.php';

// Zaten giriş yapmışsa panele yönlendir
if (isset($_SESSION['admin_giris']) && $_SESSION['admin_giris'] === true) {
    header('Location: panel.php');
    exit;
}

$hata = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullanici = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    
    if (!empty($kullanici) && !empty($sifre)) {
        try {
            $pdo = veritabani_baglantisi();
            $stmt = $pdo->prepare("SELECT * FROM admin_kullanicilar WHERE kullanici_adi = ?");
            $stmt->execute([$kullanici]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($sifre, $admin['sifre_hash'])) {
                $_SESSION['admin_giris'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_kullanici'] = $admin['kullanici_adi'];
                header('Location: panel.php');
                exit;
            } else {
                $hata = 'Kullanıcı adı veya şifre hatalı.';
            }
        } catch (Exception $e) {
            $hata = 'Veritabanı bağlantı hatası. Lütfen kurulum.php dosyasını çalıştırın.';
        }
    } else {
        $hata = 'Lütfen tüm alanları doldurun.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sintesi - Admin Giriş</title>
    <link rel="icon" type="image/webp" href="../sintesi.webp">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
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
        .login-container {
            background: #151515;
            border: 1px solid rgba(157, 67, 44, 0.2);
            border-radius: 15px;
            padding: 3rem;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .login-header img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 1rem;
        }
        .login-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2rem;
            font-weight: 400;
            color: #9D432C;
            letter-spacing: 3px;
        }
        .login-header p {
            color: #a0a0a0;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #a0a0a0;
            margin-bottom: 0.5rem;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #f5f5f5;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.3s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #9D432C;
        }
        .btn-giris {
            width: 100%;
            padding: 14px;
            background: #9D432C;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: background 0.3s, transform 0.2s;
        }
        .btn-giris:hover {
            background: #b85436;
            transform: translateY(-1px);
        }
        .hata-mesaji {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .geri-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: #a0a0a0;
            text-decoration: none;
            font-size: 0.8rem;
            transition: color 0.3s;
        }
        .geri-link:hover { color: #9D432C; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../sintesi.webp" alt="Sintesi Logo">
            <h1>Sintesi</h1>
            <p>Yönetim Paneli Girişi</p>
        </div>
        
        <?php if ($hata): ?>
            <div class="hata-mesaji"><?= htmlspecialchars($hata) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi" required autocomplete="username" placeholder="Kullanıcı adınızı girin">
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="sifre" required autocomplete="current-password" placeholder="Şifrenizi girin">
            </div>
            <button type="submit" class="btn-giris">Giriş Yap</button>
        </form>
        
        <a href="../" class="geri-link">← Ana Sayfaya Dön</a>
    </div>
</body>
</html>
