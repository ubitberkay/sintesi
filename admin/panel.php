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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* ===== CHARTS ===== */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .chart-card {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .chart-title {
            font-size: 0.9rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
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
        
        .badge-visitor {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: rgba(217, 119, 6, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(217, 119, 6, 0.3);
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 5px;
            width: fit-content;
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
            flex-wrap: wrap;
        }
        .btn-action {
            min-height: 38px;
            padding: 0 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: var(--surface-2);
            color: var(--text);
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
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
            align-items: flex-start;
            z-index: 2000;
            backdrop-filter: blur(5px);
            overflow-y: auto;
            padding: 40px 0;
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
            margin-bottom: 40px;
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
        .btn-status-geldi { background: #22c55e !important; color: white !important; }
        .btn-status-gelmedi { background: #f59e0b !important; color: white !important; }
        .btn-approve { background: #3b82f6 !important; color: white !important; }
        .btn-reject { background: #ef4444 !important; color: white !important; }
        
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

        /* Mail Modalı Özel Stiller */
        .email-list-container {
            max-height: 200px;
            overflow-y: auto;
            background: var(--surface-2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 15px;
        }
        .email-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            cursor: pointer;
        }
        .email-item:last-child { border-bottom: none; }
        .email-item:hover { background: rgba(157,67,44,0.1); }
        .email-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
        }
        .email-item label {
            cursor: pointer;
            font-size: 0.9rem;
            flex: 1;
        }
        .email-search {
            width: 100%;
            padding: 10px;
            background: var(--surface-2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text);
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        .select-all-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        /* ============================================================ */
        /* RESPONSIVE DESIGN (MOBİL UYUMLULUK) */
        /* ============================================================ */
        
        @media (max-width: 1024px) {
            .rez-card {
                grid-template-columns: 1.5fr 1fr 1fr;
                gap: 1.2rem;
            }
            .rez-special { grid-column: span 3; }
            .actions { grid-column: span 3; }
        }

        @media (max-width: 768px) {
            .admin-header { padding: 0.8rem 1.2rem; }
            .admin-user-name { display: none; }
            .admin-main { padding: 1.2rem; }
            
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 1rem; }
            .stat-card { padding: 1.2rem; }
            .stat-value { font-size: 1.8rem; }
            
            .charts-grid { grid-template-columns: 1fr; }
            
            .filters { flex-direction: column; align-items: stretch; gap: 1rem; padding: 1.2rem; }
            .filter-group { width: 100%; }
            .btn-filtrele { width: 100%; padding: 12px; font-weight: 600; }
            
            .top-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.8rem;
                width: 100%;
            }
            .top-actions .btn-primary, .top-actions .btn-refresh {
                width: 100%;
                justify-content: center;
                padding: 10px;
            }
            
            .rez-card {
                grid-template-columns: 1fr 1fr;
                padding: 1rem;
            }
            .rez-special { grid-column: span 2; }
            .actions { grid-column: span 2; }
        }

        @media (max-width: 480px) {
            .logo-text { display: none; }
            .header-right { gap: 0.8rem; }
            
            .stats-grid { grid-template-columns: 1fr; }
            
            .rez-card {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }
            .rez-special { grid-column: span 1; }
            .actions { 
                grid-column: span 1; 
                flex-direction: column;
                align-items: stretch;
                width: 100%;
                gap: 6px;
            }
            .btn-action { width: 100%; }
            
            .modal-content { 
                width: 95%; 
                padding: 1.5rem; 
                margin: 10px;
                max-height: 90vh;
                overflow-y: auto;
            }
            .chart-container { height: 220px; }
            
            .top-actions { grid-template-columns: 1fr; }
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

        <!-- Gelişmiş İstatistikler - Grafikler -->
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title">
                    <span>📈 Yıllık Rezervasyon Trendi</span>
                </div>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-title">
                    <span>📊 Rezervasyon Durumları</span>
                </div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="chart-card" style="grid-column: 1 / -1;">
                <div class="chart-title">
                    <span>🕒 En Yoğun Saatler (Popülerlik)</span>
                </div>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="hourChart"></canvas>
                </div>
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
                    <option value="geldi">📍 Geldi</option>
                    <option value="gelmedi">❓ Gelmedi</option>
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

        <!-- Mail Gönder Modal -->
        <div id="mailGonderModal" class="modal-overlay">
            <div class="modal-content" style="max-width: 600px;">
                <h3 class="modal-title">📧 Özel Mail Gönder</h3>
                <form id="mailGonderForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="filter-group" style="margin-bottom: 15px;">
                        <label>Alıcılar</label>
                        <input type="text" class="email-search" id="emailSearch" placeholder="Müşteri ara..." onkeyup="filtreleMailler()">
                        <div class="select-all-container">
                            <input type="checkbox" id="selectAllEmails" onclick="toggleSelectAll(this)">
                            <label for="selectAllEmails" style="font-weight: 600; font-size: 0.85rem;">Tümünü Seç</label>
                        </div>
                        <div class="email-list-container" id="customerEmailList">
                            <!-- Mailler JS ile buraya yüklenecek -->
                            <div style="padding: 20px; text-align: center; color: var(--muted);">Yükleniyor...</div>
                        </div>
                    </div>

                    <div class="filter-group" style="margin-bottom: 15px;">
                        <label>Konu / Başlık</label>
                        <input type="text" name="subject" required placeholder="Mail başlığını girin...">
                    </div>

                    <div class="filter-group" style="margin-bottom: 20px;">
                        <label>Mesaj İçeriği</label>
                        <textarea name="message" required style="min-height: 150px;" placeholder="Mesajınızı buraya yazın..."></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-modal btn-cancel" onclick="kapatModal('mailGonderModal')">İptal</button>
                        <button type="submit" class="btn-modal btn-confirm" style="background: var(--accent);">Maili Gönder</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rezervasyonlar Tablosu -->
        <h2>🍽️ Rezervasyonlar</h2>
        <div class="table-container">
             
            <div class="table-header">
             
                <div class="top-actions">
                    <button class="btn-primary" onclick="acModal('manuelEkleModal')"><span>+</span> <span class="btn-text">Yeni Ekle</span></button>
                    <button class="btn-primary" onclick="ayarlariAc()" style="background:var(--surface-2);border:1px solid rgba(255,255,255,0.1);"><span>⚙️</span> <span class="btn-text">Ayarlar</span></button>
                    <button class="btn-primary" onclick="mailGonderAc()" style="background:var(--surface-2);border:1px solid rgba(255,255,255,0.1);"><span>📧</span> <span class="btn-text">Mail Gönder</span></button>
                    <button class="btn-primary" onclick="window.location.href='api.php?action=export_excel'" style="background:var(--surface-2);border:1px solid rgba(255,255,255,0.1);"><span>📊</span> <span class="btn-text">Excel İndir</span></button>
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
                <div class="modal-form-group">
                    <label>E-posta (Opsiyonel)</label>
                    <input type="email" id="m-email" placeholder="orn@mail.com">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="modal-form-group">
                        <label>Tarih</label>
                        <input type="text" id="m-tarih" placeholder="Tarih Seçin" required readonly>
                    </div>
                    <div class="modal-form-group">
                        <label>Saat</label>
                        <select id="m-saat" required>
                            <!-- Saatler JS ile yüklenecek -->
                        </select>
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
        <div class="modal-content" style="max-width: 650px;">
            <h3 class="modal-title">Sistem Ayarları</h3>
            <form id="ayarlarForm" onsubmit="ayarlariKaydetSubmit(event)">
                <div class="modal-form-group">
                    <label>Saatlik Maksimum Kapasite</label>
                    <input type="number" id="ayar-kapasite" min="1" required>
                </div>
                <div class="modal-form-group">
                    <label>Çalışma Saatleri</label>
                    <div style="background: var(--surface-2); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 10px; margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                            <thead>
                                <tr style="color: var(--muted); border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <th style="padding: 8px; text-align: left;">Gün</th>
                                    <th style="padding: 8px; text-align: center;">Açılış</th>
                                    <th style="padding: 8px; text-align: center;">Kapanış</th>
                                    <th style="padding: 8px; text-align: center;">Durum</th>
                                </tr>
                            </thead>
                            <tbody id="calisma-saatleri-listesi">
                                <!-- Günler JS ile gelecek -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-form-group">
                    <label>Kapalı / Özel Günler (Tatil, Tadilat vb.)</label>
                    <div style="margin-bottom:10px;">
                        <input type="text" id="ayar-yeni-gun" placeholder="Tarih Seçin" readonly style="width:100%;">
                    </div>
                    <div style="display:flex; gap:10px; margin-bottom:15px;">
                        <input type="text" id="ayar-yeni-gun-not" placeholder="Kapatma Açıklaması (Müşteriye gösterilir)" style="flex:1;">
                        <button type="button" class="btn-primary" onclick="kapaliGunEkle()" style="padding: 0 25px;">Ekle</button>
                    </div>
                    <div id="kapali-gunler-listesi" style="max-height: 200px; overflow-y: auto; background: var(--surface-2); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                        <!-- Günler JS ile buraya gelecek -->
                    </div>
                    <small style="color:var(--muted); font-size:0.75rem; margin-top:5px; display:block;">Bu tarihlerde rezervasyon alınamaz ve açıklama müşteriye gösterilir.</small>
                </div>

                <div class="modal-form-group">
                    <label>Menü Dosyaları (PDF)</label>
                    <div style="background: var(--surface-2); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 15px; margin-bottom: 20px;">
                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 0.8rem; margin-bottom: 5px; display: block; color: var(--muted);">Yemek Menüsü</label>
                            <input type="file" id="ayar-menu-yemek" accept=".pdf" style="width: 100%; padding: 8px; background: var(--bg); border: 1px solid rgba(255,255,255,0.1); color: var(--text); border-radius: 4px;">
                            <div id="current-menu-yemek" style="font-size: 0.75rem; color: var(--accent); margin-top: 5px;"></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="font-size: 0.8rem; margin-bottom: 5px; display: block; color: var(--muted);">Alkol Menüsü</label>
                            <input type="file" id="ayar-menu-alkol" accept=".pdf" style="width: 100%; padding: 8px; background: var(--bg); border: 1px solid rgba(255,255,255,0.1); color: var(--text); border-radius: 4px;">
                            <div id="current-menu-alkol" style="font-size: 0.75rem; color: var(--accent); margin-top: 5px;"></div>
                        </div>
                        <div style="margin-bottom: 0;">
                            <label style="font-size: 0.8rem; margin-bottom: 5px; display: block; color: var(--muted);">Tatlı Menüsü</label>
                            <input type="file" id="ayar-menu-tatli" accept=".pdf" style="width: 100%; padding: 8px; background: var(--bg); border: 1px solid rgba(255,255,255,0.1); color: var(--text); border-radius: 4px;">
                            <div id="current-menu-tatli" style="font-size: 0.75rem; color: var(--accent); margin-top: 5px;"></div>
                        </div>
                    </div>
                    <small style="color:var(--muted); font-size:0.75rem; display:block;">Sadece PDF dosyaları kabul edilir. Yeni dosya yüklendiğinde eskisi silinmez ama veritabanında güncellenir.</small>
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
        let fpBas, fpSon, fpYeniGun;
        let kapaliGunler = {}; // { "2026-05-10": "Not", ... }
        let charts = {}; // ChartJS örneklerini tutmak için
        const gunIsimleri = {
            "1": "Pazartesi",
            "2": "Salı",
            "3": "Çarşamba",
            "4": "Perşembe",
            "5": "Cuma",
            "6": "Cumartesi",
            "0": "Pazar"
        };

        function initFlatpickr() {
            const config = {
                locale: "tr",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d F Y",
            };
            fpBas = flatpickr("#filtre-tarih-bas", config);
            fpSon = flatpickr("#filtre-tarih-son", config);
            
            fpYeniGun = flatpickr("#ayar-yeni-gun", config);
            
            flatpickr("#m-tarih", {
                locale: "tr",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d F Y"
            });

            // Saat dropdown'ını doldur
            const saatSelect = document.getElementById('m-saat');
            saatSelect.innerHTML = '<option value="" disabled selected>Saat Seçin</option>';
            for (let h = 10; h <= 23; h++) {
                for (let m = 0; m < 60; m += 15) {
                    const hh = String(h).padStart(2, '0');
                    const mm = String(m).padStart(2, '0');
                    const val = `${hh}:${mm}`;
                    saatSelect.innerHTML += `<option value="${val}">${val}</option>`;
                }
            }
            saatSelect.innerHTML += '<option value="00:00">00:00</option>';
            
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
                    
                    renderCharts(json.data);
                }
            } catch (e) {
                console.error('İstatistik hatası:', e);
            }
        }
        /**
         * Grafikleri Render Et
         */
        function renderCharts(data) {
            Chart.defaults.color = '#a0a0a0';
            Chart.defaults.font.family = "'Montserrat', sans-serif";

            // 1. Aylık Trend
            if (charts.trend) charts.trend.destroy();
            charts.trend = new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: data.aylik_trend.map(a => a.ay),
                    datasets: [{
                        label: 'Aylık Rezervasyon',
                        data: data.aylik_trend.map(a => a.count),
                        borderColor: '#9D432C',
                        backgroundColor: 'rgba(157, 67, 44, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#9D432C'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 2. Durum Dağılımı
            if (charts.status) charts.status.destroy();
            const statusLabels = {
                'beklemede': 'Beklemede',
                'onaylandi': 'Onaylandı',
                'iptal': 'İptal',
                'geldi': 'Geldi',
                'gelmedi': 'Gelmedi'
            };
            charts.status = new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(data.durum_dagilimi).map(d => statusLabels[d] || d),
                    datasets: [{
                        data: Object.values(data.durum_dagilimi),
                        backgroundColor: ['#eab308', '#22c55e', '#ef4444', '#3b82f6', '#666'],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { 
                            position: 'bottom',
                            labels: { padding: 20, usePointStyle: true, font: { size: 11 } }
                        }
                    },
                    cutout: '70%'
                }
            });

            // 3. Yoğun Saatler
            if (charts.hour) charts.hour.destroy();
            charts.hour = new Chart(document.getElementById('hourChart'), {
                type: 'bar',
                data: {
                    labels: data.saat_dagilimi.map(s => s.saat),
                    datasets: [{
                        label: 'Rezervasyon Sayısı',
                        data: data.saat_dagilimi.map(s => s.count),
                        backgroundColor: '#9D432C',
                        borderRadius: 5,
                        hoverBackgroundColor: '#b85436'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
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
                                ${r.toplam_ziyaret > 1 ? `<div class="badge-visitor" title="Daha önce ${r.toplam_ziyaret - 1} kez rezervasyon yapmış">⭐ Tekrar Gelen Müşteri (${r.toplam_ziyaret}. Ziyaret)</div>` : ''}
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
                                    ${(r.durum !== 'onaylandi' && r.durum !== 'geldi' && r.durum !== 'gelmedi') ? `<button class="btn-action btn-approve" onclick="islem('approve',${r.id})" title="Onayla">✓ Onayla</button>` : ''}
                                    ${r.durum === 'onaylandi' ? `
                                        <button class="btn-action btn-status-geldi" onclick="islem('arrived',${r.id})">Geldi</button>
                                        <button class="btn-action btn-status-gelmedi" onclick="islem('no-show',${r.id})">Gelmedi</button>
                                    ` : ''}
                                    ${(r.durum !== 'iptal' && r.durum !== 'geldi' && r.durum !== 'gelmedi') ? `<button class="btn-action btn-reject" onclick="islem('reject',${r.id})" title="İptal Et">✕ İptal</button>` : ''}
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
                    showToast(json.message, 'success');
                    yukleRezervasyonlar();
                    yukleIstatistikler();
                } else {
                    showToast(json.message, 'error');
                }
            } catch (e) {
                showToast('İşlem sırasında hata oluştu.', 'error');
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
                'iptal': '<span class="badge badge-iptal">❌ İptal</span>',
                'geldi': '<span class="badge" style="background:#22c55e; color:#fff;">📍 Geldi</span>',
                'gelmedi': '<span class="badge" style="background:#f59e0b; color:#fff;">❓ Gelmedi</span>'
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
                formData.append('email', document.getElementById('m-email').value);
                formData.append('tarih', document.getElementById('m-tarih').value);
                formData.append('saat', document.getElementById('m-saat').value);
                formData.append('kisi_sayisi', document.getElementById('m-kisi').value);
                formData.append('csrf_token', csrfToken);
                
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    showToast(json.message, 'success');
                    kapatModal('manuelEkleModal');
                    e.target.reset();
                    yukleHerSeyi();
                } else {
                    showToast(json.message, 'error');
                }
            } catch(err) {
                showToast('Bağlantı hatası.', 'error');
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
            document.getElementById('ayar-yeni-gun-not').value = '';
            fpYeniGun.clear();
            kapaliGunler = {};
            listeleKapaliGunler();
            
            try {
                const res = await fetch('api.php?action=settings_get');
                const json = await res.json();
                if (json.success) {
                    document.getElementById('ayar-kapasite').value = json.data.kapasite;
                    
                    // Eski dizi formatını nesneye dönüştür (JSON.stringify hatasını önlemek için)
                    let loadedData = json.data.kapali_gunler || {};
                    if (Array.isArray(loadedData)) {
                        kapaliGunler = {};
                        loadedData.forEach(d => { kapaliGunler[d] = ""; });
                    } else {
                        kapaliGunler = loadedData;
                    }
                    
                    listeleKapaliGunler();
                    renderCalismaSaatleri(json.data.calisma_saatleri || {});

                    // Menü dosyaları
                    document.getElementById('current-menu-yemek').textContent = json.data.menu_yemek ? 'Mevcut: ' + json.data.menu_yemek.split('/').pop() : 'Henüz yüklenmedi';
                    document.getElementById('current-menu-alkol').textContent = json.data.menu_alkol ? 'Mevcut: ' + json.data.menu_alkol.split('/').pop() : 'Henüz yüklenmedi';
                    document.getElementById('current-menu-tatli').textContent = json.data.menu_tatli ? 'Mevcut: ' + json.data.menu_tatli.split('/').pop() : 'Henüz yüklenmedi';
                    
                    // Inputları temizle
                    document.getElementById('ayar-menu-yemek').value = '';
                    document.getElementById('ayar-menu-alkol').value = '';
                    document.getElementById('ayar-menu-tatli').value = '';
                }
            } catch(err) {
                showToast('Ayarlar yüklenemedi', 'error');
            }
        }

        /**
         * Çalışma saatlerini modalda render et
         */
        function renderCalismaSaatleri(saatler) {
            const container = document.getElementById('calisma-saatleri-listesi');
            let html = '';
            ["1", "2", "3", "4", "5", "6", "0"].forEach(gun => {
                const s = saatler[gun] || { acilis: "15:00", kapanis: "00:00", durum: "acik" };
                html += `
                <tr style="border-bottom:1px solid rgba(255,255,255,0.02);">
                    <td style="padding:8px; font-weight:500;">${gunIsimleri[gun]}</td>
                    <td style="padding:8px; text-align:center;">
                        <input type="time" class="saat-input" data-gun="${gun}" data-type="acilis" value="${s.acilis}" 
                               style="background:var(--surface-2); border:1px solid rgba(255,255,255,0.1); color:var(--text); border-radius:4px; padding:3px 5px; font-size:0.8rem;">
                    </td>
                    <td style="padding:8px; text-align:center;">
                        <input type="time" class="saat-input" data-gun="${gun}" data-type="kapanis" value="${s.kapanis}" 
                               style="background:var(--surface-2); border:1px solid rgba(255,255,255,0.1); color:var(--text); border-radius:4px; padding:3px 5px; font-size:0.8rem;">
                    </td>
                    <td style="padding:8px; text-align:center;">
                        <input type="checkbox" class="durum-input" data-gun="${gun}" ${s.durum === 'acik' ? 'checked' : ''} style="cursor:pointer;">
                    </td>
                </tr>`;
            });
            container.innerHTML = html;
        }

        /**
         * Kapalı günleri modalda listele
         */
        function listeleKapaliGunler() {
            const container = document.getElementById('kapali-gunler-listesi');
            const keys = Object.keys(kapaliGunler).sort();
            
            if (keys.length === 0) {
                container.innerHTML = '<div style="padding:15px; color:var(--muted); text-align:center; font-size:0.85rem;">Henüz kapalı gün eklenmedi.</div>';
                return;
            }
            
            let html = '';
            keys.forEach(date => {
                html += `
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; border-bottom:1px solid rgba(255,255,255,0.03);">
                    <div>
                        <div style="font-size:0.85rem; font-weight:500;">${formatTarih(date)}</div>
                        <div style="font-size:0.75rem; color:var(--muted);">${kapaliGunler[date] || 'Açıklama yok'}</div>
                    </div>
                    <button type="button" onclick="kapaliGunSil('${date}')" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:1.1rem;">×</button>
                </div>`;
            });
            container.innerHTML = html;
        }

        /**
         * Yeni kapalı gün ekle
         */
        function kapaliGunEkle() {
            const date = document.getElementById('ayar-yeni-gun').value;
            const note = document.getElementById('ayar-yeni-gun-not').value;
            
            if (!date) {
                showToast('Lütfen bir tarih seçin.', 'error');
                return;
            }
            
            kapaliGunler[date] = note;
            listeleKapaliGunler();
            
            // Temizle
            fpYeniGun.clear();
            document.getElementById('ayar-yeni-gun-not').value = '';
        }

        /**
         * Kapalı gün sil
         */
        function kapaliGunSil(date) {
            delete kapaliGunler[date];
            listeleKapaliGunler();
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
                formData.append('kapali_gunler', JSON.stringify(kapaliGunler));
                
                // Çalışma Saatlerini topla
                const saatler = {};
                document.querySelectorAll('#calisma-saatleri-listesi tr').forEach(tr => {
                    const gun = tr.querySelector('.saat-input').dataset.gun;
                    saatler[gun] = {
                        acilis: tr.querySelector('.saat-input[data-type="acilis"]').value,
                        kapanis: tr.querySelector('.saat-input[data-type="kapanis"]').value,
                        durum: tr.querySelector('.durum-input').checked ? 'acik' : 'kapali'
                    };
                });
                formData.append('calisma_saatleri', JSON.stringify(saatler));

                // Menü Dosyaları
                const menuYemek = document.getElementById('ayar-menu-yemek').files[0];
                const menuAlkol = document.getElementById('ayar-menu-alkol').files[0];
                const menuTatli = document.getElementById('ayar-menu-tatli').files[0];
                
                if (menuYemek) formData.append('menu_yemek', menuYemek);
                if (menuAlkol) formData.append('menu_alkol', menuAlkol);
                if (menuTatli) formData.append('menu_tatli', menuTatli);

                formData.append('csrf_token', csrfToken);
                
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    showToast(json.message, 'success');
                    kapatModal('ayarlarModal');
                } else {
                    showToast(json.message, 'error');
                }
            } catch(err) {
                showToast('Bağlantı hatası.', 'error');
            }
            btn.disabled = false;
            btn.textContent = "Ayarları Kaydet";
        }

        /**
         * Toast mesajı göster
         */
        function showToast(mesaj, tip) {
            const toast = document.getElementById('toast');
            toast.textContent = mesaj;
            toast.className = `toast toast-${tip} show`;
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
        /**
         * Mail Gönder Modalını Aç
         */
        async function mailGonderAc() {
            acModal('mailGonderModal');
            const listContainer = document.getElementById('customerEmailList');
            listContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--muted);">Yükleniyor...</div>';
            
            try {
                const response = await fetch('api.php?action=get_emails');
                const result = await response.json();
                
                if (result.success) {
                    if (result.data.length === 0) {
                        listContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: var(--muted);">Kayıtlı e-posta adresi bulunamadı.</div>';
                        return;
                    }
                    
                    let html = '';
                    result.data.forEach((item, index) => {
                        html += `
                            <div class="email-item" data-search="${item.ad_soyad.toLowerCase()} ${item.email.toLowerCase()}">
                                <input type="checkbox" name="emails[]" value="${item.email}" id="em_${index}">
                                <label for="em_${index}"><strong>${item.ad_soyad}</strong> (${item.email})</label>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html;
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Mailler yüklenirken bir hata oluştu.', 'error');
            }
        }

        /**
         * Mailleri Listede Filtrele
         */
        function filtreleMailler() {
            const search = document.getElementById('emailSearch').value.toLowerCase();
            const items = document.querySelectorAll('.email-item');
            items.forEach(item => {
                const text = item.getAttribute('data-search');
                if (text.includes(search)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        /**
         * Tümünü Seç/Kaldır
         */
        function toggleSelectAll(source) {
            const checkboxes = document.querySelectorAll('#customerEmailList input[type="checkbox"]');
            checkboxes.forEach(cb => {
                if (cb.parentElement.style.display !== 'none') {
                    cb.checked = source.checked;
                }
            });
        }

        /**
         * Mail Gönder Form İşlemi
         */
        document.getElementById('mailGonderForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const selectedEmails = Array.from(document.querySelectorAll('input[name="emails[]"]:checked')).map(cb => cb.value);
            if (selectedEmails.length === 0) {
                showToast('Lütfen en az bir alıcı seçin.', 'error');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;
            submitBtn.disabled = true;
            submitBtn.innerText = 'Gönderiliyor...';
            
            const formData = new FormData(this);
            formData.append('action', 'send_bulk_email');
            // Checkbox'ları elle ekliyoruz çünkü normal FormData ile alması zor olabilir (seçilenleri array yapıyoruz)
            formData.delete('emails[]');
            selectedEmails.forEach(email => formData.append('emails[]', email));
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message, 'success');
                    kapatModal('mailGonderModal');
                    this.reset();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Bir hata oluştu.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        });
    </script>
</body>
</html>
