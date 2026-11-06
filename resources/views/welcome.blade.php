<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SOSPEM — Pemantauan Opini Publik & Jaringan Bot</title>

    <!-- Tipografi Tunggal: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js untuk modal analitik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ==========================================================================
           DESIGN TOKENS: Crisp White Monochrome + Cool Mint Teal (Referensi Gambar)
           Latar belakang putih bersih (#ffffff), garis border slate dingin,
           satu aksen mint/teal tenang (#10b981 / #0d9488), bebas warna terakota keruh.
           ========================================================================== */
        :root {
            --color-canvas: #ffffff;
            --color-surface: #ffffff;
            --color-surface-subtle: #f8fafc;
            --color-surface-hover: #f1f5f9;
            --color-surface-pill: #f1f5f9;

            /* Border Dingin & Bersih */
            --color-border: #e2e8f0;
            --color-border-subtle: #f1f5f9;
            --color-border-strong: #cbd5e1;

            /* Tipografi Slate Modern (Tidak Keruh/Cokelat) */
            --color-text-primary: #0f172a;
            --color-text-secondary: #475569;
            --color-text-muted: #94a3b8;

            /* Aksen Utama Sesuai Foto Referensi (Mint Teal) & Slate Netral */
            --color-teal: #10b981;
            --color-teal-dark: #059669;
            --color-teal-bg: #ecfdf5;

            --color-slate-bar: #cbd5e1;
            --color-slate-dark: #334155;
            --color-rose-muted: #e11d48;

            /* Radius & Bayangan */
            --radius-xs: 4px;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 12px;

            --shadow-card: 0 1px 2px rgba(15, 23, 42, 0.03);
            --shadow-modal: 0 20px 40px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--color-canvas);
            color: var(--color-text-primary);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
        }

        a:focus-visible, button:focus-visible, input:focus-visible {
            outline: 2px solid var(--color-text-primary);
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ==========================================================================
           APP SHELL: SIDEBAR KIRI + KONTEN UTAMA (Sesuai Foto Referensi)
           ========================================================================== */
        .app-shell {
            display: flex;
            min-height: 100vh;
            background-color: #ffffff;
        }

        /* Sidebar Kiri Ramping (Icon Rail seperti foto referensi) */
        .left-sidebar {
            width: 60px;
            border-right: 1px solid var(--color-border);
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 18px 0;
            position: sticky;
            top: 0;
            height: 100vh;
            flex-shrink: 0;
            z-index: 50;
        }

        .sidebar-brand-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background-color: #0f172a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            cursor: pointer;
            border: none;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            align-items: center;
        }

        .side-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--color-text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
            position: relative;
        }
        .side-icon-btn:hover {
            color: var(--color-text-primary);
            background-color: var(--color-surface-subtle);
        }
        .side-icon-btn.is-active {
            color: var(--color-text-primary);
            background-color: var(--color-surface-hover);
            border-color: var(--color-border);
        }

        .sidebar-bottom {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }

        .live-dot-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--color-teal);
            box-shadow: 0 0 0 3px var(--color-teal-bg);
        }

        /* Area Konten Utama */
        .main-workspace {
            flex: 1;
            min-width: 0;
            max-width: 1340px;
            margin: 0 auto;
            padding: 28px 36px 64px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .main-workspace {
                padding: 20px 16px 48px;
            }
        }

        /* Baris Judul Atas ("SOSPEM · 5 Nov 2026" seperti "Revenue · Aug 1 to Aug 31, 2026") */
        .page-title-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-heading {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-heading-muted {
            font-weight: 400;
            color: var(--color-text-secondary);
        }

        .top-right-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-clean {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--color-border);
            background: #ffffff;
            color: var(--color-text-primary);
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-clean:hover {
            background: var(--color-surface-subtle);
            border-color: var(--color-border-strong);
        }
        .btn-clean.btn-primary {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }
        .btn-clean.btn-primary:hover {
            background: #1e293b;
        }

        /* ==========================================================================
           1. ASYMMETRIC BENTO GRID (1 Primary Besar di Kiri + 3 Stack di Kanan)
           ========================================================================== */
        .kpi-bento-grid {
            display: grid;
            grid-template-columns: 1.55fr 1fr;
            gap: 14px;
            align-items: stretch;
        }
        @media (max-width: 960px) {
            .kpi-bento-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Kartu Primary Besar (Kiri) */
        .kpi-primary-card {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 26px 28px;
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 22px;
        }

        .kpi-primary-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
        }

        .kpi-primary-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--color-text-secondary);
            margin-bottom: 6px;
        }

        .kpi-primary-hero-number {
            font-size: 42px;
            font-weight: 700;
            color: var(--color-text-primary);
            letter-spacing: -0.03em;
            line-height: 1.05;
        }

        .kpi-primary-delta {
            font-size: 13px;
            color: var(--color-text-secondary);
            margin-top: 10px;
        }

        .kpi-primary-spark {
            padding-top: 10px;
            flex-shrink: 0;
        }

        .kpi-primary-divider {
            height: 1px;
            background: var(--color-border);
            width: 100%;
        }

        .kpi-primary-split {
            display: flex;
            align-items: center;
            gap: 48px;
            flex-wrap: wrap;
        }

        .kpi-split-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .kpi-split-label {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--color-text-secondary);
        }

        .kpi-split-val {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-text-primary);
            letter-spacing: -0.015em;
        }

        /* 3 Kartu Sekunder di Kanan */
        .kpi-secondary-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
            justify-content: space-between;
        }

        .kpi-compact-card {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            box-shadow: var(--shadow-card);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex: 1;
        }

        .kpi-compact-left {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }

        .kpi-compact-label {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--color-text-secondary);
        }

        .kpi-compact-val {
            font-size: 22px;
            font-weight: 700;
            color: var(--color-text-primary);
            letter-spacing: -0.02em;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .kpi-compact-delta {
            font-size: 13px;
            font-weight: 500;
            color: var(--color-text-secondary);
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* ==========================================================================
           2. GRAFIK HARIAN RINGKAS (Seperti "Daily revenue" di Foto Referensi)
           ========================================================================== */
        .daily-chart-card {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: 18px 24px;
            box-shadow: var(--shadow-card);
        }

        .daily-chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .daily-chart-title {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--color-text-primary);
        }

        .period-tabs {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .period-tab {
            font-size: 12px;
            font-weight: 500;
            padding: 3px 9px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: var(--color-text-secondary);
            cursor: pointer;
        }
        .period-tab.is-active {
            background: var(--color-surface-pill);
            color: var(--color-text-primary);
            font-weight: 600;
        }

        .daily-svg-wrap {
            width: 100%;
            overflow: hidden;
        }

        .daily-axis-labels {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            color: var(--color-text-muted);
            margin-top: 6px;
        }

        /* ==========================================================================
           3. DAFTAR ISU TERPANTAU (Seperti "Recent transactions" di Foto Referensi)
           ========================================================================== */
        .issues-table-card {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }

        .issues-card-topbar {
            padding: 16px 24px;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .issues-card-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text-primary);
        }

        .search-clean-wrap {
            position: relative;
            width: 260px;
        }
        .search-clean-input {
            width: 100%;
            padding: 6px 12px 6px 30px;
            font-size: 12.5px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            background: var(--color-surface-subtle);
            color: var(--color-text-primary);
            outline: none;
        }
        .search-clean-input:focus {
            background: #ffffff;
            border-color: var(--color-text-primary);
        }
        .search-clean-icon {
            position: absolute;
            left: 9px;
            top: 8px;
            color: var(--color-text-muted);
            pointer-events: none;
        }

        /* Baris Isu dalam Satu Kartu Bersih (Seperti baris Maya Torres, Elias Brandt) */
        .issue-item {
            border-bottom: 1px solid var(--color-border-subtle);
            transition: background-color 0.12s ease;
        }
        .issue-item:last-child {
            border-bottom: none;
        }
        .issue-item:hover {
            background-color: var(--color-surface-subtle);
        }

        .issue-row-header {
            padding: 14px 24px;
            display: grid;
            grid-template-columns: 36px minmax(240px, 1.5fr) 150px 180px 110px 80px;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            user-select: none;
        }
        @media (max-width: 1024px) {
            .issue-row-header {
                grid-template-columns: 34px 1fr auto;
            }
            .hide-on-tablet {
                display: none !important;
            }
        }

        /* Avatar Inisial Lingkaran (Seperti "MT", "EB", "PN" pada Referensi) */
        .issue-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--color-surface-pill);
            border: 1px solid var(--color-border);
            color: var(--color-text-secondary);
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .issue-item.is-lead .issue-avatar {
            background-color: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .issue-primary {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .issue-name {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--color-text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .issue-meta {
            font-size: 12px;
            color: var(--color-text-muted);
        }

        /* Stacked Bar Minimalis (Mint Teal vs Cool Slate) */
        .bar-group-box {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .bar-labels {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--color-text-secondary);
            font-weight: 500;
        }
        .bar-track {
            height: 5px;
            border-radius: 9999px;
            background: var(--color-slate-bar);
            overflow: hidden;
            display: flex;
            width: 100%;
        }
        .bar-fill-teal { background: var(--color-teal); }
        .bar-fill-slate { background: var(--color-slate-dark); }

        /* Status Badge Tren Isu */
        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11.5px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 6px;
            background-color: var(--color-surface-pill);
            color: var(--color-text-secondary);
            border: 1px solid var(--color-border);
            white-space: nowrap;
        }
        /* Naik Cepat: Paling menonjol (Hitam Slate gelap) */
        .status-pill.is-fast-up {
            background-color: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
            font-weight: 600;
        }
        /* Naik: Indikator kenaikan volume percakapan (Mint Teal terang) */
        .status-pill.is-up {
            background-color: var(--color-teal-bg);
            color: var(--color-teal-dark);
            border-color: #a7f3d0;
            font-weight: 600;
        }
        /* Stabil: Netral (Abu-abu terang tenang) */
        .status-pill.is-stable {
            background-color: var(--color-surface-pill);
            color: var(--color-text-secondary);
            border-color: var(--color-border);
        }
        /* Turun: Redup/Menurun (Abu-abu soft dengan teks muted) */
        .status-pill.is-down {
            background-color: #f8fafc;
            color: var(--color-text-muted);
            border-color: var(--color-border);
        }

        .expand-trigger {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
            font-size: 12px;
            font-weight: 500;
            color: var(--color-text-secondary);
        }
        .expand-icon {
            transition: transform 0.15s ease;
        }
        .issue-item.is-expanded .expand-icon {
            transform: rotate(180deg);
        }

        /* Expansion Panel Breakdown Platform */
        .issue-expansion-panel {
            display: none;
            border-top: 1px solid var(--color-border-subtle);
            background: var(--color-surface-subtle);
            padding: 16px 24px 20px 76px;
        }
        @media (max-width: 768px) {
            .issue-expansion-panel {
                padding: 14px 16px;
            }
        }
        .issue-item.is-expanded .issue-expansion-panel {
            display: block;
        }

        .platform-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        @media (max-width: 860px) {
            .platform-grid { grid-template-columns: 1fr; }
        }

        .platform-card {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .platform-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            color: var(--color-text-primary);
            border-bottom: 1px solid var(--color-border-subtle);
            padding-bottom: 6px;
        }

        .segment-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 8px;
            border-radius: var(--radius-xs);
            cursor: pointer;
            transition: background 0.12s;
        }
        .segment-row:hover {
            background: var(--color-surface-hover);
        }

        .segment-label {
            font-size: 12px;
            font-weight: 500;
            color: var(--color-text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dot-teal { width: 6px; height: 6px; border-radius: 50%; background: var(--color-teal); }
        .dot-slate { width: 6px; height: 6px; border-radius: 50%; background: var(--color-slate-dark); }

        .segment-metric {
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Slide-Over Feed Drill Down */
        .drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.35);
            z-index: 1000;
            display: none;
            justify-content: flex-end;
        }
        .drawer-overlay.is-active {
            display: flex;
        }
        .drawer-panel {
            background: #ffffff;
            width: 100%;
            max-width: 600px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-modal);
            border-left: 1px solid var(--color-border);
        }
        .drawer-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            background: #ffffff;
        }
        .feed-scroll-area {
            flex: 1;
            overflow-y: auto;
            padding: 18px 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: var(--color-surface-subtle);
        }
        .feed-card {
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .feed-card-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .feed-user-link {
            font-weight: 600;
            font-size: 13.5px;
            color: var(--color-text-primary);
            cursor: pointer;
            text-decoration: underline;
        }
        .feed-text {
            font-size: 13.5px;
            line-height: 1.5;
            color: var(--color-text-primary);
        }
        .feed-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--color-text-muted);
            border-top: 1px solid var(--color-border-subtle);
            padding-top: 8px;
        }

        /* Modal Dialog */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            z-index: 1200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-overlay.is-active {
            display: flex;
        }
        .modal-box {
            background: #ffffff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--color-border);
            width: 100%;
            max-width: 620px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-modal);
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 16px 20px;
            background: #ffffff;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            font-size: 13px;
            color: var(--color-text-primary);
        }

        /* Toast Notifikasi */
        .app-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: #ffffff;
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            font-size: 12.5px;
            font-weight: 500;
            display: none;
            box-shadow: var(--shadow-modal);
            z-index: 2000;
            align-items: center;
            gap: 8px;
        }
        .app-toast.is-active {
            display: flex;
        }
    </style>
