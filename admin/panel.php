<?php
/**
 * Sintesi - Admin Yönetim Paneli
 */
require_once __DIR__ . '/../config.php';

// Oturum kontrolü
if (!isset($_SESSION['admin_giris']) || $_SESSION['admin_giris'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sintesi - Yönetim Paneli</title>
    <link rel="icon" type="image/webp" href="../sintesi.webp">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg: #0c0c0c;
            --surface: #151515;
            --surface-2: #1c1c1c;
            --text: #f5f5f5;
            --muted: #a0a0a0;
            --accent: #9D432C;
            --accent-hover: #b85436;
            --success: #22c55e;
            --warning: #eab308;
            --danger: #ef4444;
        }
        
        html, body {
            overflow-x: hidden;
            width: 100%;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* ===== HEADER ===== */
        .admin-header {
            background: var(--surface);
            border-bottom: 1px solid rgba(157,67,44,0.2);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-header .logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem;
            color: var(--accent);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .admin-header .logo img {
            height: 35px;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .admin-user {
            font-size: 0.85rem;
            color: var(--muted);
        }
        .btn-cikis {
            padding: 8px 18px;
            background: transparent;
            border: 1px solid rgba(239,68,68,0.4);
            color: var(--danger);
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.8rem;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-cikis:hover {
            background: var(--danger);
            color: #fff;
        }
        
        /* ===== MAIN ===== */
        .admin-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            overflow-x: hidden;
        }
        
        /* ===== STAT CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 1.8rem;
            transition: transform 0.3s, border-color 0.3s;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: rgba(157,67,44,0.3);
        }
        .stat-card .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 0.8rem;
        }
        .stat-card .stat-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--accent);
        }
        .stat-card .stat-label {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.3rem;
        }
        
        /* ===== FILTERS ===== */
        .filters {
            background: var(--surface);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }
        .filter-group label {
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .filter-group select,
        .filter-group input {
            padding: 10px 14px;
            background: var(--surface-2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.85rem;
            outline: none;
            transition: border-color 0.3s;
        }
        .filter-group select:focus,
        .filter-group input:focus {
            border-color: var(--accent);
        }
        .btn-filtrele {
            padding: 10px 20px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
            transition: background 0.3s;
        }
        .btn-filtrele:hover { background: var(--accent-hover); }
        
        /* ===== TABLE ===== */
        .table-container {
            background: transparent;
            border-radius: 12px;
        }
        .table-header {
            padding: 1.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-header h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 400;
            font-size: 1.8rem;
            color: var(--accent);
        }
        
        .rez-card {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.03);
            border-radius: 12px;
            margin-bottom: 1rem;
            padding: 1.2rem;
            display: grid;
            grid-template-columns: 2fr 1.5fr 1.5fr 1fr 1fr 1.5fr;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }
        .rez-card:hover {
            border-color: rgba(157,67,44,0.3);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transform: translateX(5px);
        }
        
        .rez-name { font-weight: 600; font-size: 1rem; }
        .rez-info { display: flex; flex-direction: column; gap: 2px; }
        .rez-label { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .rez-value { font-size: 0.9rem; color: var(--text); }
        
        .rez-special {
            grid-column: 1 / -1;
            background: rgba(157,67,44,0.05);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--muted);
            border-left: 3px solid var(--accent);
            margin-top: 5px;
        }

        /* Durum badge'leri */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-beklemede { background: rgba(234,179,8,0.1); color: var(--warning); border: 1px solid rgba(234,179,8,0.2); }
        .badge-onaylandi { background: rgba(34,197,94,0.1); color: var(--success); border: 1px solid rgba(34,197,94,0.2); }
        .badge-iptal { background: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.2); }
        
        /* Aksiyon butonları */
        .actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .btn-action {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--surface-2);
            color: var(--text);
            font-size: 1.1rem;
        }
        .btn-approve:hover { background: var(--success); color: #fff; border-color: var(--success); transform: translateY(-3px); }
        .btn-reject:hover { background: var(--warning); color: #000; border-color: var(--warning); transform: translateY(-3px); }
        .btn-delete:hover { background: var(--danger); color: #fff; border-color: var(--danger); transform: translateY(-3px); }
        
        /* Yenile Butonu Modern */
        .btn-refresh {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--muted);
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .btn-refresh:hover {
            background: rgba(157,67,44,0.1);
            border-color: var(--accent);
            color: var(--accent);
        }
        .btn-refresh i, .btn-refresh span.icon {
            display: inline-block;
            transition: transform 0.5s ease;
        }
        .btn-refresh.loading span.icon {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Boş durum */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--muted);
        }
        .empty-state .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 3rem;
            color: var(--muted);
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 0.9rem;
            z-index: 9999;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .toast-success {
            background: rgba(34,197,94,0.15);
            border: 1px solid rgba(34,197,94,0.4);
            color: var(--success);
        }
        .toast-error {
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(239,68,68,0.4);
            color: var(--danger);
        }

        /* ===== MODAL ===== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.85);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background: var(--surface);
            padding: 2.5rem;
            border-radius: 15px;
            max-width: 450px;
            width: 90%;
            border: 1px solid rgba(157,67,44,0.3);
            text-align: center;
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: var(--accent);
        }
        .modal-text {
            color: var(--muted);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        .btn-modal {
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
            transition: 0.3s;
            border: none;
        }
        .btn-cancel {
            background: var(--surface-2);
            color: var(--text);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .btn-confirm-delete {
            background: var(--danger);
            color: #fff;
        }
        .btn-modal:hover { opacity: 0.9; }
        
        .search-bar {
            padding: 10px 14px;
            background: var(--surface-2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: var(--text);
            font-family: inherit;
            font-size: 0.85rem;
            outline: none;
            width: 250px;
            box-sizing: border-box;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .page-btn {
            padding: 8px 12px;
            background: var(--surface-2);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text);
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }
        .page-btn:hover { background: var(--accent); border-color: var(--accent); }
        .page-btn.active { background: var(--accent); border-color: var(--accent); font-weight: bold; }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .top-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: var(--accent);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
            transition: 0.3s;
            white-space: nowrap;
        }
        .btn-primary:hover { background: var(--accent-hover); }
        
        /* Modal Form Styles */
        .modal-form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .modal-form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--muted);
            font-size: 0.85rem;
        }
        .modal-form-group input, .modal-form-group select, .modal-form-group textarea {
            width: 100%;
            padding: 10px;
            background: var(--surface-2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: var(--text);
            font-family: inherit;
            box-sizing: border-box;
        }

        /* Flatpickr Dark Theme Customization */
        .flatpickr-calendar {
            background: #151515 !important;
            border: 1px solid rgba(157,67,44,0.3) !important;
        }
        .flatpickr-day.selected {
            background: var(--accent) !important;
            border-color: var(--accent) !important;
        }
        .flatpickr-time {
            background: #151515 !important;
            border-top: 1px solid rgba(157,67,44,0.2) !important;
        }
        .flatpickr-time .numInputWrapper:hover {
            background: rgba(255,255,255,0.05) !important;
        }
        .flatpickr-time input {
            color: var(--text) !important;
        }
        .flatpickr-time .flatpickr-am-pm {
            color: var(--text) !important;
        }
        .flatpickr-time .flatpickr-time-separator {
            color: var(--muted) !important;
        }


        
        /* Responsive */
        @media (max-width: 1024px) {
            .rez-card {
                grid-template-columns: 1fr 1fr 1fr;
                gap: 1.5rem;
            }
            .rez-special { grid-column: 1 / -1; }
            .actions { grid-column: 1 / -1; }
        }
        @media (max-width: 768px) {
            .admin-header { padding: 0.8rem 1rem; }
            .admin-main { padding: 1rem; }
            .filters { 
                flex-direction: column; 
                align-items: stretch;
            }
            .filter-group { width: 100%; }
            .filter-group input, 
            .filter-group select,
            .search-bar,
            .flatpickr-input,
            .flatpickr-mobile {
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .btn-filtrele { width: 100%; margin-top: 0.5rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .rez-card { grid-template-columns: 1fr 1fr; }
            
            /* Table Header Mobile */
            .table-header {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            .table-header h2 {
                font-size: 1.4rem;
            }
            .top-actions {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 8px;
                width: 100%;
            }
            .top-actions .btn-primary,
            .top-actions .btn-refresh {
                width: 100%;
                text-align: center;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
                font-size: 0.8rem;
                padding: 10px 5px;
                box-sizing: border-box;
                white-space: nowrap;
            }

            /* Modal Mobile */
            .modal-content {
                padding: 1.5rem;
                width: 95%;
            }
            .modal-content div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }
            .modal-actions {
                flex-direction: column;
            }
            .modal-actions .btn-modal {
                width: 100%;
            }
            
            /* Navbar Sadeleştirme */
            .logo-text, .btn-text, .admin-user-name { display: none; }
            .btn-cikis { 
                padding: 8px; 
                width: 40px; 
                height: 40px; 
                display: flex; 
                align-items: center; 
                justify-content: center;
                font-size: 1.2rem;
            }
            .admin-user { font-size: 1.2rem; margin-right: 0.5rem; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .rez-card { grid-template-columns: 1fr; }
            .top-actions {
                grid-template-columns: 1fr 1fr 1fr;
            }
            .btn-text { display: none; }
            .top-actions .btn-primary, .top-actions .btn-refresh {
                font-size: 1.2rem;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <a href="panel.php" class="logo">
            <img src="../sintesi.webp" alt="Sintesi">
            <span class="logo-text">Sintesi Panel</span>
        </a>
        <div class="header-right">
            <span class="admin-user" title="<?= htmlspecialchars($_SESSION['admin_kullanici']) ?>">👤 <span class="admin-user-name"><?= htmlspecialchars($_SESSION['admin_kullanici']) ?></span></span>
            <a href="../" class="btn-cikis" title="Siteye Git" style="color: var(--muted); border-color: rgba(255,255,255,0.2);">🌐 <span class="btn-text">Siteye Git</span></a>
            <a href="logout.php" class="btn-cikis" title="Çıkış Yap">🚪 <span class="btn-text">Çıkış Yap</span></a>
        </div>
    </header>

    <main class="admin-main">
        <!-- İstatistik Kartları -->
        <div class="stats-grid">
            <div class="stat-card" onclick="hizliFiltre('bugun')">
                <div class="stat-icon">📅</div>
                <div class="stat-value" id="stat-bugun">-</div>
                <div class="stat-label">Bugünkü Rezervasyon</div>
            </div>
            <div class="stat-card" onclick="hizliFiltre('hafta')">
                <div class="stat-icon">📆</div>
                <div class="stat-value" id="stat-hafta">-</div>
                <div class="stat-label">Bu Hafta</div>
            </div>
            <div class="stat-card" onclick="hizliFiltre('bekleyen')">
                <div class="stat-icon">⏳</div>
                <div class="stat-value" id="stat-bekleyen">-</div>
                <div class="stat-label">Bekleyen Onay</div>
            </div>
            <div class="stat-card" onclick="hizliFiltre('toplam')">
                <div class="stat-icon">📊</div>
                <div class="stat-value" id="stat-toplam">-</div>
                <div class="stat-label">Toplam Kayıt</div>
            </div>
        </div>

        <!-- Filtreler -->
        <div class="filters">
            <div class="filter-group">
                <label>Arama</label>
                <input type="text" id="filtre-arama" class="search-bar" placeholder="Ad Soyad veya Tel...">
            </div>
            <div class="filter-group">
                <label>Durum</label>
                <select id="filtre-durum">
                    <option value="">Tümü</option>
                    <option value="beklemede">⏳ Beklemede</option>
                    <option value="onaylandi">✅ Onaylandı</option>
                    <option value="iptal">❌ İptal</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Başlangıç Tarihi</label>
                <input type="text" id="filtre-tarih-bas" placeholder="Tarih Seçin" readonly>
            </div>
            <div class="filter-group">
                <label>Bitiş Tarihi</label>
                <input type="text" id="filtre-tarih-son" placeholder="Tarih Seçin" readonly>
            </div>
            <button class="btn-filtrele" onclick="yukleRezervasyonlar()">Filtrele</button>
        </div>
        <!-- Rezervasyonlar Tablosu -->
        <h2>🍽️ Rezervasyonlar</h2>
        <div class="table-container">
             
            <div class="table-header">
             
                <div class="top-actions">
                    <button class="btn-primary" onclick="acModal('manuelEkleModal')"><span>+</span> <span class="btn-text">Yeni Ekle</span></button>
                    <button class="btn-primary" onclick="ayarlariAc()" style="background:var(--surface-2);border:1px solid rgba(255,255,255,0.1);"><span>⚙️</span> <span class="btn-text">Ayarlar</span></button>
                    <button class="btn-refresh" onclick="yukleHerSeyi(this)">
                        <span class="icon">🔄</span>
                        <span class="btn-text">Yenile</span>
                    </button>
                </div>
            </div>
            <div id="tablo-icerik">
                <div class="loading">Yükleniyor...</div>
            </div>
            <div id="sayfalama" class="pagination"></div>
        </div>
    </main>

    <!-- Toast Mesajı -->
    <div id="toast" class="toast"></div>

    <!-- Silme Onay Modalı -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content">
            <h3 class="modal-title">Emin misiniz?</h3>
            <p class="modal-text">Bu rezervasyon kaydı kalıcı olarak silinecektir. Bu işlemi geri alamazsınız.</p>
            <div class="modal-actions">
                <button class="btn-modal btn-cancel" onclick="kapatModal('deleteModal')">Vazgeç</button>
                <button class="btn-modal btn-confirm-delete" id="confirmDeleteBtn">Evet, Sil</button>
            </div>
        </div>
    </div>

    <!-- Manuel Ekle Modal -->
    <div id="manuelEkleModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <h3 class="modal-title">Yeni Rezervasyon Ekle</h3>
            <form id="manuelEkleForm" onsubmit="manuelEkleKaydet(event)">
                <div class="modal-form-group">
                    <label>Ad Soyad</label>
                    <input type="text" id="m-ad" required>
                </div>
                <div class="modal-form-group">
                    <label>Telefon</label>
                    <input type="text" id="m-tel" required>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="modal-form-group">
                        <label>Tarih</label>
                        <input type="text" id="m-tarih" placeholder="Tarih Seçin" required readonly>
                    </div>
                    <div class="modal-form-group">
                        <label>Saat</label>
                        <input type="text" id="m-saat" placeholder="Saat Seçin" required readonly>
                    </div>
                </div>
                <div class="modal-form-group">
                    <label>Kişi Sayısı</label>
                    <input type="number" id="m-kisi" value="2" min="1" max="50" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="kapatModal('manuelEkleModal')">Vazgeç</button>
                    <button type="submit" class="btn-modal btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ayarlar Modal -->
    <div id="ayarlarModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px;">
            <h3 class="modal-title">Sistem Ayarları</h3>
            <form id="ayarlarForm" onsubmit="ayarlariKaydetSubmit(event)">
                <div class="modal-form-group">
                    <label>Saatlik Maksimum Kapasite</label>
                    <input type="number" id="ayar-kapasite" min="1" required>
                </div>
                <div class="modal-form-group">
                    <label>Kapalı / Özel Günler</label>
                    <input type="text" id="ayar-kapali-gunler" placeholder="Tarihleri seçin" readonly>
                    <small style="color:var(--muted); font-size:0.75rem;">Bu tarihlerde rezervasyon alınamaz.</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="kapatModal('ayarlarModal')">İptal</button>
                    <button type="submit" class="btn-modal btn-primary">Ayarları Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>";

        // Sayfa yüklendiğinde verileri çek
        document.addEventListener('DOMContentLoaded', () => {
            yukleIstatistikler();
            yukleRezervasyonlar();
            initFlatpickr();
        });

        let currentPage = 1;
        let currentDeleteId = null;

        let fpBas, fpSon, fpAyarlar;

        function initFlatpickr() {
            const config = {
                locale: "tr",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d F Y",
            };
            fpBas = flatpickr("#filtre-tarih-bas", config);
            fpSon = flatpickr("#filtre-tarih-son", config);
            
            fpAyarlar = flatpickr("#ayar-kapali-gunler", {
                locale: "tr",
                mode: "multiple",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d F Y"
            });
            
            flatpickr("#m-tarih", {
                locale: "tr",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d F Y"
            });

            flatpickr("#m-saat", {
                locale: "tr",
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true,
                altInput: true,
                altFormat: "H:i",
                placeholder: "Saat Seçin"
            });
            
            // Arama inputu listener'ı
            document.getElementById('filtre-arama').addEventListener('input', (e) => {
                currentPage = 1; // Yeni aramada ilk sayfaya dön
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(yukleRezervasyonlar, 500);
            });
        }

        // Modal Kontrolleri
        function acModal(id) { document.getElementById(id).style.display = 'flex'; }
        function kapatModal(id) { document.getElementById(id).style.display = 'none'; }

        /**
         * Hızlı Filtre (Stat kartlarına tıklanınca)
         */
        function hizliFiltre(tip) {
            const durumSelect = document.getElementById('filtre-durum');
            
            // Önce her şeyi sıfırla
            durumSelect.value = '';
            fpBas.clear();
            fpSon.clear();
            
            const bugun = new Date();
            
            if (tip === 'bugun') {
                fpBas.setDate(bugun);
                fpSon.setDate(bugun);
            } else if (tip === 'hafta') {
                const pazartesi = new Date(bugun);
                const day = pazartesi.getDay() || 7; // Pazar 0'dır, onu 7 yapıyoruz
                pazartesi.setDate(pazartesi.getDate() - day + 1);
                
                const pazar = new Date(pazartesi);
                pazar.setDate(pazartesi.getDate() + 6);
                
                fpBas.setDate(pazartesi);
                fpSon.setDate(pazar);
            } else if (tip === 'bekleyen') {
                durumSelect.value = 'beklemede';
            }
            
            // Filtreyi uygula
            yukleRezervasyonlar();
            
            // Tablo kısmına hafifçe scroll ol
            document.querySelector('.table-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        /**
         * İstatistikleri yükle
         */
        async function yukleIstatistikler() {
            try {
                const res = await fetch('api.php?action=stats');
                const json = await res.json();
                if (json.success) {
                    document.getElementById('stat-bugun').textContent = json.data.bugunki;
                    document.getElementById('stat-hafta').textContent = json.data.haftalik;
                    document.getElementById('stat-bekleyen').textContent = json.data.bekleyen;
                    document.getElementById('stat-toplam').textContent = json.data.toplam;
                }
            } catch (e) {
                console.error('İstatistik hatası:', e);
            }
        }

        /**
         * Her şeyi yenile (Buton animasyonuyla)
         */
        async function yukleHerSeyi(btn) {
            if (btn) btn.classList.add('loading');
            await Promise.all([yukleIstatistikler(), yukleRezervasyonlar()]);
            if (btn) {
                setTimeout(() => {
                    btn.classList.remove('loading');
                }, 500);
            }
        }

        /**
         * Rezervasyonları yükle
         */
        async function yukleRezervasyonlar(page = currentPage) {
            currentPage = page;
            const durum = document.getElementById('filtre-durum').value;
            const tarihBas = document.getElementById('filtre-tarih-bas').value;
            const tarihSon = document.getElementById('filtre-tarih-son').value;
            const arama = document.getElementById('filtre-arama').value;

            let url = `api.php?action=list&page=${page}`;
            if (durum) url += '&durum=' + durum;
            if (tarihBas) url += '&tarih_bas=' + tarihBas;
            if (tarihSon) url += '&tarih_son=' + tarihSon;
            if (arama) url += '&search=' + encodeURIComponent(arama);

            const container = document.getElementById('tablo-icerik');
            const paginationContainer = document.getElementById('sayfalama');
            container.innerHTML = '<div class="loading">Yükleniyor...</div>';
            paginationContainer.innerHTML = '';

            try {
                const res = await fetch(url);
                const json = await res.json();

                if (json.success && json.data.length > 0) {
                    let html = '';

                    json.data.forEach(r => {
                        const tarihStr = formatTarih(r.tarih);
                        const durumBadge = getDurumBadge(r.durum);
                        
                        html += `
                        <div class="rez-card">
                            <div class="rez-info">
                                <span class="rez-name">${r.ad_soyad}</span>
                                <span style="color:var(--muted);font-size:0.8rem;">${r.email || 'E-posta yok'}</span>
                            </div>
                            <div class="rez-info">
                                <span class="rez-label">Telefon</span>
                                <span class="rez-value">${r.telefon}</span>
                            </div>
                            <div class="rez-info">
                                <span class="rez-label">Tarih</span>
                                <span class="rez-value" style="font-weight:500;">${tarihStr}</span>
                            </div>
                            <div class="rez-info">
                                <span class="rez-label">Saat</span>
                                <span class="rez-value">${r.saat}</span>
                            </div>
                            <div class="rez-info">
                                <span class="rez-label">Kişi</span>
                                <span class="rez-value">${r.kisi_sayisi} Kişi</span>
                            </div>
                            <div class="rez-info">
                                <div class="actions">
                                    ${r.durum !== 'onaylandi' ? `<button class="btn-action btn-approve" onclick="islem('approve',${r.id})" title="Onayla">✓</button>` : ''}
                                    ${r.durum !== 'iptal' ? `<button class="btn-action btn-reject" onclick="islem('reject',${r.id})" title="İptal Et">✕</button>` : ''}
                                    <button class="btn-action btn-delete" onclick="silOnay(${r.id})" title="Sil">🗑</button>
                                </div>
                                <div style="margin-top:8px; text-align:right;">${durumBadge}</div>
                            </div>
                            ${r.ozel_istekler ? `
                            <div class="rez-special">
                                <strong>Not:</strong> ${r.ozel_istekler}
                            </div>` : ''}
                        </div>`;
                    });

                    container.innerHTML = html;

                    // Sayfalama oluştur
                    if (json.pagination && json.pagination.total_pages > 1) {
                        let pageHtml = '';
                        const tp = json.pagination.total_pages;
                        const cp = json.pagination.current_page;
                        
                        pageHtml += `<button class="page-btn" ${cp === 1 ? 'disabled' : `onclick="yukleRezervasyonlar(${cp - 1})"`}>Önceki</button>`;
                        
                        for (let i = 1; i <= tp; i++) {
                            if (i === 1 || i === tp || (i >= cp - 2 && i <= cp + 2)) {
                                pageHtml += `<button class="page-btn ${i === cp ? 'active' : ''}" onclick="yukleRezervasyonlar(${i})">${i}</button>`;
                            } else if (i === cp - 3 || i === cp + 3) {
                                pageHtml += `<span style="color:var(--muted); margin:0 5px;">...</span>`;
                            }
                        }
                        
                        pageHtml += `<button class="page-btn" ${cp === tp ? 'disabled' : `onclick="yukleRezervasyonlar(${cp + 1})"`}>Sonraki</button>`;
                        paginationContainer.innerHTML = pageHtml;
                    }
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="icon">🍽️</div>
                            <p>Henüz rezervasyon bulunmuyor.</p>
                        </div>`;
                }
            } catch (e) {
                container.innerHTML = '<div class="empty-state"><p>Veriler yüklenirken hata oluştu.</p></div>';
                console.error('Liste hatası:', e);
            }
        }

        /**
         * Rezervasyon işlemi (onayla / iptal)
         */
        async function islem(action, id) {
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('id', id);
                formData.append('csrf_token', csrfToken);

                const res = await fetch('api.php?action=' + action, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (json.success) {
                    toastGoster(json.message, 'success');
                    yukleRezervasyonlar();
                    yukleIstatistikler();
                } else {
                    toastGoster(json.message, 'error');
                }
            } catch (e) {
                toastGoster('İşlem sırasında hata oluştu.', 'error');
            }
        }

        /**
         * Silme onayı (Modal aç)
         */
        function silOnay(id) {
            currentDeleteId = id;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function modalKapat() {
            document.getElementById('deleteModal').style.display = 'none';
            currentDeleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            if (currentDeleteId) {
                islem('delete', currentDeleteId);
                modalKapat();
            }
        });

        /**
         * Durum badge'i oluştur
         */
        function getDurumBadge(durum) {
            const map = {
                'beklemede': '<span class="badge badge-beklemede">⏳ Beklemede</span>',
                'onaylandi': '<span class="badge badge-onaylandi">✅ Onaylandı</span>',
                'iptal': '<span class="badge badge-iptal">❌ İptal</span>'
            };
            return map[durum] || durum;
        }

        /**
         * Tarih formatla
         */
        function formatTarih(tarihStr) {
            const date = new Date(tarihStr);
            return date.toLocaleDateString('tr-TR', { day: '2-digit', month: 'long', year: 'numeric' });
        }

        /**
         * Manuel Rezervasyon Ekleme
         */
        async function manuelEkleKaydet(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = "Kaydediliyor...";
            
            try {
                const formData = new FormData();
                formData.append('action', 'create');
                formData.append('ad_soyad', document.getElementById('m-ad').value);
                formData.append('telefon', document.getElementById('m-tel').value);
                formData.append('tarih', document.getElementById('m-tarih').value);
                formData.append('saat', document.getElementById('m-saat').value);
                formData.append('kisi_sayisi', document.getElementById('m-kisi').value);
                formData.append('csrf_token', csrfToken);
                
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    toastGoster(json.message, 'success');
                    kapatModal('manuelEkleModal');
                    e.target.reset();
                    yukleHerSeyi();
                } else {
                    toastGoster(json.message, 'error');
                }
            } catch(err) {
                toastGoster('Bağlantı hatası.', 'error');
            }
            btn.disabled = false;
            btn.textContent = "Kaydet";
        }

        /**
         * Ayarlar Modalını Aç ve Verileri Çek
         */
        async function ayarlariAc() {
            acModal('ayarlarModal');
            document.getElementById('ayar-kapasite').value = '';
            fpAyarlar.clear();
            
            try {
                const res = await fetch('api.php?action=settings_get');
                const json = await res.json();
                if (json.success) {
                    document.getElementById('ayar-kapasite').value = json.data.kapasite;
                    fpAyarlar.setDate(json.data.kapali_gunler);
                }
            } catch(err) {
                toastGoster('Ayarlar yüklenemedi', 'error');
            }
        }

        /**
         * Ayarları Kaydet
         */
        async function ayarlariKaydetSubmit(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = "Kaydediliyor...";
            
            try {
                const formData = new FormData();
                formData.append('action', 'settings_save');
                formData.append('kapasite', document.getElementById('ayar-kapasite').value);
                
                const seciliGunler = fpAyarlar.selectedDates.map(date => {
                    return date.getFullYear() + "-" + 
                           String(date.getMonth() + 1).padStart(2, '0') + "-" + 
                           String(date.getDate()).padStart(2, '0');
                });
                formData.append('kapali_gunler', JSON.stringify(seciliGunler));
                formData.append('csrf_token', csrfToken);
                
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    toastGoster(json.message, 'success');
                    kapatModal('ayarlarModal');
                } else {
                    toastGoster(json.message, 'error');
                }
            } catch(err) {
                toastGoster('Bağlantı hatası.', 'error');
            }
            btn.disabled = false;
            btn.textContent = "Ayarları Kaydet";
        }

        /**
         * Toast mesajı göster
         */
        function toastGoster(mesaj, tip) {
            const toast = document.getElementById('toast');
            toast.textContent = mesaj;
            toast.className = `toast toast-${tip} show`;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>
