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
            --sidebar-bg: #111111;
            --surface: #151515;
            --surface-2: #1c1c1c;
            --text: #f5f5f5;
            --muted: #a0a0a0;
            --accent: #9D432C;
            --accent-hover: #b85436;
            --success: #22c55e;
            --warning: #eab308;
            --danger: #ef4444;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(255,255,255,0.05);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            padding: 2rem 1.5rem;
            z-index: 1000;
        }
        .sidebar-logo {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8rem;
            color: var(--accent);
            text-decoration: none;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-logo img { height: 35px; }
        
        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .nav-item {
            padding: 12px 15px;
            border-radius: 8px;
            color: var(--muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .nav-item:hover {
            background: rgba(255,255,255,0.03);
            color: var(--text);
        }
        .nav-item.active {
            background: rgba(157,67,44,0.1);
            color: var(--accent);
            border-right: 3px solid var(--accent);
        }
        .sidebar-footer {
            margin-top: auto;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        /* ===== MAIN CONTENT ===== */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2rem 3rem;
            min-width: 0;
        }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }
        .page-title h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            font-weight: 400;
            margin-bottom: 0.3rem;
        }
        .page-title p {
            color: var(--muted);
            font-size: 0.85rem;
        }

        /* Glass Cards */
        .glass-card {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .view-section { display: none; }
        .view-section.active { display: block; }

        /* Existing Styles Adaptations */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); border-color: var(--accent); }
        .stat-card .stat-value { font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; color: var(--accent); line-height: 1; margin: 10px 0; }
        
        .table-container { background: var(--surface); border-radius: 16px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1.2rem; background: rgba(255,255,255,0.02); color: var(--muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        td { padding: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 0.9rem; }
        tr:hover td { background: rgba(255,255,255,0.01); }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-2px); }

        /* Badges */
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-onaylandi { background: rgba(34,197,94,0.1); color: var(--success); }
        .badge-beklemede { background: rgba(234,179,8,0.1); color: var(--warning); }
        .badge-iptal { background: rgba(239,68,68,0.1); color: var(--danger); }



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

        /* Toplu Mail Sayfası */
        .email-list-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 450px;
            overflow-y: auto;
            padding-right: 10px;
            margin-top: 10px;
        }
        .email-list-container::-webkit-scrollbar {
            width: 5px;
        }
        .email-list-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
        }
        .email-list-container::-webkit-scrollbar-thumb {
            background: rgba(157, 67, 44, 0.4);
            border-radius: 10px;
        }
        .email-list-container::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }
        .email-item {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 12px 15px;
            border-radius: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
        }
        .email-item:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(157, 67, 44, 0.3);
            transform: translateX(4px);
        }
        .email-item.selected {
            background: rgba(157, 67, 44, 0.1);
            border-color: rgba(157, 67, 44, 0.5);
        }
        .email-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--accent);
            cursor: pointer;
            flex-shrink: 0;
        }
        .email-item label {
            cursor: pointer;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .email-item .customer-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
        }
        .email-item .customer-email {
            font-size: 0.75rem;
            color: var(--muted);
            letter-spacing: 0.5px;
        }
        .email-search {
            width: 100%;
            padding: 14px 18px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            color: var(--text);
            margin-bottom: 5px;
            outline: none;
            transition: all 0.3s;
            font-family: inherit;
        }
        .email-search:focus {
            border-color: var(--accent);
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 15px rgba(157, 67, 44, 0.1);
        }
        .selection-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 5px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .selected-count {
            font-size: 0.8rem;
            color: var(--accent);
            font-weight: 600;
            background: rgba(157, 67, 44, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
        }

        /* Bulk Mail Responsive */
        .bulk-mail-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .bulk-mail-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .bulk-mail-grid > div:first-child {
                border-right: none !important;
                padding-right: 0 !important;
                border-bottom: 1px solid rgba(255,255,255,0.05);
                padding-bottom: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .view-section {
                padding: 15px !important;
            }
            .content-header h1 {
                font-size: 1.5rem !important;
            }
            .glass-card {
                padding: 20px !important;
            }
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

        /* Galeri Admin Stilleri */
        #galeri-liste {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            padding: 10px;
        }
        .galeri-item-admin {
            position: relative;
            background: var(--surface-2);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .galeri-item-admin:hover {
            transform: translateY(-3px);
            border-color: var(--accent);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .galeri-item-admin img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }
        .galeri-item-admin .controls {
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.2);
            margin-top: auto;
        }
        .galeri-item-admin .btn-del {
            color: #ef4444;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            transition: transform 0.2s;
        }
        .galeri-item-admin .btn-del:hover {
            transform: scale(1.2);
        }
        .galeri-item-admin .move-btns {
            display: flex;
            gap: 8px;
        }
        .galeri-item-admin .move-btn {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: none;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .galeri-item-admin .move-btn:hover {
            background: var(--accent);
            color: #fff;
        }search {
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
        /* Settings View */
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        .saat-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(255,255,255,0.02);
            border-radius: 8px;
        }
        .gun-adı { width: 100px; font-size: 0.85rem; }
        .saat-inputs { display: flex; gap: 5px; flex: 1; }
        .saat-input-main {
            background: var(--surface-2);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 4px;
            padding: 2px 5px;
            font-size: 0.8rem;
            width: 80px;
        }
        .durum-checkbox {
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            min-width: 60px;
        }

        /* RESPONSIVE DESIGN (MOBİL UYUMLULUK) */
        /* ============================================================ */
        
        .mobile-header {
            display: none;
            background: var(--sidebar-bg);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            position: sticky;
            top: 0;
            z-index: 1001;
            justify-content: space-between;
            align-items: center;
        }
        .menu-toggle {
            background: none;
            border: none;
            color: var(--text);
            font-size: 1.5rem;
            cursor: pointer;
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            z-index: 999;
        }

        @media (max-width: 1024px) {
            body { flex-direction: column; }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                width: 280px;
                box-shadow: 20px 0 50px rgba(0,0,0,0.5);
                z-index: 1002;
            }
            .sidebar.open { transform: translateX(0); }
            .mobile-header { display: flex; }
            .main-wrapper { margin-left: 0; padding: 1.5rem; }
            .sidebar-overlay.show { display: block; }
            
            :root { --sidebar-width: 240px; }
            .filters > div { grid-template-columns: 1fr 1fr 1fr !important; }
            .settings-grid { grid-template-columns: 1fr; gap: 2rem; }

            .content-header { flex-direction: column; align-items: stretch; gap: 1rem; }
            .content-header button { width: 100%; justify-content: center; }
            
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .charts-grid { grid-template-columns: 1fr; }
            
            .rez-card { grid-template-columns: 1fr; }
            .actions { flex-direction: column; align-items: stretch; }
            .btn-action { width: 100%; justify-content: center; }

            .saat-row { flex-wrap: wrap; gap: 8px; }
            .gun-adı { width: 100%; font-weight: 600; margin-bottom: 2px; }
            .saat-inputs { width: 100%; }
            .saat-input-main { flex: 1; height: 35px; width: auto; }
            .durum-checkbox { width: 100%; margin-top: 5px; }
        }

        @media (max-width: 768px) {
            .filters > div { grid-template-columns: 1fr !important; gap: 0.8rem; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .kapali-gun-input-group { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <header class="mobile-header">
        <a href="#" class="sidebar-logo" style="margin-bottom:0;">
            <img src="../sintesi.webp" alt="Sintesi" style="width:30px;">
            <span>Sintesi</span>
        </a>
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    </header>

    <aside class="sidebar" id="sidebar">
        <a href="panel.php" class="sidebar-logo">
            <img src="../sintesi.webp" alt="Sintesi">
            <span>Sintesi</span>
        </a>
        
        <nav class="sidebar-nav">
            <div class="nav-item active" onclick="switchView('dashboard', this)">
                <span>📊</span> Dashboard
            </div>
            <div class="nav-item" onclick="switchView('reservations', this)">
                <span>🍽️</span> Rezervasyonlar
            </div>
            <div class="nav-item" onclick="switchView('gallery', this)">
                <span>🖼️</span> Galeri Yönetimi
            </div>
            <div class="nav-item" onclick="switchView('menu', this)">
                <span>🍴</span> Menü Yönetimi
            </div>
            <div class="nav-item" onclick="switchView('settings', this)">
                <span>⚙️</span> Ayarlar
            </div>
            <div class="nav-item" onclick="switchView('bulk-mail', this)">
                <span>📧</span> Toplu Mail
            </div>
            <div class="nav-item" onclick="window.location.href='api.php?action=export_excel'">
                <span>📥</span> Excel Raporu
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="nav-item" style="color: var(--muted); cursor: default; opacity: 0.7;">
                👤 <?= htmlspecialchars($_SESSION['admin_kullanici']) ?>
            </div>
            <a href="logout.php" class="nav-item" style="color: var(--danger);">
                <span>🚪</span> Çıkış Yap
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-wrapper">
        
        <!-- DASHBOARD VIEW -->
        <section id="view-dashboard" class="view-section active">
            <div class="content-header">
                <div class="page-title">
                    <h1>Dashboard</h1>
                    <p>Restoran genel durumu ve istatistikler</p>
                </div>
                <button class="btn-primary" onclick="yukleHerSeyi(this)">🔄 Verileri Yenile</button>
            </div>

            <div class="stats-grid">
                <div class="stat-card" onclick="hizliFiltre('bugun')">
                    <div class="stat-label">Bugünkü Rezervasyon</div>
                    <div class="stat-value" id="stat-bugun">-</div>
                    <div class="stat-icon">📅</div>
                </div>
                <div class="stat-card" onclick="hizliFiltre('hafta')">
                    <div class="stat-label">Bu Hafta</div>
                    <div class="stat-value" id="stat-hafta">-</div>
                    <div class="stat-icon">📆</div>
                </div>
                <div class="stat-card" onclick="hizliFiltre('bekleyen')">
                    <div class="stat-label">Bekleyen Onay</div>
                    <div class="stat-value" id="stat-bekleyen">-</div>
                    <div class="stat-icon">⏳</div>
                </div>
                <div class="stat-card" onclick="hizliFiltre('toplam')">
                    <div class="stat-label">Toplam Kayıt</div>
                    <div class="stat-value" id="stat-toplam">-</div>
                    <div class="stat-icon">📊</div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-card glass-card">
                    <div class="chart-title">📈 Yıllık Rezervasyon Trendi</div>
                    <div class="chart-container"><canvas id="trendChart"></canvas></div>
                </div>
                <div class="chart-card glass-card">
                    <div class="chart-title">📊 Rezervasyon Durumları</div>
                    <div class="chart-container"><canvas id="statusChart"></canvas></div>
                </div>
                <div class="chart-card glass-card" style="grid-column: 1 / -1;">
                    <div class="chart-title">🕒 En Yoğun Saatler</div>
                    <div class="chart-container" style="height: 250px;"><canvas id="hourChart"></canvas></div>
                </div>
            </div>
        </section>

        <!-- RESERVATIONS VIEW -->
        <section id="view-reservations" class="view-section">
            <div class="content-header">
                <div class="page-title">
                    <h1>Rezervasyonlar</h1>
                    <p>Tüm kayıtları listeleyin ve yönetin</p>
                </div>
                <button class="btn-primary" onclick="acModal('manuelEkleModal')"><span>+</span> Yeni Rezervasyon</button>
            </div>

            <div class="filters glass-card" style="margin-bottom: 2rem;">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                    <div class="filter-group">
                        <label>Arama</label>
                        <input type="text" id="filtre-arama" placeholder="Müşteri Adı, Tel...">
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
                        <label>Başlangıç</label>
                        <input type="text" id="filtre-tarih-bas" placeholder="Seç" readonly>
                    </div>
                    <div class="filter-group">
                        <label>Bitiş</label>
                        <input type="text" id="filtre-tarih-son" placeholder="Seç" readonly>
                    </div>
                    <button class="btn-primary" onclick="yukleRezervasyonlar()" style="height: 45px; padding: 0 25px;">Filtrele</button>
                </div>
            </div>

            <div class="table-container glass-card">
                <div id="tablo-icerik"><div class="loading">Yükleniyor...</div></div>
                <div id="sayfalama" class="pagination"></div>
            </div>
        </section>

        <!-- GALLERY VIEW -->
        <section id="view-gallery" class="view-section">
            <div class="content-header">
                <div class="page-title">
                    <h1>Galeri Yönetimi</h1>
                    <p>Web sitesindeki fotoğrafları yükleyin ve sıralayın</p>
                </div>
            </div>
            
            <div class="glass-card">
                <div style="background: rgba(59, 130, 246, 0.05); padding: 20px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.1); margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 12px; font-weight: 600;">Yeni Fotoğraflar Ekle</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                        <input type="file" id="galeri-yukle-input-main" accept="image/*" multiple style="flex: 1; min-width: 200px; padding: 10px; background: var(--surface-2); border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px; color: var(--muted);">
                        <button class="btn-primary" onclick="galeriResimYukleMain()" id="galeri-yukle-btn-main" style="white-space: nowrap;">Resimleri Yükle</button>
                    </div>
                    <small style="color: var(--muted); margin-top: 8px; display: block;">Maksimum 1200px genişliğe düşürülür ve WebP formatına dönüştürülür. Çoklu seçim yapabilirsiniz.</small>
                </div>

                <div id="galeri-liste-main" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; padding: 5px;">
                    <!-- Galeri öğeleri JS ile gelecek -->
                </div>

                <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                    <button class="btn-primary" onclick="galeriSiralamayiKaydetMain()" id="galeri-sirala-btn-main">Değişiklikleri Kaydet</button>
                </div>
            </div>
        </section>

        <!-- MENU VIEW -->
        <section id="view-menu" class="view-section">
            <div class="content-header">
                <div class="page-title">
                    <h1>Menü Yönetimi</h1>
                    <p>Menü linklerini (PDF/Link) güncelleyin</p>
                </div>
            </div>
            
            <div class="glass-card">
                <form id="menuFormMain" onsubmit="menuKaydetMain(event)" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                        <!-- Türkçe Menüler -->
                        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                            <h3 style="font-family: 'Cormorant Garamond'; color: var(--accent); font-size: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">🇹🇷 Türkçe Menüler</h3>
                            <div class="filter-group">
                                <label>Yemek Menüsü (PDF)</label>
                                <div id="current-menu-yemek-tr-main" style="font-size: 0.75rem; color: var(--muted); margin-bottom: 5px;"></div>
                                <input type="file" name="menu_yemek" id="menu-yemek-tr-main" accept=".pdf">
                            </div>
                            <div class="filter-group">
                                <label>Alkol Menüsü (PDF)</label>
                                <div id="current-menu-alkol-tr-main" style="font-size: 0.75rem; color: var(--muted); margin-bottom: 5px;"></div>
                                <input type="file" name="menu_alkol" id="menu-alkol-tr-main" accept=".pdf">
                            </div>
                            <div class="filter-group">
                                <label>Tatlı Menüsü (PDF)</label>
                                <div id="current-menu-tatli-tr-main" style="font-size: 0.75rem; color: var(--muted); margin-bottom: 5px;"></div>
                                <input type="file" name="menu_tatli" id="menu-tatli-tr-main" accept=".pdf">
                            </div>
                        </div>
                        <!-- İngilizce Menüler -->
                        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                            <h3 style="font-family: 'Cormorant Garamond'; color: var(--accent); font-size: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;">🇬🇧 English Menus</h3>
                            <div class="filter-group">
                                <label>Food Menu (PDF)</label>
                                <div id="current-menu-yemek-en-main" style="font-size: 0.75rem; color: var(--muted); margin-bottom: 5px;"></div>
                                <input type="file" name="menu_yemek_en" id="menu-yemek-en-main" accept=".pdf">
                            </div>
                            <div class="filter-group">
                                <label>Alcohol Menu (PDF)</label>
                                <div id="current-menu-alkol-en-main" style="font-size: 0.75rem; color: var(--muted); margin-bottom: 5px;"></div>
                                <input type="file" name="menu_alkol_en" id="menu-alkol-en-main" accept=".pdf">
                            </div>
                            <div class="filter-group">
                                <label>Dessert Menu (PDF)</label>
                                <div id="current-menu-tatli-en-main" style="font-size: 0.75rem; color: var(--muted); margin-bottom: 5px;"></div>
                                <input type="file" name="menu_tatli_en" id="menu-tatli-en-main" accept=".pdf">
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1.5rem; display: flex; justify-content: flex-end;">
                        <button type="submit" id="menu-submit-btn-main" class="btn-primary">💾 Değişiklikleri Yükle ve Kaydet</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- SETTINGS VIEW -->
        <section id="view-settings" class="view-section">
            <div class="content-header">
                <div class="page-title">
                    <h1>Genel Ayarlar</h1>
                    <p>Kapasite, çalışma saatleri ve kapalı günler</p>
                </div>
            </div>
            
            <div class="glass-card">
                <form id="ayarlarFormMain" onsubmit="ayarlariKaydetMain(event)">
                    <div class="settings-grid">
                        <div>
                            <h3 style="font-family: 'Cormorant Garamond'; color: var(--accent); font-size: 1.4rem; margin-bottom: 1rem;">⚙️ Sistem Ayarları</h3>
                            <div class="filter-group" style="margin-bottom: 1.5rem;"><label>Saatlik Kişi Kapasitesi</label><input type="number" name="kapasite" id="kapasite_main" min="1" max="100"></div>
                            
                            <h3 style="font-family: 'Cormorant Garamond'; color: var(--accent); font-size: 1.4rem; margin-bottom: 1rem;">🔒 Kapalı Günler</h3>
                            <div id="kapali-gunler-liste-main" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; min-height: 40px; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 8px;"></div>
                            <div style="display: flex; flex-direction: column; gap: 10px; background: var(--surface-2); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                                <div class="kapali-gun-input-group" style="display: flex; gap: 10px;">
                                    <input type="date" id="yeni-kapali-gun-main" style="flex: 1; background: rgba(255,255,255,0.05); border: none; color: #fff; padding: 8px; border-radius: 4px;">
                                    <input type="text" id="yeni-kapali-gun-not-main" placeholder="Açıklama (Örn: Özel Davet)" style="flex: 2; background: rgba(255,255,255,0.05); border: none; color: #fff; padding: 8px; border-radius: 4px;">
                                </div>
                                <button type="button" class="btn-primary" onclick="kapaliGunEkleMain()" style="width: 100%; padding: 10px; font-size: 0.9rem;">+ Kapalı Gün Ekle</button>
                            </div>
                        </div>
                        <div>
                            <h3 style="font-family: 'Cormorant Garamond'; color: var(--accent); font-size: 1.4rem; margin-bottom: 1rem;">🕒 Çalışma Saatleri</h3>
                            <div id="calisma-saatleri-konteyner-main" style="display: flex; flex-direction: column; gap: 10px;">
                                <!-- Dinamik Gelecek -->
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1.5rem; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-primary">✅ Ayarları Kaydet</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- BULK MAIL VIEW -->
        <section id="view-bulk-mail" class="view-section">
            <div class="content-header">
                <div class="page-title">
                    <h1>📧 Toplu Mail Gönder</h1>
                    <p>Müşterilere özel kampanya veya duyuru e-postaları gönderin</p>
                </div>
            </div>
            
            <div class="glass-card" style="max-width: 900px; margin: 0 auto;">
                <form id="mailGonderForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="bulk-mail-grid">
                        <!-- Sol: Alıcı Seçimi -->
                        <div style="border-right: 1px solid rgba(255,255,255,0.05); padding-right: 1.5rem;">
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; margin-bottom: 10px; font-weight: 600; font-size: 0.95rem; color: var(--accent);">👥 Alıcı Seçimi</label>
                                <input type="text" class="email-search" id="emailSearch" placeholder="İsim veya e-posta ile ara..." onkeyup="filtreleMailler()">
                            </div>
                            
                            <div class="selection-info">
                                <div class="select-all-container">
                                    <input type="checkbox" id="selectAllEmails" onclick="toggleSelectAll(this)">
                                    <label for="selectAllEmails" style="font-weight: 600; font-size: 0.85rem; margin-left: 8px; cursor: pointer;">Tümünü Seç</label>
                                </div>
                                <div id="selectedEmailCount" class="selected-count">0 Seçildi</div>
                            </div>
                            
                            <div class="email-list-container" id="customerEmailList">
                                <!-- Mailler JS ile yüklenecek -->
                            </div>
                        </div>

                        <!-- Sağ: Mesaj İçeriği -->
                        <div>
                            <div class="filter-group" style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Konu / Başlık</label>
                                <input type="text" name="subject" required placeholder="Mail başlığını girin..." style="width: 100%; padding: 12px; background: var(--surface-2); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: var(--text);">
                            </div>
                            <div class="filter-group" style="margin-bottom: 25px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Mesaj İçeriği</label>
                                <textarea name="message" required style="width: 100%; min-height: 250px; padding: 15px; background: var(--surface-2); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: var(--text); resize: vertical; font-family: inherit;" placeholder="Mesajınızı buraya yazın..."></textarea>
                            </div>
                            <div style="display: flex; justify-content: flex-end;">
                                <button type="submit" class="btn-primary" style="width: 100%; padding: 15px; font-weight: 600; font-size: 1rem;">
                                    Maili Gönder
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

    </div>

    <!-- MODALS (Sadece küçük işlemler için saklıyoruz) -->

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

                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="kapatModal('ayarlarModal')">İptal</button>
                    <button type="submit" class="btn-modal btn-primary">Ayarları Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Menü Yönetimi Modal -->
    <div id="menuModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 700px;">
            <h3 class="modal-title">Menü Yönetimi</h3>
            <p class="modal-text">Yemek, Alkol ve Tatlı menülerini PDF formatında buradan güncelleyebilirsiniz.</p>
            <form id="menuForm" onsubmit="menuKaydetSubmit(event)">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; text-align: left; margin-bottom: 25px;">
                    <!-- Türkçe Menüler -->
                    <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
                        <h4 style="color: var(--accent); margin-bottom: 15px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">🇹🇷 TR Menüler (Türkçe)</h4>
                        
                        <div class="modal-form-group">
                            <label>Yemek Menüsü (TR)</label>
                            <input type="file" id="menu-yemek-tr" accept=".pdf" style="font-size: 0.8rem;">
                            <div id="current-menu-yemek-tr" style="font-size: 0.7rem; color: var(--muted); margin-top: 4px;"></div>
                        </div>
                        
                        <div class="modal-form-group">
                            <label>Alkol Menüsü (TR)</label>
                            <input type="file" id="menu-alkol-tr" accept=".pdf" style="font-size: 0.8rem;">
                            <div id="current-menu-alkol-tr" style="font-size: 0.7rem; color: var(--muted); margin-top: 4px;"></div>
                        </div>
                        
                        <div class="modal-form-group">
                            <label>Tatlı Menüsü (TR)</label>
                            <input type="file" id="menu-tatli-tr" accept=".pdf" style="font-size: 0.8rem;">
                            <div id="current-menu-tatli-tr" style="font-size: 0.7rem; color: var(--muted); margin-top: 4px;"></div>
                        </div>
                    </div>

                    <!-- İngilizce Menüler -->
                    <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
                        <h4 style="color: var(--accent); margin-bottom: 15px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">🇬🇧 EN Menüler (English)</h4>
                        
                        <div class="modal-form-group">
                            <label>Yemek Menüsü (EN)</label>
                            <input type="file" id="menu-yemek-en" accept=".pdf" style="font-size: 0.8rem;">
                            <div id="current-menu-yemek-en" style="font-size: 0.7rem; color: var(--muted); margin-top: 4px;"></div>
                        </div>
                        
                        <div class="modal-form-group">
                            <label>Alkol Menüsü (EN)</label>
                            <input type="file" id="menu-alkol-en" accept=".pdf" style="font-size: 0.8rem;">
                            <div id="current-menu-alkol-en" style="font-size: 0.7rem; color: var(--muted); margin-top: 4px;"></div>
                        </div>
                        
                        <div class="modal-form-group">
                            <label>Tatlı Menüsü (EN)</label>
                            <input type="file" id="menu-tatli-en" accept=".pdf" style="font-size: 0.8rem;">
                            <div id="current-menu-tatli-en" style="font-size: 0.7rem; color: var(--muted); margin-top: 4px;"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="kapatModal('menuModal')">İptal</button>
                    <button type="submit" class="btn-modal btn-primary">Menüleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Galeri Yönetimi Modal -->
    <div id="galeriModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 900px;">
            <h3 class="modal-title">Galeri Yönetimi</h3>
            
            <div style="background: rgba(59, 130, 246, 0.05); padding: 15px; border-radius: 10px; border: 1px solid rgba(59, 130, 246, 0.2); margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: 500; font-size: 0.9rem;">Yeni Fotoğraf Ekle (Çoklu Seçim Yapılabilir)</label>
                <div style="display: flex; gap: 10px;">
                    <input type="file" id="galeri-yukle-input" accept="image/*" multiple style="flex: 1; padding: 8px; background: var(--surface-2); border: 1px dashed rgba(255,255,255,0.2); border-radius: 6px;">
                    <button class="btn-primary" onclick="galeriResimYukle()" id="galeri-yukle-btn">Yükle</button>
                </div>
                <small style="color: var(--muted); margin-top: 5px; display: block;">Maksimum 1200px genişliğe düşürülür ve WebP formatına dönüştürülür.</small>
            </div>

            <div id="galeri-liste" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; max-height: 450px; overflow-y: auto; padding: 5px;">
                <!-- Galeri öğeleri JS ile gelecek -->
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal btn-cancel" onclick="kapatModal('galeriModal')">Kapat</button>
                <button type="button" class="btn-modal btn-primary" onclick="galeriSiralamayiKaydet()" id="galeri-sirala-btn">Sıralamayı Kaydet</button>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>";

        let currentPage = 1;
        let currentDeleteId = null;
        let fpBas, fpSon, fpManual;
        let kapaliGunler = {};
        window.sistemAyarlari = {};
        let charts = { trend: null, status: null, hour: null };
        const gunIsimleri = { "1": "Pazartesi", "2": "Salı", "3": "Çarşamba", "4": "Perşembe", "5": "Cuma", "6": "Cumartesi", "0": "Pazar" };

        /**
         * Görünüm Değiştirme (Tab Mantığı)
         */
        function switchView(viewId, el) {
            // Aktif menü öğesini güncelle
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            el.classList.add('active');

            // Aktif bölümü güncelle
            document.querySelectorAll('.view-section').forEach(section => section.classList.remove('active'));
            const target = document.getElementById('view-' + viewId);
            if (target) {
                target.classList.add('active');
            }

            // Bölüme göre veri yükle
            if (viewId === 'dashboard') yukleIstatistikler();
            if (viewId === 'reservations') yukleRezervasyonlar();
            if (viewId === 'gallery') galeriListeleYukleMain();
            if (viewId === 'menu') menuYonetimiAcMain();
            if (viewId === 'settings') ayarlariAcMain();
            if (viewId === 'bulk-mail') mailGonderYukle();
        }

        document.addEventListener('DOMContentLoaded', () => {
            yukleHerSeyi();
            initFlatpickr();
        });

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
            
            fpManual = flatpickr("#m-tarih", {
                locale: "tr",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d F Y",
                minDate: "today",
                onChange: function(selectedDates, dateStr) {
                    populateManualHours(dateStr);
                }
            });
            
            // Arama inputu listener'ı
            document.getElementById('filtre-arama').addEventListener('input', (e) => {
                currentPage = 1; // Yeni aramada ilk sayfaya dön
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(yukleRezervasyonlar, 500);
            });
        }

        function populateManualHours(dateStr) {
            const saatSelect = document.getElementById('m-saat');
            saatSelect.innerHTML = '<option value="" disabled selected>Saat Seçin</option>';
            
            if (!dateStr || !window.sistemAyarlari.calisma_saatleri) return;

            const date = new Date(dateStr);
            const gun = String(date.getDay()); // 0-6
            const s = window.sistemAyarlari.calisma_saatleri[gun];

            if (!s || s.durum === 'kapali') {
                saatSelect.innerHTML = '<option value="" disabled selected>Bu gün kapalı</option>';
                return;
            }

            const [startH, startM] = s.acilis.split(':').map(Number);
            const [endH, endM] = s.kapanis.split(':').map(Number);

            let currentH = startH;
            let currentM = startM;

            const endMinutes = endH * 60 + endM;
            const compareEnd = endMinutes === 0 ? 1440 : endMinutes;

            while (true) {
                const totalMinutes = currentH * 60 + currentM;
                if (totalMinutes > compareEnd) break;

                const hh = String(currentH).padStart(2, '0');
                const mm = String(currentM).padStart(2, '0');
                const val = `${hh}:${mm}`;
                
                const displayVal = val === "24:00" ? "00:00" : val;
                saatSelect.innerHTML += `<option value="${displayVal}">${displayVal}</option>`;

                currentM += 30; // 30 dakikalık aralıklar
                if (currentM >= 60) {
                    currentH++;
                    currentM = 0;
                }
                
                if (currentH > 24) break;
            }
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
            await Promise.all([
                yukleIstatistikler(),
                yukleRezervasyonlar(),
                yukleSistemAyarlari()
            ]);
            if (btn) {
                setTimeout(() => {
                    btn.classList.remove('loading');
                }, 500);
            }
        }

        async function yukleSistemAyarlari() {
            try {
                const res = await fetch('api.php?action=settings_get');
                const json = await res.json();
                if (json.success) {
                    window.sistemAyarlari = json.data;
                }
            } catch(e) {
                console.error('Ayarlar yüklenemedi:', e);
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
                }
            } catch(err) {
                showToast('Ayarlar yüklenemedi', 'error');
            }
        }

        /**
         * Menü Yönetimi Modalını Aç ve Verileri Çek
         */
        async function menuYonetimiAc() {
            acModal('menuModal');
            try {
                const res = await fetch('api.php?action=settings_get');
                const json = await res.json();
                if (json.success) {
                    // Mevcut dosyalar (TR)
                    document.getElementById('current-menu-yemek-tr').textContent = json.data.menu_yemek ? 'Mevcut: ' + json.data.menu_yemek.split('/').pop() : 'Henüz yüklenmedi';
                    document.getElementById('current-menu-alkol-tr').textContent = json.data.menu_alkol ? 'Mevcut: ' + json.data.menu_alkol.split('/').pop() : 'Henüz yüklenmedi';
                    document.getElementById('current-menu-tatli-tr').textContent = json.data.menu_tatli ? 'Mevcut: ' + json.data.menu_tatli.split('/').pop() : 'Henüz yüklenmedi';
                    
                    // Mevcut dosyalar (EN)
                    document.getElementById('current-menu-yemek-en').textContent = json.data.menu_yemek_en ? 'Mevcut: ' + json.data.menu_yemek_en.split('/').pop() : 'Henüz yüklenmedi';
                    document.getElementById('current-menu-alkol-en').textContent = json.data.menu_alkol_en ? 'Mevcut: ' + json.data.menu_alkol_en.split('/').pop() : 'Henüz yüklenmedi';
                    document.getElementById('current-menu-tatli-en').textContent = json.data.menu_tatli_en ? 'Mevcut: ' + json.data.menu_tatli_en.split('/').pop() : 'Henüz yüklenmedi';

                    // Inputları temizle
                    ['tr', 'en'].forEach(lang => {
                        ['yemek', 'alkol', 'tatli'].forEach(type => {
                            document.getElementById(`menu-${type}-${lang}`).value = '';
                        });
                    });
                }
            } catch(err) {
                showToast('Menü bilgileri yüklenemedi', 'error');
            }
        }

        /**
         * Menüleri Kaydet
         */
        async function menuKaydetSubmit(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = "Kaydediliyor...";
            
            try {
                const formData = new FormData();
                formData.append('action', 'settings_save');
                
                // Menü Dosyaları (TR)
                const trKeys = { 'menu_yemek': 'menu-yemek-tr', 'menu_alkol': 'menu-alkol-tr', 'menu_tatli': 'menu-tatli-tr' };
                for (const [key, id] of Object.entries(trKeys)) {
                    const file = document.getElementById(id).files[0];
                    if (file) formData.append(key, file);
                }

                // Menü Dosyaları (EN)
                const enKeys = { 'menu_yemek_en': 'menu-yemek-en', 'menu_alkol_en': 'menu-alkol-en', 'menu_tatli_en': 'menu-tatli-en' };
                for (const [key, id] of Object.entries(enKeys)) {
                    const file = document.getElementById(id).files[0];
                    if (file) formData.append(key, file);
                }

                formData.append('csrf_token', csrfToken);
                
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    showToast('Menüler başarıyla güncellendi.', 'success');
                    kapatModal('menuModal');
                } else {
                    showToast(json.message, 'error');
                }
            } catch(err) {
                showToast('Bağlantı hatası.', 'error');
            }
            btn.disabled = false;
            btn.textContent = "Menüleri Kaydet";
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
         * Galeri Yönetimi İşlemleri
         */
        let galleryData = [];

        async function galeriYonetimiAc() {
            acModal('galeriModal');
            galeriListeleYukle();
        }

        async function galeriListeleYukle() {
            const container = document.getElementById('galeri-liste');
            container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:20px;">Yükleniyor...</div>';
            
            try {
                const res = await fetch('api.php?action=gallery_list');
                const json = await res.json();
                if (json.success) {
                    galleryData = json.data;
                    renderGaleri();
                }
            } catch(err) {
                showToast('Galeri listesi alınamadı.', 'error');
            }
        }

        function renderGaleri() {
            const container = document.getElementById('galeri-liste');
            if (galleryData.length === 0) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:20px; color:var(--muted);">Henüz fotoğraf eklenmedi.</div>';
                return;
            }

            container.innerHTML = galleryData.map((item, index) => `
                <div class="galeri-item-admin" data-id="${item.id}">
                    <img src="../${item.resim_yolu}" alt="Galeri">
                    <div class="controls">
                        <div class="move-btns">
                            <button class="move-btn" onclick="galeriSirala(${index}, -1)" title="Sola Taşı">←</button>
                            <button class="move-btn" onclick="galeriSirala(${index}, 1)" title="Sağa Taşı">→</button>
                        </div>
                        <span class="btn-del" onclick="galeriResimSil(${item.id})" title="Sil">🗑</span>
                    </div>
                </div>
            `).join('');
        }

        function galeriSirala(index, direction) {
            const newIndex = index + direction;
            if (newIndex >= 0 && newIndex < galleryData.length) {
                const item = galleryData.splice(index, 1)[0];
                galleryData.splice(newIndex, 0, item);
                renderGaleri();
            }
        }

        async function galeriSiralamayiKaydet() {
            const btn = document.getElementById('galeri-sirala-btn');
            btn.disabled = true;
            btn.textContent = "Kaydediliyor...";

            const order = galleryData.map(item => item.id);
            const formData = new FormData();
            formData.append('order', JSON.stringify(order));
            formData.append('csrf_token', csrfToken);

            try {
                const res = await fetch('api.php?action=gallery_reorder', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    showToast(json.message, 'success');
                }
            } catch(err) {
                showToast('Sıralama kaydedilemedi.', 'error');
            }
            btn.disabled = false;
            btn.textContent = "Sıralamayı Kaydet";
        }

        async function galeriResimYukle() {
            const input = document.getElementById('galeri-yukle-input');
            const btn = document.getElementById('galeri-yukle-btn');
            
            if (!input.files.length) {
                showToast('Lütfen en az bir resim seçin.', 'error');
                return;
            }

            btn.disabled = true;
            const files = Array.from(input.files);
            let successCount = 0;
            let errorCount = 0;

            for (let i = 0; i < files.length; i++) {
                btn.textContent = `Yükleniyor (${i+1}/${files.length})...`;
                
                const formData = new FormData();
                formData.append('image', files[i]);
                formData.append('csrf_token', csrfToken);

                try {
                    const res = await fetch('api.php?action=gallery_upload', { method: 'POST', body: formData });
                    const json = await res.json();
                    if (json.success) successCount++;
                    else errorCount++;
                } catch(err) {
                    errorCount++;
                }
            }

            if (successCount > 0) {
                showToast(`${successCount} resim başarıyla yüklendi.`, 'success');
                input.value = '';
                galeriListeleYukle();
            }
            if (errorCount > 0) {
                showToast(`${errorCount} resim yüklenemedi.`, 'error');
            }

            btn.disabled = false;
            btn.textContent = "Yükle";
        }

        async function galeriResimSil(id) {
            if (!confirm('Bu resmi galeriden silmek istediğinize emin misiniz?')) return;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('csrf_token', csrfToken);

            try {
                const res = await fetch('api.php?action=gallery_delete', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    showToast(json.message, 'success');
                    galeriListeleYukle();
                }
            } catch(err) {
                showToast('Silme işlemi başarısız.', 'error');
            }
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
        async function mailGonderYukle() {
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
                            <div class="email-item" data-search="${item.ad_soyad.toLowerCase()} ${item.email.toLowerCase()}" onclick="toggleEmailRow(this, event)">
                                <input type="checkbox" name="emails[]" value="${item.email}" id="em_${index}" onclick="event.stopPropagation(); updateSelectedCount();">
                                <label for="em_${index}">
                                    <span class="customer-name">${item.ad_soyad}</span>
                                    <span class="customer-email">${item.email}</span>
                                </label>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html;
                    updateSelectedCount();
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
                    if (source.checked) cb.parentElement.classList.add('selected');
                    else cb.parentElement.classList.remove('selected');
                }
            });
            updateSelectedCount();
        }

        function toggleEmailRow(row, event) {
            const cb = row.querySelector('input[type="checkbox"]');
            if (event.target !== cb) {
                cb.checked = !cb.checked;
            }
            if (cb.checked) row.classList.add('selected');
            else row.classList.remove('selected');
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const count = document.querySelectorAll('#customerEmailList input[type="checkbox"]:checked').length;
            document.getElementById('selectedEmailCount').textContent = count + ' Seçildi';
            
            // Eğer hepsi seçiliyse ana checkbox'ı işaretle
            const allVisible = document.querySelectorAll('#customerEmailList .email-item:not([style*="display: none"]) input[type="checkbox"]');
            const allChecked = document.querySelectorAll('#customerEmailList .email-item:not([style*="display: none"]) input[type="checkbox"]:checked');
            document.getElementById('selectAllEmails').checked = allVisible.length > 0 && allVisible.length === allChecked.length;
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
                    e.target.reset();
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

        /**
         * Galeri Ana Görünüm Listeleme
         */
        async function galeriListeleYukleMain() {
            const container = document.getElementById('galeri-liste-main');
            container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:40px;">Yükleniyor...</div>';
            
            try {
                const res = await fetch('api.php?action=gallery_list');
                const json = await res.json();
                if (json.success) {
                    galleryData = json.data;
                    renderGaleriMain();
                }
            } catch(err) {
                showToast('Galeri listesi alınamadı.', 'error');
            }
        }

        function renderGaleriMain() {
            const container = document.getElementById('galeri-liste-main');
            if (galleryData.length === 0) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:40px; color:var(--muted);">Henüz fotoğraf eklenmedi.</div>';
                return;
            }

            container.innerHTML = galleryData.map((item, index) => `
                <div class="galeri-item-admin glass-card" style="padding:10px; position:relative;">
                    <img src="../${item.resim_yolu}" style="width:100%; aspect-ratio:1; object-fit:cover; border-radius:8px;">
                    <div style="display:flex; justify-content:space-between; margin-top:10px;">
                        <div style="display:flex; gap:5px;">
                            <button class="move-btn" onclick="galeriSiralaMain(${index}, -1)" style="padding:2px 8px;">←</button>
                            <button class="move-btn" onclick="galeriSiralaMain(${index}, 1)" style="padding:2px 8px;">→</button>
                        </div>
                        <button onclick="galeriResimSilMain(${item.id})" style="background:none; border:none; color:var(--danger); cursor:pointer;">🗑 Sil</button>
                    </div>
                </div>
            `).join('');
        }

        function galeriSiralaMain(index, dir) {
            const newIndex = index + dir;
            if (newIndex >= 0 && newIndex < galleryData.length) {
                const item = galleryData.splice(index, 1)[0];
                galleryData.splice(newIndex, 0, item);
                renderGaleriMain();
            }
        }

        async function galeriResimYukleMain() {
            const input = document.getElementById('galeri-yukle-input-main');
            const btn = document.getElementById('galeri-yukle-btn-main');
            if (!input.files.length) { showToast('Lütfen resim seçin.', 'error'); return; }
            btn.disabled = true;
            const files = Array.from(input.files);
            for (let i = 0; i < files.length; i++) {
                btn.textContent = `(${i+1}/${files.length})`;
                const formData = new FormData();
                formData.append('image', files[i]);
                formData.append('csrf_token', csrfToken);
                await fetch('api.php?action=gallery_upload', { method: 'POST', body: formData });
            }
            showToast('Yükleme tamamlandı.', 'success');
            input.value = '';
            galeriListeleYukleMain();
            btn.disabled = false;
            btn.textContent = "Yükle";
        }

        async function galeriResimSilMain(id) {
            if (!confirm('Silmek istediğinize emin misiniz?')) return;
            const formData = new FormData();
            formData.append('id', id);
            formData.append('csrf_token', csrfToken);
            await fetch('api.php?action=gallery_delete', { method: 'POST', body: formData });
            galeriListeleYukleMain();
        }

        async function galeriSiralamayiKaydetMain() {
            const btn = document.getElementById('galeri-sirala-btn-main');
            btn.disabled = true;
            const order = galleryData.map(item => item.id);
            const formData = new FormData();
            formData.append('order', JSON.stringify(order));
            formData.append('csrf_token', csrfToken);
            await fetch('api.php?action=gallery_reorder', { method: 'POST', body: formData });
            showToast('Sıralama kaydedildi.', 'success');
            btn.disabled = false;
        }

        /**
         * Menü Yönetimi Ana Görünüm
         */
        async function menuYonetimiAcMain() {
            try {
                const res = await fetch('api.php?action=settings_get');
                const json = await res.json();
                if (json.success) {
                    const data = json.data;
                    document.getElementById('current-menu-yemek-tr-main').textContent = data.menu_yemek ? 'Mevcut: ' + data.menu_yemek.split('/').pop() : 'Yüklü değil';
                    document.getElementById('current-menu-alkol-tr-main').textContent = data.menu_alkol ? 'Mevcut: ' + data.menu_alkol.split('/').pop() : 'Yüklü değil';
                    document.getElementById('current-menu-tatli-tr-main').textContent = data.menu_tatli ? 'Mevcut: ' + data.menu_tatli.split('/').pop() : 'Yüklü değil';
                    
                    document.getElementById('current-menu-yemek-en-main').textContent = data.menu_yemek_en ? 'Mevcut: ' + data.menu_yemek_en.split('/').pop() : 'Yüklü değil';
                    document.getElementById('current-menu-alkol-en-main').textContent = data.menu_alkol_en ? 'Mevcut: ' + data.menu_alkol_en.split('/').pop() : 'Yüklü değil';
                    document.getElementById('current-menu-tatli-en-main').textContent = data.menu_tatli_en ? 'Mevcut: ' + data.menu_tatli_en.split('/').pop() : 'Yüklü değil';
                    
                    // Inputları temizle
                    ['yemek', 'alkol', 'tatli'].forEach(t => {
                        document.getElementById(`menu-${t}-tr-main`).value = '';
                        document.getElementById(`menu-${t}-en-main`).value = '';
                    });
                }
            } catch(e) {}
        }

        async function menuKaydetMain(e) {
            e.preventDefault();
            const btn = document.getElementById('menu-submit-btn-main');
            btn.disabled = true;
            btn.textContent = "Kaydediliyor...";

            const formData = new FormData(e.target);
            formData.append('action', 'settings_save');
            formData.append('csrf_token', csrfToken);
            
            try {
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    showToast('Menüler başarıyla güncellendi.', 'success');
                    menuYonetimiAcMain(); // İsimleri güncelle
                } else {
                    showToast(json.message || 'Hata oluştu.', 'error');
                }
            } catch(e) {
                showToast('Bağlantı hatası.', 'error');
            }
            btn.disabled = false;
            btn.textContent = "💾 Değişiklikleri Yükle ve Kaydet";
        }

        /**
         * Ayarlar Ana Görünüm
         */
        async function ayarlariAcMain() {
            try {
                const res = await fetch('api.php?action=settings_get');
                const json = await res.json();
                if (json.success) {
                    document.getElementById('kapasite_main').value = json.data.kapasite;
                    kapaliGunler = json.data.kapali_gunler || {};
                    if (Array.isArray(kapaliGunler)) kapaliGunler = {}; 
                    listeleKapaliGunlerMain();
                    renderCalismaSaatleriMain(json.data.calisma_saatleri || {});
                }
            } catch(e) {}
        }

        function listeleKapaliGunlerMain() {
            const container = document.getElementById('kapali-gunler-liste-main');
            container.innerHTML = Object.keys(kapaliGunler).map(date => `
                <div style="background:var(--surface-2); padding:8px 15px; border-radius:8px; font-size:0.85rem; display:flex; justify-content:space-between; align-items:center; border:1px solid rgba(255,255,255,0.05);">
                    <div>
                        <strong style="color:var(--accent);">${date}</strong>
                        <span style="color:var(--muted); margin-left:10px;">${kapaliGunler[date] || ''}</span>
                    </div>
                    <span onclick="kapaliGunSilMain('${date}')" style="color:var(--danger); cursor:pointer; font-weight:bold; font-size:1.2rem; padding:0 5px;">×</span>
                </div>
            `).join('') || '<div style="color:var(--muted); font-size:0.85rem; text-align:center;">Henüz kapalı gün eklenmedi.</div>';
        }

        function kapaliGunEkleMain() {
            const date = document.getElementById('yeni-kapali-gun-main').value;
            const note = document.getElementById('yeni-kapali-gun-not-main').value;
            if (!date) {
                showToast('Lütfen bir tarih seçin.', 'error');
                return;
            }
            kapaliGunler[date] = note || "Kapalı";
            listeleKapaliGunlerMain();
            
            // Temizle
            document.getElementById('yeni-kapali-gun-main').value = '';
            document.getElementById('yeni-kapali-gun-not-main').value = '';
        }

        function kapaliGunSilMain(date) {
            delete kapaliGunler[date];
            listeleKapaliGunlerMain();
        }

        function renderCalismaSaatleriMain(saatler) {
            const container = document.getElementById('calisma-saatleri-konteyner-main');
            container.innerHTML = ["1", "2", "3", "4", "5", "6", "0"].map(gun => {
                const s = saatler[gun] || { acilis: "15:00", kapanis: "00:00", durum: "acik" };
                return `
                <div class="saat-row">
                    <span class="gun-adı">${gunIsimleri[gun]}</span>
                    <div class="saat-inputs">
                        <input type="time" class="saat-input-main" data-gun="${gun}" data-type="acilis" value="${s.acilis}">
                        <input type="time" class="saat-input-main" data-gun="${gun}" data-type="kapanis" value="${s.kapanis}">
                    </div>
                    <label class="durum-checkbox">
                        <input type="checkbox" class="durum-input-main" data-gun="${gun}" ${s.durum === 'acik' ? 'checked' : ''}> Açık
                    </label>
                </div>`;
            }).join('');
        }

        async function ayarlariKaydetMain(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = "Kaydediliyor...";

            try {
                const formData = new FormData(e.target);
                formData.append('action', 'settings_save');
                formData.append('kapali_gunler', JSON.stringify(kapaliGunler));
                
                const saatler = {};
                document.querySelectorAll('.saat-input-main').forEach(input => {
                    const gun = input.dataset.gun;
                    if (!saatler[gun]) saatler[gun] = {};
                    saatler[gun][input.dataset.type] = input.value;
                    saatler[gun].durum = document.querySelector(`.durum-input-main[data-gun="${gun}"]`).checked ? 'acik' : 'kapali';
                });
                formData.append('calisma_saatleri', JSON.stringify(saatler));
                formData.append('csrf_token', csrfToken);
                
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const json = await res.json();
                
                if (json.success) {
                    showToast('Ayarlar başarıyla kaydedildi.', 'success');
                } else {
                    showToast(json.message || 'Hata oluştu.', 'error');
                }
            } catch(err) {
                showToast('Bağlantı hatası: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }

        // Modal Kontrolleri
        function acModal(id) { document.getElementById(id).style.display = 'flex'; }
        function kapatModal(id) { document.getElementById(id).style.display = 'none'; }

        // Sidebar Toggle (Mobile)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        // Sidebar linklerine tıklanınca kapat (Mobile)
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 768) toggleSidebar();
            });
        });
    </script>
</body>
</html>