</head>
<body>

    <div class="app-shell">

        <!-- SIDEBAR KIRI RAMPING (Sesuai Foto Referensi) -->
        <aside class="left-sidebar" aria-label="Navigasi Utama">
            <button class="sidebar-brand-btn" title="SOSPEM Dashboard" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                </svg>
            </button>

            <nav class="sidebar-nav">
                <button class="side-icon-btn is-active" title="Ringkasan & Matriks Isu" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                </button>

                <button class="side-icon-btn" title="Audit Jaringan Akun" onclick="openProfileModal('Sahabat Kebijakan', '@sahabat_negeri77', 96.4)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </button>

                <button class="side-icon-btn" title="Grafik Deret Waktu" onclick="openModal('modalAnalytics')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                </button>

                <button class="side-icon-btn" title="Metodologi & Kepatuhan UU PDP" onclick="openModal('modalMetodologi')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </button>
            </nav>

            <div class="sidebar-bottom">
                <span class="live-dot-indicator" title="Live Stream Aktif"></span>
            </div>
        </aside>

        <!-- AREA KONTEN UTAMA -->
        <div class="main-workspace">

            <!-- JUDUL ATAS MINIMALIS (Seperti "Revenue · Aug 1 to Aug 31, 2026") -->
            <header class="page-title-bar">
                <h1 class="page-heading">
                    <span>Opini Publik</span>
                    <span class="page-heading-muted">&middot; 1 Nov s/d 5 Nov 2026</span>
                </h1>

                <div class="top-right-actions">
                    <button class="btn-clean" onclick="openModal('modalAnalytics')">Grafik Detail</button>
                    <button class="btn-clean" onclick="openModal('modalMetodologi')">UU PDP</button>
                    <button class="btn-clean btn-primary" onclick="simulateIngest()">+ Simulasi Stream</button>
                </div>
            </header>

            <!-- 1. ASYMMETRIC BENTO GRID: 1 KARTU PRIMARY BESAR + 3 STACK DI KANAN -->
            <section class="kpi-bento-grid">

                <!-- Kartu Primary Besar (Kiri) -->
                <div class="kpi-primary-card">
                    <div class="kpi-primary-top">
                        <div>
                            <div class="kpi-primary-label">Total Sampel Terpantau</div>
                            <div class="kpi-primary-hero-number tabular" id="kpiTotalSampel">68.492</div>
                            <div class="kpi-primary-delta tabular">+14,2% vs kemarin</div>
                        </div>

                        <!-- Sparkline Mint Teal dengan Titik Ujung -->
                        <div class="kpi-primary-spark">
                            <svg width="165" height="56" viewBox="0 0 165 56" fill="none">
                                <path d="M4 44 C 18 38, 22 46, 36 36 C 48 26, 54 37, 68 28 C 80 20, 88 31, 102 20 C 116 10, 124 22, 138 14 C 146 9, 152 12, 158 6"
                                      stroke="#10b981" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                <circle cx="158" cy="6" r="3.5" fill="#10b981" />
                            </svg>
                        </div>
                    </div>

                    <div class="kpi-primary-divider"></div>

                    <!-- 2 Kolom Bawah (Seperti Subscriptions & One-time) -->
                    <div class="kpi-primary-split">
                        <div class="kpi-split-item">
                            <div class="kpi-split-label">Warga Organik</div>
                            <div class="kpi-split-val tabular">42.191</div>
                        </div>

                        <div class="kpi-split-item">
                            <div class="kpi-split-label">Jaringan Bot</div>
                            <div class="kpi-split-val tabular">26.301</div>
                        </div>
                    </div>
                </div>

                <!-- 3 Kartu Stack di Kanan (Seperti Active users, Conversion, Churn) -->
                <div class="kpi-secondary-stack">

                    <div class="kpi-compact-card">
                        <div class="kpi-compact-left">
                            <span class="kpi-compact-label">Warga aktif organik</span>
                            <span class="kpi-compact-val tabular">61,6%</span>
                        </div>
                        <div class="kpi-compact-delta tabular">+3,1%</div>
                    </div>

                    <div class="kpi-compact-card">
                        <div class="kpi-compact-left">
                            <span class="kpi-compact-label">Anomali botnet</span>
                            <span class="kpi-compact-val tabular">38,4%</span>
                        </div>
                        <div class="kpi-compact-delta tabular">+6,2 pt</div>
                    </div>

                    <div class="kpi-compact-card">
                        <div class="kpi-compact-left">
                            <span class="kpi-compact-label">Isu tertinggi (#1)</span>
                            <span class="kpi-compact-val" style="font-size: 18px;">Makan Bergizi Gratis</span>
                        </div>
                        <div class="kpi-compact-delta tabular" style="color: var(--color-teal-dark); font-weight: 600;">+42,8%</div>
                    </div>

                </div>
            </section>

            <!-- 2. GRAFIK VOLUME HARIAN (Seperti "Daily revenue" di Foto Referensi) -->
            <section class="daily-chart-card">
                <div class="daily-chart-header">
                    <span class="daily-chart-title">Tren volume percakapan harian</span>
                    <div class="period-tabs">
                        <button class="period-tab" onclick="switchPeriod(this)">24j</button>
                        <button class="period-tab is-active" onclick="switchPeriod(this)">7h</button>
                        <button class="period-tab" onclick="switchPeriod(this)">30h</button>
                    </div>
                </div>

                <div class="daily-svg-wrap">
                    <svg width="100%" height="96" viewBox="0 0 1000 96" preserveAspectRatio="none">
                        <line x1="0" y1="20" x2="1000" y2="20" stroke="#f1f5f9" stroke-width="1"/>
                        <line x1="0" y1="50" x2="1000" y2="50" stroke="#f1f5f9" stroke-width="1"/>
                        <line x1="0" y1="80" x2="1000" y2="80" stroke="#f1f5f9" stroke-width="1"/>
                        <path d="M0,74 C50,68 90,62 140,70 C190,78 230,45 280,48 C330,51 370,38 420,44 C470,50 510,28 560,32 C610,36 650,24 700,38 C750,52 790,18 840,22 C890,26 940,8 1000,12"
                              fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>

                <div class="daily-axis-labels tabular">
                    <span>1 Nov</span>
                    <span>3 Nov</span>
                    <span>5 Nov</span>
                </div>
            </section>

            <!-- 3. DAFTAR 10 ISU TERPANTAU (Seperti "Recent transactions" di Foto Referensi) -->
            <main class="issues-table-card" id="issuesList">

                <div class="issues-card-topbar">
                    <span class="issues-card-title">Isu terpantau (10 peringkat volume tertinggi)</span>
                    <div class="search-clean-wrap">
                        <svg class="search-clean-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" id="searchInput" class="search-clean-input" placeholder="Cari isu..." oninput="filterIssues()">
                    </div>
                </div>

                <!-- ISU #1: MBG -->
                <article class="issue-item is-lead is-expanded" data-title="makan bergizi gratis mbg">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">01</div>
                        <div class="issue-primary">
                            <div class="issue-name">Makan Bergizi Gratis (MBG)</div>
                            <div class="issue-meta">Isu Strategis Nasional &middot; Dominan di X &amp; Meta</div>
                        </div>

                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 61.6%</span><span>Bot 38.4%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 61.6%;"></div><div class="bar-fill-slate" style="width: 38.4%;"></div></div>
                        </div>

                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+31.4% Pos</span><span>68.6% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 31.4%;"></div><div class="bar-fill-slate" style="width: 68.6%;"></div></div>
                        </div>

                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">32.840 post</div>

                        <div style="display: flex; justify-content: flex-end;">
                            <span class="status-pill is-fast-up">▲ Naik Cepat</span>
                        </div>
                    </div>

                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>Meta (IG &amp; FB)</span><span class="tabular" style="color: var(--color-text-muted);">23.4k post</span></div>
                                <div class="segment-row" onclick="openDrill('Makan Bergizi Gratis (MBG)', 'Meta (IG & FB)', 'human', 'negative', 41.8)">
                                    <span class="segment-label"><span class="dot-teal"></span>Warga Organik</span>
                                    <span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+58.2%</span><span>/</span><span>41.8%</span></span>
                                </div>
                                <div class="segment-row" onclick="openDrill('Makan Bergizi Gratis (MBG)', 'Meta (IG & FB)', 'bot', 'positive', 89.4)">
                                    <span class="segment-label"><span class="dot-slate"></span>Jaringan Bot</span>
                                    <span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+89.4%</span><span>/</span><span>10.6%</span></span>
                                </div>
                            </div>

                            <div class="platform-card">
                                <div class="platform-header"><span>X &amp; Threads</span><span class="tabular" style="color: var(--color-text-muted);">31.2k post</span></div>
                                <div class="segment-row" onclick="openDrill('Makan Bergizi Gratis (MBG)', 'X / Threads', 'human', 'negative', 68.6)">
                                    <span class="segment-label"><span class="dot-teal"></span>Warga Organik</span>
                                    <span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+31.4%</span><span>/</span><span>68.6%</span></span>
                                </div>
                                <div class="segment-row" onclick="openDrill('Makan Bergizi Gratis (MBG)', 'X / Threads', 'bot', 'positive', 84.1)">
                                    <span class="segment-label"><span class="dot-slate"></span>Jaringan Bot</span>
                                    <span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+84.1%</span><span>/</span><span>15.9%</span></span>
                                </div>
                            </div>

                            <div class="platform-card">
                                <div class="platform-header"><span>TikTok &amp; YouTube</span><span class="tabular" style="color: var(--color-text-muted);">13.8k post</span></div>
                                <div class="segment-row" onclick="openDrill('Makan Bergizi Gratis (MBG)', 'TikTok / YouTube', 'human', 'positive', 62.5)">
                                    <span class="segment-label"><span class="dot-teal"></span>Warga Organik</span>
                                    <span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+62.5%</span><span>/</span><span>37.5%</span></span>
                                </div>
                                <div class="segment-row" onclick="openDrill('Makan Bergizi Gratis (MBG)', 'TikTok / YouTube', 'bot', 'positive', 91.2)">
                                    <span class="segment-label"><span class="dot-slate"></span>Jaringan Bot</span>
                                    <span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+91.2%</span><span>/</span><span>8.8%</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ISU #2 -->
                <article class="issue-item" data-title="kabur aja dulu">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">02</div>
                        <div class="issue-primary">
                            <div class="issue-name">kabur aja dulu</div>
                            <div class="issue-meta">Ketenagakerjaan &amp; Pajak &middot; Dominan di X</div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 78.2%</span><span>Bot 21.8%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 78.2%;"></div><div class="bar-fill-slate" style="width: 21.8%;"></div></div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+14.3% Pos</span><span>85.7% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 14.3%;"></div><div class="bar-fill-slate" style="width: 85.7%;"></div></div>
                        </div>
                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">18.420 post</div>
                        <div style="display: flex; justify-content: flex-end;"><span class="status-pill is-down">▼ Turun</span></div>
                    </div>
                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>Meta (IG / FB)</span></div>
                                <div class="segment-row" onclick="openDrill('kabur aja dulu', 'Meta (IG & FB)', 'human', 'negative', 74.8)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+25.2%</span><span>/</span><span>74.8%</span></span></div>
                            </div>
                            <div class="platform-card">
                                <div class="platform-header"><span>X &amp; Threads</span></div>
                                <div class="segment-row" onclick="openDrill('kabur aja dulu', 'X / Threads', 'human', 'negative', 85.7)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+14.3%</span><span>/</span><span>85.7%</span></span></div>
                            </div>
                            <div class="platform-card">
                                <div class="platform-header"><span>TikTok &amp; YouTube</span></div>
                                <div class="segment-row" onclick="openDrill('kabur aja dulu', 'TikTok / YouTube', 'human', 'negative', 70.3)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+29.7%</span><span>/</span><span>70.3%</span></span></div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ISU #3 -->
                <article class="issue-item" data-title="gas alam">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">03</div>
                        <div class="issue-primary">
                            <div class="issue-name">gas alam</div>
                            <div class="issue-meta">Pasokan &amp; Distribusi Elpiji 3kg</div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 68.4%</span><span>Bot 31.6%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 68.4%;"></div><div class="bar-fill-slate" style="width: 31.6%;"></div></div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+21.1% Pos</span><span>78.9% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 21.1%;"></div><div class="bar-fill-slate" style="width: 78.9%;"></div></div>
                        </div>
                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">14.180 post</div>
                        <div style="display: flex; justify-content: flex-end;"><span class="status-pill is-up">▲ Naik</span></div>
                    </div>
                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>Meta (IG / FB)</span></div>
                                <div class="segment-row" onclick="openDrill('gas alam', 'Meta (IG & FB)', 'human', 'negative', 65.8)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+34.2%</span><span>/</span><span>65.8%</span></span></div>
                            </div>
                            <div class="platform-card">
                                <div class="platform-header"><span>X &amp; Threads</span></div>
                                <div class="segment-row" onclick="openDrill('gas alam', 'X / Threads', 'human', 'negative', 78.9)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+21.1%</span><span>/</span><span>78.9%</span></span></div>
                            </div>
                            <div class="platform-card">
                                <div class="platform-header"><span>TikTok &amp; YouTube</span></div>
                                <div class="segment-row" onclick="openDrill('gas alam', 'TikTok / YouTube', 'human', 'negative', 72.1)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+27.9%</span><span>/</span><span>72.1%</span></span></div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ISU #4 -->
                <article class="issue-item" data-title="karhutla">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">04</div>
                        <div class="issue-primary">
                            <div class="issue-name">Karhutla</div>
                            <div class="issue-meta">Titik Api Riau &amp; Jambi</div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 72.0%</span><span>Bot 28.0%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 72%;"></div><div class="bar-fill-slate" style="width: 28%;"></div></div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+7.6% Pos</span><span>92.4% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 7.6%;"></div><div class="bar-fill-slate" style="width: 92.4%;"></div></div>
                        </div>
                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">8.940 post</div>
                        <div style="display: flex; justify-content: flex-end;"><span class="status-pill is-up">▲ Naik</span></div>
                    </div>
                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>X &amp; Threads</span></div>
                                <div class="segment-row" onclick="openDrill('Karhutla', 'X / Threads', 'human', 'negative', 92.4)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+7.6%</span><span>/</span><span>92.4%</span></span></div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ISU #5 -->
                <article class="issue-item" data-title="kopdes">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">05</div>
                        <div class="issue-primary">
                            <div class="issue-name">kopdes</div>
                            <div class="issue-meta">Koperasi Pedesaan Pangan</div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 54.0%</span><span>Bot 46.0%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 54%;"></div><div class="bar-fill-slate" style="width: 46%;"></div></div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+63.8% Pos</span><span>36.2% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 63.8%;"></div><div class="bar-fill-slate" style="width: 36.2%;"></div></div>
                        </div>
                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">3.120 post</div>
                        <div style="display: flex; justify-content: flex-end;"><span class="status-pill is-stable">━ Stabil</span></div>
                    </div>
                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>Meta (IG / FB)</span></div>
                                <div class="segment-row" onclick="openDrill('kopdes', 'Meta (IG & FB)', 'human', 'positive', 63.8)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+63.8%</span><span>/</span><span>36.2%</span></span></div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ISU #6 -->
                <article class="issue-item" data-title="subsidi pupuk">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">06</div>
                        <div class="issue-primary">
                            <div class="issue-name">subsidi pupuk</div>
                            <div class="issue-meta">Alokasi Petani Daerah</div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 82.0%</span><span>Bot 18.0%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 82%;"></div><div class="bar-fill-slate" style="width: 18%;"></div></div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+26.8% Pos</span><span>73.2% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 26.8%;"></div><div class="bar-fill-slate" style="width: 73.2%;"></div></div>
                        </div>
                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">2.840 post</div>
                        <div style="display: flex; justify-content: flex-end;"><span class="status-pill is-stable">━ Stabil</span></div>
                    </div>
                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>X &amp; Threads</span></div>
                                <div class="segment-row" onclick="openDrill('subsidi pupuk', 'X / Threads', 'human', 'negative', 73.2)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+26.8%</span><span>/</span><span>73.2%</span></span></div>
                            </div>
                            <div class="platform-card">
                                <div class="platform-header"><span>TikTok &amp; YouTube</span></div>
                                <div style="font-size: 12px; color: var(--color-text-muted); padding: 6px 0;">Belum cukup data untuk platform ini (&lt;45 post)</div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ISU #7 -->
                <article class="issue-item" data-title="polusi udara jkt">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">07</div>
                        <div class="issue-primary">
                            <div class="issue-name">polusi udara jkt</div>
                            <div class="issue-meta">ISPU Jabodetabek</div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 88.0%</span><span>Bot 12.0%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 88%;"></div><div class="bar-fill-slate" style="width: 12%;"></div></div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+11.2% Pos</span><span>88.8% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 11.2%;"></div><div class="bar-fill-slate" style="width: 88.8%;"></div></div>
                        </div>
                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">2.410 post</div>
                        <div style="display: flex; justify-content: flex-end;"><span class="status-pill is-up">▲ Naik</span></div>
                    </div>
                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>X &amp; Threads</span></div>
                                <div class="segment-row" onclick="openDrill('polusi udara jkt', 'X / Threads', 'human', 'negative', 88.8)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+11.2%</span><span>/</span><span>88.8%</span></span></div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ISU #8 -->
                <article class="issue-item" data-title="kenaikan pbb">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">08</div>
                        <div class="issue-primary">
                            <div class="issue-name">kenaikan pbb</div>
                            <div class="issue-meta">Pajak Bumi &amp; Bangunan Daerah</div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 92.0%</span><span>Bot 8.0%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 92%;"></div><div class="bar-fill-slate" style="width: 8%;"></div></div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+18.4% Pos</span><span>81.6% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 18.4%;"></div><div class="bar-fill-slate" style="width: 81.6%;"></div></div>
                        </div>
                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">1.920 post</div>
                        <div style="display: flex; justify-content: flex-end;"><span class="status-pill is-down">▼ Turun</span></div>
                    </div>
                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>Meta (IG / FB)</span></div>
                                <div class="segment-row" onclick="openDrill('kenaikan pbb', 'Meta (IG & FB)', 'human', 'negative', 81.6)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+18.4%</span><span>/</span><span>81.6%</span></span></div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ISU #9 -->
                <article class="issue-item" data-title="jalan rusak lpg">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">09</div>
                        <div class="issue-primary">
                            <div class="issue-name">jalan rusak lpg</div>
                            <div class="issue-meta">Infrastruktur Jalan Daerah</div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 94.0%</span><span>Bot 6.0%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 94%;"></div><div class="bar-fill-slate" style="width: 6%;"></div></div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+8.9% Pos</span><span>91.1% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 8.9%;"></div><div class="bar-fill-slate" style="width: 91.1%;"></div></div>
                        </div>
                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">1.450 post</div>
                        <div style="display: flex; justify-content: flex-end;"><span class="status-pill is-stable">━ Stabil</span></div>
                    </div>
                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>TikTok &amp; YouTube</span></div>
                                <div class="segment-row" onclick="openDrill('jalan rusak lpg', 'TikTok / YouTube', 'human', 'negative', 91.1)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+8.9%</span><span>/</span><span>91.1%</span></span></div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ISU #10 -->
                <article class="issue-item" data-title="iuran tapera">
                    <div class="issue-row-header" onclick="toggleExpand(this)">
                        <div class="issue-avatar tabular">10</div>
                        <div class="issue-primary">
                            <div class="issue-name">iuran tapera</div>
                            <div class="issue-meta">Potongan Gaji &amp; Tabungan</div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>Warga 85.0%</span><span>Bot 15.0%</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 85%;"></div><div class="bar-fill-slate" style="width: 15%;"></div></div>
                        </div>
                        <div class="bar-group-box hide-on-tablet">
                            <div class="bar-labels"><span>+12.6% Pos</span><span>87.4% Neg</span></div>
                            <div class="bar-track"><div class="bar-fill-teal" style="width: 12.6%;"></div><div class="bar-fill-slate" style="width: 87.4%;"></div></div>
                        </div>
                        <div class="tabular" style="font-weight: 600; font-size: 13.5px; text-align: right;">1.180 post</div>
                        <div style="display: flex; justify-content: flex-end;"><span class="status-pill is-down">▼ Turun</span></div>
                    </div>
                    <div class="issue-expansion-panel">
                        <div class="platform-grid">
                            <div class="platform-card">
                                <div class="platform-header"><span>X &amp; Threads</span></div>
                                <div class="segment-row" onclick="openDrill('iuran tapera', 'X / Threads', 'human', 'negative', 87.4)"><span class="segment-label"><span class="dot-teal"></span>Warga Organik</span><span class="segment-metric tabular"><span style="color: var(--color-teal-dark);">+12.6%</span><span>/</span><span>87.4%</span></span></div>
                            </div>
                        </div>
                    </div>
                </article>

            </main>

        </div>
    </div>

    <!-- SLIDE-OVER FEED DRILL DOWN -->
    <div class="drawer-overlay" id="drillDrawer" onclick="handleDrawerBackdrop(event)">
        <div class="drawer-panel">
            <div class="drawer-header">
                <div>
                    <div style="font-size: 11.5px; font-weight: 500; color: var(--color-text-muted);">Inspeksi Sampel Feed</div>
                    <div style="font-size: 16px; font-weight: 700; color: var(--color-text-primary); margin-top: 2px;" id="drillTitle">Makan Bergizi Gratis (MBG) &middot; X</div>
                    <div style="font-size: 12.5px; color: var(--color-text-secondary); margin-top: 2px;" id="drillSubtitle">Segmentasi: Warga Organik &middot; Sentimen Negatif (68.6%)</div>
                </div>
                <button class="btn-clean" onclick="closeDrawer()">✕ Tutup</button>
            </div>

            <div style="padding: 10px 24px; background: #ffffff; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 6px;">
                    <button class="btn-clean" style="padding: 4px 10px; font-size: 12px;" onclick="renderFilteredFeed('all')">Semua</button>
                    <button class="btn-clean" style="padding: 4px 10px; font-size: 12px;" onclick="renderFilteredFeed('positive')">Positif</button>
                    <button class="btn-clean" style="padding: 4px 10px; font-size: 12px;" onclick="renderFilteredFeed('negative')">Negatif</button>
                </div>
                <button class="btn-clean btn-primary" style="font-size: 12px; padding: 4px 10px;" onclick="injectLiveFeedItem()">+ Uji 1 Post Live</button>
            </div>

            <div class="feed-scroll-area" id="feedListContainer"></div>
        </div>
    </div>

    <!-- MODAL AUDIT AKUN (UU PDP) -->
    <div class="modal-overlay" id="modalProfile" onclick="handleModalBackdrop(event, 'modalProfile')">
        <div class="modal-box">
            <div class="modal-header">
                <div style="font-weight: 600; font-size: 14px;">Audit Profil Jaringan &amp; Perilaku Akun</div>
                <button class="btn-clean" onclick="closeModal('modalProfile')">✕</button>
            </div>
            <div class="modal-body">
                <div style="background: var(--color-surface-subtle); border-left: 3px solid var(--color-teal); padding: 10px 12px; font-size: 12px; color: var(--color-text-secondary); border-radius: var(--radius-xs);">
                    <strong>Kepatuhan UU PDP No. 27/2022:</strong> Sistem hanya memantau data publik terbuka dan berfokus pada <strong>audit perilaku bot terkoordinasi</strong>, bukan menargetkan privasi warga biasa.
                </div>

                <div style="display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1px solid var(--color-border-subtle); padding-bottom: 10px;">
                    <div>
                        <div style="font-size: 16px; font-weight: 700;" id="profName">Sahabat Kebijakan</div>
                        <div class="tabular" style="font-size: 12px; color: var(--color-text-muted);" id="profHandle">@sahabat_negeri77 &middot; X (Twitter)</div>
                    </div>
                    <span class="status-pill is-hot tabular" id="profScore">Skor Bot: 96,4%</span>
                </div>

                <div>
                    <div style="font-size: 12.5px; font-weight: 600; margin-bottom: 6px;">Indikasi Perilaku Jaringan (Astroturfing):</div>
                    <ul style="margin-left: 18px; line-height: 1.6; font-size: 12.5px; color: var(--color-text-secondary);">
                        <li><strong>Teks Kembar:</strong> Mengirimkan template teks 98% identik dengan 24 akun lain dalam 90 detik.</li>
                        <li><strong>Kecepatan Postingan:</strong> Frekuensi mencapai 14 tweet/menit saat kampanye narasi.</li>
                        <li><strong>Metadata Anomali:</strong> Akun baru berusia 34 hari dengan 1.890 following vs 11 follower.</li>
                    </ul>
                </div>

                <div>
                    <div style="font-size: 12.5px; font-weight: 600; margin-bottom: 6px;">Akun Lain dalam Kluster Serupa:</div>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        <span class="status-pill tabular">@rakyat_bersatu_id</span>
                        <span class="status-pill tabular">@indonesia_maju2026</span>
                        <span class="status-pill tabular">@fakta_bicara24</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL GRAFIK ANALITIK -->
    <div class="modal-overlay" id="modalAnalytics" onclick="handleModalBackdrop(event, 'modalAnalytics')">
        <div class="modal-box" style="max-width: 820px;">
            <div class="modal-header">
                <div style="font-weight: 600; font-size: 14px;">Grafik Deret Waktu &amp; Proporsi Platform</div>
                <button class="btn-clean" onclick="closeModal('modalAnalytics')">✕</button>
            </div>
            <div class="modal-body">
                <div>
                    <div style="font-size: 12.5px; font-weight: 600; margin-bottom: 6px;">Volume Postingan per Jam (Warga Organik vs Bot)</div>
                    <div style="height: 200px;"><canvas id="chartLineVolume"></canvas></div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 8px;">
                    <div>
                        <div style="font-size: 12.5px; font-weight: 600; margin-bottom: 6px;">Proporsi Platform</div>
                        <div style="height: 160px;"><canvas id="chartDoughnutPlatform"></canvas></div>
                    </div>
                    <div>
                        <div style="font-size: 12.5px; font-weight: 600; margin-bottom: 6px;">Sentimen Warga vs Bot</div>
                        <div style="height: 160px;"><canvas id="chartBarCompare"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL METODOLOGI -->
    <div class="modal-overlay" id="modalMetodologi" onclick="handleModalBackdrop(event, 'modalMetodologi')">
        <div class="modal-box">
            <div class="modal-header">
                <div style="font-weight: 600; font-size: 14px;">Metodologi Analisis &amp; Kepatuhan UU PDP</div>
                <button class="btn-clean" onclick="closeModal('modalMetodologi')">✕</button>
            </div>
            <div class="modal-body" style="line-height: 1.6; color: var(--color-text-secondary);">
                <p><strong style="color: var(--color-text-primary);">1. Klasifikasi Sentimen:</strong> Menggunakan model IndoBERT yang dilatih pada korpus opini kebijakan publik dengan F1-Score 91.2%.</p>
                <p><strong style="color: var(--color-text-primary);">2. Deteksi Jaringan Bot:</strong> Dihitung dari 4 parameter heuristik: kesamaan teks (min-hash), jeda antar-postingan, rasio follower/following, dan usia akun.</p>
                <p><strong style="color: var(--color-text-primary);">3. Kepatuhan UU PDP No. 27/2022:</strong> Seluruh data warga biasa dianalisis secara agregat tanpa pengumpulan data privasi personal.</p>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFIKASI -->
    <div class="app-toast" id="toastNotif">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <span id="toastText">Data stream baru berhasil diterima</span>
    </div>

    <script>
        const postRepository = {
            'Makan Bergizi Gratis (MBG)': {
                'X / Threads': {
                    'human': {
                        'negative': [
                            {
                                author: "Rizal Fauzi",
                                handle: "@rizalfauzi_econ",
                                platform: "X",
                                time: "3 mnt lalu",
                                isBot: false,
                                botScore: 4.2,
                                sentiment: "negative",
                                text: "Anggaran MBG tembus ratusan triliun per tahun, tapi pos riset kampus malah kena efisiensi. Skala prioritas fiskal kita perlu diaudit transparan.",
                                likes: 2410,
                                reposts: 780,
                                url: "https://x.com"
                            },
                            {
                                author: "Nadia Amalia",
                                handle: "@nadiamalia",
                                platform: "X",
                                time: "8 mnt lalu",
                                isBot: false,
                                botScore: 8.1,
                                sentiment: "negative",
                                text: "Tadi dapat kiriman foto menu MBG di salah satu SDN kabupaten: porsinya cuma nasi sedikit sama tempe orek satu iris. Padahal alokasinya Rp 15rb/anak. Pengawasannya di mana?",
                                likes: 4190,
                                reposts: 1620,
                                url: "https://x.com"
                            }
                        ],
                        'positive': [
                            {
                                author: "dr. Tirta Sari, Sp.GK",
                                handle: "@gizi_keluarga",
                                platform: "X",
                                time: "15 mnt lalu",
                                isBot: false,
                                botScore: 3.5,
                                sentiment: "positive",
                                text: "Secara konsep gizi klinis, intervensi protein hewani harian lewat MBG ini terbukti ilmiah menekan stunting asalkan gramasi mikronutriennya dipantau ketat.",
                                likes: 820,
                                reposts: 190,
                                url: "https://x.com"
                            }
                        ]
                    },
                    'bot': {
                        'positive': [
                            {
                                author: "Sahabat Kebijakan",
                                handle: "@sahabat_negeri77",
                                platform: "X",
                                time: "1 mnt lalu",
                                isBot: true,
                                botScore: 96.4,
                                sentiment: "positive",
                                text: "Makan Bergizi Gratis (MBG) adalah investasi peradaban terbesar bangsa! Anak Indonesia siap menyongsong Indonesia Emas 2045! #MBGUntukBangsa #GenerasiSehat",
                                likes: 4120,
                                reposts: 2500,
                                url: "https://x.com"
                            }
                        ]
                    }
                }
            }
        };

        let activeContext = {
            topic: 'Makan Bergizi Gratis (MBG)',
            platform: 'X / Threads',
            actor: 'human',
            sentiment: 'negative',
            pct: 68.6
        };

        function toggleExpand(rowElement) {
            const card = rowElement.closest('.issue-item');
            card.classList.toggle('is-expanded');
        }

        function switchPeriod(btn) {
            btn.parentElement.querySelectorAll('.period-tab').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
        }

        function openDrill(topic, platform, actor, sentiment, pct) {
            activeContext = { topic, platform, actor, sentiment, pct };
            const actorLabel = actor === 'bot' ? 'Jaringan Bot' : 'Warga Organik';
            const sentLabel = sentiment === 'positive' ? 'Sentimen Positif' : 'Sentimen Negatif';

            document.getElementById('drillTitle').innerText = `${topic} · ${platform}`;
            document.getElementById('drillSubtitle').innerText = `Segmentasi: ${actorLabel} · ${sentLabel} (${pct}%)`;

            renderFilteredFeed(sentiment);
            document.getElementById('drillDrawer').classList.add('is-active');
        }

        function closeDrawer() {
            document.getElementById('drillDrawer').classList.remove('is-active');
        }

        function handleDrawerBackdrop(e) {
            if (e.target.id === 'drillDrawer') closeDrawer();
        }

        function renderFilteredFeed(filterType = 'all') {
            const container = document.getElementById('feedListContainer');
            container.innerHTML = '';

            let posts = [];
            const t = activeContext.topic;
            const p = activeContext.platform;
            const a = activeContext.actor;

            if (postRepository[t] && postRepository[t][p] && postRepository[t][p][a]) {
                const node = postRepository[t][p][a];
                if (filterType === 'all') {
                    posts = [...(node.positive || []), ...(node.negative || [])];
                } else if (filterType === 'positive') {
                    posts = node.positive || [];
                } else {
                    posts = node.negative || [];
                }
            }

            if (posts.length === 0) {
                const isB = a === 'bot';
                const isP = filterType === 'positive';
                posts = [
                    {
                        author: isB ? "Akun Bot #" + Math.floor(Math.random() * 800 + 100) : "Warga Netizen",
                        handle: isB ? "@buzzer_amplif_" + Math.floor(Math.random() * 900) : "@suara_warga_id",
                        platform: p,
                        time: "4 mnt lalu",
                        isBot: isB,
                        botScore: isB ? 96.4 : 6.1,
                        sentiment: isP ? 'positive' : 'negative',
                        text: isP
                            ? `Terkait isu ${t}, pelaksanaan di lapangan patut diapresiasi karena terbukti membantu meringankan beban masyarakat secara terukur.`
                            : `Penyaluran dan pengawasan isu ${t} di daerah masih dikeluhkan warga. Mohon kementerian terkait segera turun ke lapangan.`,
                        likes: isB ? 1420 : 340,
                        reposts: isB ? 620 : 88,
                        url: "https://x.com"
                    }
                ];
            }

            posts.forEach(post => {
                const card = document.createElement('div');
                card.className = 'feed-card';

                const botBadge = post.isBot
                    ? `<span class="status-pill is-hot tabular">Bot ${post.botScore}%</span>`
                    : `<span class="status-pill is-teal tabular">Warga Asli</span>`;

                const sentBadge = post.sentiment === 'positive'
                    ? `<span style="color: var(--color-teal-dark); font-weight: 600; font-size: 11.5px;">+ Positif</span>`
                    : `<span style="color: var(--color-text-secondary); font-weight: 600; font-size: 11.5px;">- Negatif</span>`;

                card.innerHTML = `
                    <div class="feed-card-header">
                        <div>
                            <span class="feed-user-link" onclick="openProfileModal('${post.author}', '${post.handle}', ${post.botScore})">${post.author}</span>
                            <span class="tabular" style="font-size: 12px; color: var(--color-text-muted);">(${post.handle} &middot; ${post.platform})</span>
                        </div>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            ${botBadge}
                            ${sentBadge}
                        </div>
                    </div>
                    <div class="feed-text">${post.text}</div>
                    <div class="feed-card-footer">
                        <span>${post.likes.toLocaleString()} suka &middot; ${post.reposts.toLocaleString()} repost &middot; ${post.time}</span>
                        <a href="${post.url}" target="_blank" style="color: var(--color-text-primary); font-weight: 500; text-decoration: underline;">Buka di ${post.platform} ↗</a>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function injectLiveFeedItem() {
            const container = document.getElementById('feedListContainer');
            const card = document.createElement('div');
            card.className = 'feed-card';
            card.style.borderColor = 'var(--color-teal)';

            card.innerHTML = `
                <div class="feed-card-header">
                    <div>
                        <span style="font-weight: 600;">Akun Live Masuk</span>
                        <span class="tabular" style="font-size: 12px; color: var(--color-text-muted);">(@live_stream &middot; ${activeContext.platform})</span>
                    </div>
                    <span class="status-pill is-teal tabular">Warga Asli</span>
                </div>
                <div class="feed-text">[DATA LIVE] Masukan warga mengenai ${activeContext.topic}: menu hari ini sudah mulai ada variasi buah segar di beberapa sekolah.</div>
                <div class="feed-card-footer">
                    <span>1 suka &middot; Baru diterima</span>
                    <a href="https://x.com" target="_blank" style="color: var(--color-text-primary); font-weight: 500;">Buka postingan asli ↗</a>
                </div>
            `;
            container.insertBefore(card, container.firstChild);
            showToast("1 data live baru ditambahkan ke feed");
            incrementCount();
        }

        function simulateIngest() {
            showToast("Data baru diterima via stream websocket");
            incrementCount();
        }

        function incrementCount() {
            const el = document.getElementById('kpiTotalSampel');
            let n = parseInt(el.innerText.replace(/\./g, '')) + 1;
            el.innerText = n.toLocaleString('id-ID');
        }

        function showToast(msg) {
            const t = document.getElementById('toastNotif');
            document.getElementById('toastText').innerText = msg;
            t.classList.add('is-active');
            setTimeout(() => { t.classList.remove('is-active'); }, 2500);
        }

        function openModal(id) {
            document.getElementById(id).classList.add('is-active');
            if (id === 'modalAnalytics') initAnalyticsCharts();
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('is-active');
        }

        function handleModalBackdrop(e, id) {
            if (e.target.id === id) closeModal(id);
        }

        function openProfileModal(name, handle, score) {
            document.getElementById('profName').innerText = name;
            document.getElementById('profHandle').innerText = `${handle} · Platform Terpantau`;
            document.getElementById('profScore').innerText = `Skor Bot: ${score}%`;
            openModal('modalProfile');
        }

        let chartsLoaded = false;
        function initAnalyticsCharts() {
            if (chartsLoaded) return;
            chartsLoaded = true;

            new Chart(document.getElementById('chartLineVolume'), {
                type: 'line',
                data: {
                    labels: ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00'],
                    datasets: [
                        { label: 'Warga Organik', data: [420, 280, 1100, 4900, 7800, 6400, 7100], borderColor: '#10b981', tension: 0.35 },
                        { label: 'Anomali Botnet', data: [60, 40, 290, 2100, 3800, 1400, 2300], borderColor: '#475569', borderDash: [4, 4], tension: 0.35 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            new Chart(document.getElementById('chartDoughnutPlatform'), {
                type: 'doughnut',
                data: {
                    labels: ['Meta (IG & FB)', 'X & Threads', 'TikTok & YouTube'],
                    datasets: [{ data: [23400, 31200, 13800], backgroundColor: ['#0f172a', '#10b981', '#94a3b8'] }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            new Chart(document.getElementById('chartBarCompare'), {
                type: 'bar',
                data: {
                    labels: ['Warga Asli', 'Jaringan Bot'],
                    datasets: [
                        { label: 'Positif', data: [31.4, 84.1], backgroundColor: '#10b981' },
                        { label: 'Negatif', data: [68.6, 15.9], backgroundColor: '#334155' }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { max: 100 } } }
            });
        }

        function filterIssues() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            const items = document.querySelectorAll('#issuesList .issue-item');
            items.forEach(item => {
                const title = item.getAttribute('data-title') || '';
                item.style.display = title.includes(q) ? '' : 'none';
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeDrawer();
                closeModal('modalProfile');
                closeModal('modalAnalytics');
                closeModal('modalMetodologi');
            }
        });
    </script>
</body>
</html>
