<?php
// laporan.php - disesuaikan agar light-mode sama persis dengan bahan_baku.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Dummy classes fallback (jika belum ada)
$logStok = new LogStok();
$bahanBaku = new BahanBaku();

// filters
$filter_bahan = $_GET['filter_bahan'] ?? '';
$filter_jenis = $_GET['filter_jenis'] ?? '';
$filter_tanggal = $_GET['filter_tanggal'] ?? '';

$logs = $logStok->getAll();
$bahans = $bahanBaku->getAll();

if (empty($logs)) {
    $logs = [
        [
            'created_at' => '2025-11-19 13:32:00',
            'nama_bahan' => 'Telur',
            'jenis_transaksi' => 'masuk',
            'jumlah' => 30.00,
            'stok_sebelum' => 50.00,
            'stok_sesudah' => 80.00,
            'nama_user' => 'Shofa Owner',
            'keterangan' => 'Pesanan Banyak',
            'id_bahan' => '1'
        ],
    ];
    $bahans = [
        ['id' => '1', 'nama_bahan' => 'Telur'],
        ['id' => '2', 'nama_bahan' => 'Gula'],
    ];
}

if ($filter_bahan) {
    $logs = array_filter($logs, fn($log) => ($log['id_bahan'] ?? '') == $filter_bahan);
}
if ($filter_jenis) {
    $logs = array_filter($logs, fn($log) => ($log['jenis_transaksi'] ?? '') == $filter_jenis);
}
if ($filter_tanggal) {
    $logs = array_filter($logs, fn($log) => date('Y-m-d', strtotime($log['created_at'])) == $filter_tanggal);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Laporan Stok - HappyFood Inventory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{
      --accent:#6f42c1;
      --accent-2:#7c4dff;
      --muted:#6c757d;
      --page-bg:#f6f7fb;
      --card-bg:#ffffff;
      --sidebar-bg:#ffffff;
      --sidebar-ink:#2b2b3b;
      --soft-border:#eef0f4;
    }
    html,body{height:100%; min-height:100%; margin:0; font-family:"Inter","Raleway",system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial; background:var(--page-bg); color:#222;}
    body.dark-mode{
      --page-bg: #0f172a;
      --card-bg: #1a2334;
      --sidebar-bg: #1a2334;
      --sidebar-ink: #e6e9ff;
      --muted: #9aa0b4;
      --soft-border: rgba(255,255,255,0.06);
      background: var(--page-bg);
      color: #e6e9ff;
    }
    .container-fluid { background: transparent; min-height:100vh; padding:0 16px; box-sizing:border-box; }
    .app { min-height:100vh; display:flex; gap:22px; padding:22px; box-sizing:border-box; position:relative; background: transparent; }
    body.dark-mode .container-fluid,
    body.dark-mode .app { background: var(--page-bg) !important; color: inherit; }
    body.dark-mode .sidebar { box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important; }
    body.dark-mode .nav-vertical a.active,
    body.dark-mode .nav-vertical a:hover {
      background: rgba(111,66,193,0.12) !important;
      box-shadow: none !important;
    }
    body.dark-mode .sidebar .tools { border-top: 1px solid var(--soft-border); }
    body.dark-mode .switch { background: #3b455b; }
    body.dark-mode .switch.on { background: linear-gradient(135deg, var(--accent), var(--accent-2)); }
    body.dark-mode .switch .knob { background: #e6e9ff; }
    body.dark-mode .logout-btn { background: #243142; color: #e6e9ff; }
    body.dark-mode .header-card { background: var(--card-bg); border: 1px solid var(--soft-border); box-shadow:none; }
    body.dark-mode .stat { background: #1a2334; border:1px solid var(--soft-border); box-shadow:none; }
    body.dark-mode .stat .value { color:#e6e9ff !important; }
    
    .sidebar {
      width:220px;
      min-width:220px;
      background:var(--sidebar-bg);
      color:var(--sidebar-ink);
      padding:18px;
      border-radius:0 14px 14px 0;
      box-shadow: 0 10px 30px rgba(20,20,40,0.04);
      display:flex;
      flex-direction:column;
      height:100vh;
      box-sizing:border-box;
      transition: width .18s ease, padding .18s ease, border-radius .18s ease;
      overflow:hidden;
    }
    .sidebar.collapsed { width:60px; min-width:60px; padding:10px 0; border-radius:0 12px 12px 0; }
    .brand { display:flex; align-items:center; gap:10px; margin-bottom:8px; transition: opacity .12s; padding:0 10px; }
    .brand .logo { width:36px;height:36px;border-radius:8px; display:flex;align-items:center;justify-content:center;font-weight:700; background: transparent; box-shadow: 0 6px 18px rgba(99,63,171,0.06); overflow:hidden; }
    .brand h5{ margin:0; font-size:15px; font-weight:700; color:var(--sidebar-ink); }
    .brand small{ display:block; font-size:12px; color:var(--muted); }
    .brand-text { display:block; }
    .logo-img { width:100%; height:100%; object-fit:contain; display:block; }
    .sidebar.collapsed .brand h5, .sidebar.collapsed .brand small { display:none; }
    .sidebar.collapsed .brand { justify-content:center; margin-bottom:14px; padding:0; }
    .sidebar.collapsed .brand-text { display:none !important; }
    .nav-vertical { display:flex; flex-direction:column; gap:12px; margin-top:14px; flex:1; justify-content:flex-start; padding: 0 4px; }
    .nav-vertical a { color:var(--sidebar-ink); text-decoration:none; padding:8px 12px; border-radius:10px; display:flex; gap:10px; align-items:center; font-weight:600; transition:all .12s; white-space:nowrap; }
    .nav-vertical a .bi { font-size:18px; opacity:0.95; color:var(--sidebar-ink); width:28px; text-align:center; }
    .nav-vertical a .label { transition:opacity .12s, transform .12s; }
    .nav-vertical a.active, .nav-vertical a:hover { background: linear-gradient(90deg, rgba(111,66,193,0.08), rgba(124,77,255,0.06)); transform:none; box-shadow: 0 6px 18px rgba(79,55,145,0.04); }
    .sidebar.collapsed .nav-vertical { align-items:center; gap:14px; padding-top:8px; padding:8px 0; }
    .sidebar.collapsed .nav-vertical a { padding:8px 6px; justify-content:center; width:44px; }
    .sidebar.collapsed .nav-vertical a .label { display:none; }
    .sidebar .tools { margin-top:auto; padding-top:12px; border-top:1px solid var(--soft-border); display:flex; flex-direction:column; gap:10px; align-items:flex-start; padding: 12px 10px 0 10px; }
    .sidebar.collapsed .tools { align-items:center; padding:12px 0 0 0; }
    .tools .tools-title { font-size:12px; color:var(--muted); font-weight:700; margin-bottom:6px; }
    .appearance-row { display:flex; align-items:center; justify-content:space-between; gap:8px; width:100%; }
    .sidebar.collapsed .tools-title,
    .sidebar.collapsed .appearance-row > div:first-child,
    .sidebar.collapsed .tools .label { display:none; }
    .sidebar.collapsed .appearance-row { justify-content:center; }
    .switch { position:relative; width:46px; height:26px; background:#e9ecef; border-radius:16px; cursor:pointer; box-shadow: inset 0 1px 2px rgba(0,0,0,0.06); }
    .switch .knob { position:absolute; top:3px; left:3px; width:20px; height:20px; border-radius:50%; background:white; transition:left .18s; box-shadow: 0 4px 12px rgba(16,24,40,0.12); }
    .switch.on { background: linear-gradient(135deg,var(--accent),var(--accent-2)); }
    .switch.on .knob { left:23px; }
    .logout-btn { display:flex; align-items:center; gap:8px; justify-content:center; padding:10px; border-radius:10px; background: linear-gradient(90deg,#ffefc2,#ffe1a8); color:#2b2b3b; font-weight:700; text-decoration:none; width:100%; }
    .sidebar.collapsed .logout-btn { padding:8px; width:44px; height:44px; justify-content:center; margin-top:10px; }
    .sidebar.collapsed .logout-btn .label { display:none !important; }
    .collapse-inline { display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:10px; background:white; border:0; cursor:pointer; box-shadow: 0 6px 18px rgba(15,16,30,0.06); }
    body.dark-mode .collapse-inline { background:#0b1220; color:#e6e9ff; }
    .search-wrap { display:flex; align-items:center; gap:10px; }
    .search-box { position:relative; width:660px; max-width:100%; }
    .search-box .search-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:18px; pointer-events:none; }
    .search-box input { width:100%; border-radius:12px; border:1px solid #e6e7ee; padding:12px 14px 12px 40px; background:white; box-shadow: 0 6px 20px rgba(15,16,30,0.04); }
    body.dark-mode .search-box input { background: #0b1220; border-color: rgba(255,255,255,0.06); color: #e6e9ff; box-shadow: none; }
    .main { flex:1; min-height:100vh; display:flex; flex-direction:column; gap:18px; }
    .topbar { display:flex; justify-content:space-between; gap:12px; align-items:center; }
    .userbox { display:flex; gap:12px; align-items:center; }
    .avatar { width:40px; height:40px; border-radius:50%; display:flex;align-items:center;justify-content:center; background:linear-gradient(135deg,var(--accent),var(--accent-2)); color:#fff; font-weight:700; }
    .header-card { background: linear-gradient(90deg, rgba(249,115,172,0.06), rgba(124,77,255,0.06)); border-radius:12px; padding:16px; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 8px 24px rgba(18,17,40,0.03); }
    .stats { display:flex; gap:14px; margin-top:6px; flex-wrap:wrap; }
    .stat { flex:1 1 240px; min-width:180px; background:var(--card-bg); border-radius:12px; padding:14px; box-shadow: 0 8px 24px rgba(15,16,30,0.04); display:flex; justify-content:space-between; align-items:center; }
    .stat .meta { color:var(--muted); font-weight:600; font-size:13px; }
    .stat .value { font-size:18px; font-weight:800; color: #111; }
    .stat .icon { width:52px; height:52px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; }
    .stat.blue .icon{ background: linear-gradient(135deg,#2b8fff,#2b6eff); }
    .stat.red .icon{ background: linear-gradient(135deg,#ef476f,#ff6b6b); }
    .stat.green .icon{ background: linear-gradient(135deg,#18c179,#0fb38f); }
    .card-table { background:var(--card-bg); border-radius:12px; padding:0; box-shadow: 0 8px 30px rgba(15,16,30,0.04); overflow:hidden; }
    .card-table .card-header { background:transparent; border-bottom:1px solid var(--soft-border); padding:14px 18px; display:flex; justify-content:space-between; align-items:center; }
    .btn-print { font-weight:700; border-radius:8px; }
    .btn-print.btn-primary { background: linear-gradient(135deg,#2b8fff,#2b6eff); border-color: transparent; color: #fff; }
    @media (max-width:1000px){
      .sidebar{ display:none; }
      .search-box{ width:100%; }
      .app{ padding:12px; }
    }
    body.dark-mode .filter-section,
body.dark-mode .card-table {
  background: #1a2334 !important;
  border: 1px solid rgba(255,255,255,0.09) !important;
  color: #e6e9ff !important;
}

body.dark-mode .form-control,
body.dark-mode .form-select {
  background: #0b1220 !important;
  color: #e6e9ff !important;
  border-color: rgba(255,255,255,0.10);
}

body.dark-mode .form-control::placeholder {
  color: #9aa0b4 !important;
  opacity: 1;
}

body.dark-mode .btn-primary, 
body.dark-mode .btn-secondary {
  background: linear-gradient(135deg, var(--accent), var(--accent-2));
  color: #fff !important;
  border: none;
}
body.dark-mode .btn-secondary {
  background: #44485c !important;
  color: #e6e9ff !important;
}

body.dark-mode .table {
  background: transparent !important;
  color: #e6e9ff !important;
}
body.dark-mode .table thead th {
  background: rgba(255,255,255,0.04) !important;
  color: #e6e9ff !important;
  border-bottom: 1px solid #222 !important;
}
body.dark-mode .table tbody tr {
  background: transparent !important;
  border-bottom: 1px solid rgba(255,255,255,0.09) !important;
}
body.dark-mode .table-hover tbody tr:hover {
  background: rgba(249,115,172,0.09) !important;
}
body.dark-mode .badge {
  color: #fff !important;
}

body.dark-mode .text-muted {
  color: #9aa0b4 !important;
}
body.dark-mode .card-table .table tbody tr:nth-child(even) {
  background-color: #212a3a !important;
}
body.dark-mode .card-table .table tbody tr:nth-child(odd) {
  background-color: transparent !important;
}
body.dark-mode .card-table,
body.dark-mode .table,
body.dark-mode .table tbody,
body.dark-mode .table tr,
body.dark-mode .table td,
body.dark-mode .table th {
  background: #1a2334 !important;
  color: #e6e9ff !important;
}

body.dark-mode .table thead th {
  background: rgba(255,255,255,0.04) !important;
  color: #e6e9ff !important;
}

body.dark-mode .table-striped > tbody > tr:nth-of-type(even) > td,
body.dark-mode .card-table .table tbody tr:nth-child(even) > td {
  background-color: #212a3a !important;
}

body.dark-mode .table-hover tbody tr:hover > td {
  background: rgba(249,115,172,0.09) !important;
  color: #fff !important;
}
/* Memperjelas ikon date-picker pada dark-mode (fix browser default icon) */
body.dark-mode input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1) brightness(1.6);
  opacity: 1;
}

/* Untuk browser non-Webkit */
body.dark-mode input[type="date"]::-moz-calendar-picker-indicator {
  filter: invert(1) brightness(1.6);
  opacity: 1;
}

body.dark-mode input[type="date"]::-ms-input-placeholder {
  color: #9aa0b4;
  opacity: 1;
}

body.dark-mode input[type="date"] {
  background: #0b1220 !important;
  color: #e6e9ff !important;
  border-color: rgba(255,255,255,0.10);
}
/* ... (CSS yang sudah ada) ... */

    /* Aturan Khusus untuk Cetak: Mirip Screenshot */
    @media print {
        /* 1. Sembunyikan elemen UI yang tidak perlu dicetak */
        .sidebar,
        .topbar,
        .filter-section,
        .btn-print,
        .collapse-inline,
        .search-wrap,
        /* Sembunyikan footer di bawah halaman */
        body::after {
            display: none !important;
        }

        /* 2. Pengaturan Dasar Halaman dan Kontainer */
        html, body {
            background: #fff !important;
            color: #000 !important;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        .container-fluid, .app, .main {
            padding: 20px 30px !important; /* Tambahkan sedikit padding ke pinggir */
            margin: 0 !important;
            min-height: auto;
            display: block;
        }

        /* 3. Header Laporan */
        .header-card {
            background: none !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0;
            margin-bottom: 30px;
            display: block;
            border-bottom: 1px solid #eef0f4; /* Mirip pemisah di screenshot */
            padding-bottom: 15px;
        }
        .header-card h4 {
            font-size: 24px;
            font-weight: 400; /* Tidak terlalu tebal seperti H4 default */
            margin: 0 0 4px 0 !important;
        }
        .header-card div:last-child {
            display: none !important; /* Sembunyikan tombol 'Cetak' */
        }
        .header-card div:first-child + div {
            color: #6c757d !important;
            font-size: 14px;
        }

        /* 4. Tiga Kotak Statistik Ringkasan (Meniru tampilan di Screenshot) */
        .stats {
            display: block !important; /* Nonaktifkan flexbox utama */
            width: 100%;
            margin-top: 0 !important;
            margin-bottom: 20px;
        }
        .stat {
            /* Stat di print tidak lagi menggunakan flex, tapi block */
            background: none !important;
            border: none !important;
            box-shadow: none !important;
            padding: 10px 0 !important;
            min-width: 100%;
            border-bottom: 1px solid #eef0f4; /* Pembatas antar stat */
            display: flex; /* Gunakan flex untuk memisahkan value dan icon */
            justify-content: space-between;
            align-items: center;
        }
        .stat:last-of-type {
            border-bottom: none;
        }
        .stat .meta {
            color: #333 !important; /* Judul Statistik */
            font-size: 15px;
            font-weight: 500;
        }
        .stat .value {
            font-size: 22px;
            font-weight: 700 !important;
            color: #000 !important;
        }
        .stat .icon {
            background: none !important;
            color: #6c757d !important; /* Warna ikon jadi abu-abu */
            font-size: 22px !important;
            width: auto !important;
            height: auto !important;
            margin-left: 10px;
        }
        /* Penyesuaian untuk "Total Transaksi" agar ada ikon daftar */
        .stat.blue .icon {
            display: none !important; /* Sembunyikan ikon bi-list-check bawaan */
        }
        /* Tambahkan ikon list di sebelah Total Transaksi */
        .stat.blue .value::after {
            content: "\e5b0"; /* Unicode untuk bi-list-task atau bi-list-check (jika font icons tersedia) */
            font-family: "bootstrap-icons";
            margin-left: 8px;
            color: #6c757d !important;
        }
        /* Tambahkan Ikon sesuai screenshot di sebelah Judul Stat */
        .stat.green .meta::before {
            content: "\e139"; /* bi-arrow-down-circle */
            font-family: "bootstrap-icons";
            margin-right: 8px;
            font-size: 16px;
            color: #333;
            vertical-align: middle;
        }
        .stat.yellow .meta::before {
            content: "\e128"; /* bi-arrow-up-circle */
            font-family: "bootstrap-icons";
            margin-right: 8px;
            font-size: 16px;
            color: #333;
            vertical-align: middle;
        }


        /* 5. Riwayat Transaksi (Tabel) */
        .card-table {
            box-shadow: none !important;
            border: none !important;
            margin-top: 0 !important;
        }
        .card-table .card-header {
            background: none !important;
            border-bottom: 1px solid #eef0f4 !important;
            padding: 15px 0 10px 0 !important;
            margin-bottom: 0;
            display: flex;
            align-items: center;
        }
        .card-table .card-header h5 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .card-table .card-header h5 .bi {
             color: #6c757d;
             font-size: 18px;
             margin-right: 5px;
        }
        .card-table .card-header small {
            display: none;
        }
        .card-table .card-body {
            padding: 0 !important;
        }
        
        /* Tabel itu sendiri */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: none !important;
            border-bottom: 1px solid #eef0f4 !important;
            padding: 8px 10px !important;
            font-size: 12px;
            color: #000 !important;
        }
        .table thead th {
            background-color: #fff !important;
            font-weight: 700 !important;
            font-size: 11px;
            color: #6c757d !important;
            text-transform: uppercase;
            border-top: 1px solid #eef0f4 !important;
            border-bottom: 1px solid #eef0f4 !important;
        }
        .table tbody tr:last-child td {
            border-bottom: none !important;
        }
        .table-hover tbody tr:hover {
            background: none !important; /* Hapus hover saat dicetak */
        }
        .badge {
            background: none !important;
            color: #333 !important;
            padding: 0 !important;
            font-weight: normal;
        }
        .badge .bi {
            font-size: 11px;
            vertical-align: baseline;
        }
        .text-success { color: #198754 !important; }
        .text-warning { color: #ffc107 !important; }

        /* Untuk meniru tampilan akhir di footer */
        @page {
            size: A4;
            margin: 1cm;
        }
        
        /* Untuk meniru tulisan URL di bawah */
        .main::after {
            content: "localhost/happyfood_backup/laporan.php"; /* Sesuai gambar */
            display: block;
            position: fixed;
            bottom: 5px;
            left: 30px;
            font-size: 10px;
            color: #6c757d;
        }
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="app">
      <!-- Sidebar (copy dari bahan_baku) -->
      <aside class="sidebar" id="appSidebar" role="navigation" aria-label="Sidebar">
        <div class="brand">
          <div class="logo">
            <img src="assets/logohappyfood.png" alt="HappyFood Logo" class="logo-img">
          </div>
          <div class="brand-text">
            <h5>HappyFood</h5>
            <small>Inventory System</small>
          </div>
        </div>
        <nav class="nav-vertical" aria-label="Main navigation">
          <a href="index.php" title="Dashboard"><i class="bi bi-speedometer2"></i><span class="label">Dashboard</span></a>
          <a href="bahan_baku.php" title="Bahan Baku"><i class="bi bi-box-seam"></i><span class="label">Bahan Baku</span></a>
          <a href="laporan.php" class="active" title="Laporan Stok"><i class="bi bi-file-earmark-text"></i><span class="label">Laporan Stok</span></a>
        </nav>
        <div class="tools" aria-hidden="false">
          <div class="tools-title">TOOLS</div>
          <div>
            <div class="appearance-row">
              <div style="font-weight:700; color:var(--muted); font-size:13px;">Appearance</div>
              <div>
                <div id="appearanceSwitch" class="switch" role="switch" aria-checked="false" tabindex="0">
                  <div class="knob"></div>
                </div>
              </div>
            </div>
            <div class="label" style="font-size:12px; color:var(--muted); margin-top:6px;">Mode terang / gelap</div>
          </div>
          <a href="logout.php" class="logout-btn" role="button" title="Logout">
            <i class="bi bi-box-arrow-right"></i> <span class="label" style="margin-left:6px;">Logout</span>
          </a>
        </div>
      </aside>
      <!-- Main area laporan -->
      <main class="main" role="main">
        <!-- Topbar area (copy dari bahan_baku) -->
        <div class="topbar">
          <div class="search-wrap">
            <button id="btnCollapseInline" class="collapse-inline" aria-label="Toggle sidebar" title="Toggle sidebar">
              <i class="bi bi-list" style="font-size:18px;"></i>
            </button>
            <div class="search-box">
              <i class="bi bi-search search-icon" aria-hidden="true"></i>
              <input type="search" placeholder="Search... (tekan '/' untuk fokus)" id="searchInput" />
            </div>
          </div>
          <div class="userbox">
            <div style="text-align:right">
              <div style="font-weight:700; color:inherit;"><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'], ENT_QUOTES); ?></div>
              <small style="color:var(--muted)">Admin</small>
            </div>
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></div>
          </div>
        </div>
        <div class="header-card">
          <div>
            <h4 style="margin:0 0 6px 0;">Laporan Stok</h4>
            <div style="color:var(--muted)">Riwayat pergerakan stok bahan baku</div>
          </div>
          <div>
            <button class="btn btn-print btn-primary" onclick="window.print()" title="Cetak">
              <i class="bi bi-printer"></i> Cetak
            </button>
          </div>
        </div>
        <div class="filter-section" style="padding:18px;">
          <form method="GET" action="">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Filter Bahan</label>
                <select class="form-select" name="filter_bahan">
                  <option value="">Semua Bahan</option>
                  <?php foreach ($bahans as $bahan): ?>
                    <option value="<?php echo $bahan['id']; ?>" <?php echo $filter_bahan == $bahan['id'] ? 'selected' : ''; ?>>
                      <?php echo $bahan['nama_bahan']; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Filter Jenis</label>
                <select class="form-select" name="filter_jenis">
                  <option value="">Semua Jenis</option>
                  <option value="masuk" <?php echo $filter_jenis == 'masuk' ? 'selected' : ''; ?>>Stok Masuk</option>
                  <option value="keluar" <?php echo $filter_jenis == 'keluar' ? 'selected' : ''; ?>>Stok Keluar</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Filter Tanggal</label>
                <input type="date" class="form-control" name="filter_tanggal" value="<?php echo $filter_tanggal; ?>">
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <div class="d-flex gap-2 w-100">
                  <button type="submit" class="btn btn-primary" style="font-weight:700;">
                    <i class="bi bi-funnel"></i> Filter
                  </button>
                  <a href="laporan.php" class="btn btn-secondary" style="font-weight:700;">
                    <i class="bi bi-x-circle"></i> Reset
                  </a>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="stats">
          <div class="stat green">
            <div>
              <div class="meta">Total Stok Masuk</div>
              <div class="value">
                <?php
                $totalMasuk = array_sum(array_map(function ($log) {
                    return ($log['jenis_transaksi'] ?? '') == 'masuk' ? (float) ($log['jumlah'] ?? 0) : 0;
                }, $logs));
                echo number_format($totalMasuk, 2);
                ?>
              </div>
            </div>
            <div class="icon"><i class="bi bi-arrow-down-circle"></i></div>
          </div>
          <div class="stat red">
            <div>
              <div class="meta">Total Stok Keluar</div>
              <div class="value">
                <?php
                $totalKeluar = array_sum(array_map(function ($log) {
                    return ($log['jenis_transaksi'] ?? '') == 'keluar' ? (float) ($log['jumlah'] ?? 0) : 0;
                }, $logs));
                echo number_format($totalKeluar, 2);
                ?>
              </div>
            </div>
            <div class="icon"><i class="bi bi-arrow-up-circle"></i></div>
          </div>
          <div class="stat blue">
            <div>
              <div class="meta">Total Transaksi</div>
              <div class="value"><?php echo count($logs); ?></div>
            </div>
            <div class="icon"><i class="bi bi-list-check"></i></div>
          </div>
        </div>
        <div class="card-table" style="margin-top:6px;">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Transaksi Stok</h5>
            <div><small class="text-muted">Riwayat lengkap</small></div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th>Tanggal & Waktu</th>
                    <th>Nama Bahan</th>
                    <th>Jenis Transaksi</th>
                    <th>Jumlah</th>
                    <th>Stok Sebelum</th>
                    <th>Stok Sesudah</th>
                    <th>User</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($logs)): ?>
                    <tr>
                      <td colspan="8" class="text-center text-muted">Tidak ada data transaksi</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                      <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($log['nama_bahan'] ?? $log['nama'] ?? '-', ENT_QUOTES); ?></td>
                        <td>
                          <?php if (($log['jenis_transaksi'] ?? '') == 'masuk'): ?>
                            <span class="badge" style="background:#18c179;color:#fff;padding:.35em .6em;border-radius:8px;"><i class="bi bi-arrow-down"></i> Masuk</span>
                          <?php else: ?>
                            <span class="badge" style="background:#f59f00;color:#000;padding:.35em .6em;border-radius:8px;"><i class="bi bi-arrow-up"></i> Keluar</span>
                          <?php endif; ?>
                        </td>
                        <td class="fw-bold">
                          <?php if (($log['jenis_transaksi'] ?? '') == 'masuk'): ?>
                            <span class="text-success">+<?php echo htmlspecialchars($log['jumlah'] ?? 0); ?></span>
                          <?php else: ?>
                            <span class="text-warning">-<?php echo htmlspecialchars($log['jumlah'] ?? 0); ?></span>
                          <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($log['stok_sebelum'] ?? '-', ENT_QUOTES); ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($log['stok_sesudah'] ?? '-', ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($log['nama_user'] ?? '-', ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($log['keterangan'] ?? '-', ENT_QUOTES); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar collapse: persist state
    (function(){
      const sidebar = document.getElementById('appSidebar');
      const btn = document.getElementById('btnCollapseInline');
      function setCollapsed(collapsed){
        if (collapsed) sidebar.classList.add('collapsed');
        else sidebar.classList.remove('collapsed');
        localStorage.setItem('hf_sidebar_collapsed', collapsed ? '1' : '0');
      }
      const stored = localStorage.getItem('hf_sidebar_collapsed');
      setCollapsed(stored === '1');
      if (btn) btn.addEventListener('click', ()=> setCollapsed(!sidebar.classList.contains('collapsed')));
      document.addEventListener('keydown', (e)=> {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
          setCollapsed(!sidebar.classList.contains('collapsed'));
        }
      });
    })();
    // Appearance toggle (dark-mode)
    (function(){
      const body = document.body;
      const switchEl = document.getElementById('appearanceSwitch');
      function setState(isOn){
        if (isOn) {
          switchEl.classList.add('on');
          switchEl.setAttribute('aria-checked','true');
          body.classList.add('dark-mode');
        } else {
          switchEl.classList.remove('on');
          switchEl.setAttribute('aria-checked','false');
          body.classList.remove('dark-mode');
        }
        localStorage.setItem('hf_appearance_dark', isOn ? '1' : '0');
      }
      const stored = localStorage.getItem('hf_appearance_dark');
      const initialOn = stored === '1';
      setState(initialOn);
      if (switchEl) {
        switchEl.addEventListener('click', ()=> setState(!switchEl.classList.contains('on')));
        switchEl.addEventListener('keydown', (e)=> {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setState(!switchEl.classList.contains('on')); }
        });
      }
    })();
    // Search focus shortcut
    (function(){
      const input = document.getElementById('searchInput');
      if (!input) return;
      document.addEventListener('keydown', (e) => {
        if (e.key === '/' && document.activeElement !== input) {
          e.preventDefault();
          input.focus();
          input.select();
        }
      });
    })();
  </script>
</body>
</html>
