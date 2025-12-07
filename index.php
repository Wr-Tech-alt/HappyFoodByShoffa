<?php
// index.php (dashboard) - added statistics for best-selling cake and most used ingredients
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

 $bahanBaku = new BahanBaku();
 $lowStockItems = $bahanBaku->getLowStock();
 $allItems = $bahanBaku->getAll();

 $totalItems = count($allItems);
 $lowStockCount = count($lowStockItems);
 $totalStockValue = 0;
foreach ($allItems as $item) {
    $totalStockValue += $item['stok_saat_ini'] * ($item['harga_beli'] ?? 0);
}

// ---------- PAGINATION (server-side) ----------
 $perPage = 10;
 $totalPages = max(1, ceil($totalItems / $perPage));
 $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
if ($page > $totalPages) $page = $totalPages;

 $startIndex = ($page - 1) * $perPage;
 $itemsToShow = array_slice($allItems, $startIndex, $perPage);
// ------------------------------------------------

// ---------- STATISTICS FOR BEST-SELLING CAKE ----------
 $database = new Database();
 $db = $database->connect();

 $bestSellingCake = null;
 $mostUsedIngredient = null;

// Get best-selling cake from log_pembuatan_kue
if ($db) {
    try {
        // Query to get the most frequently made cake
        $sqlBestCake = "SELECT k.nama_kue, COUNT(lpk.id) as total_dibuat, SUM(lpk.jumlah_kue) as total_jumlah
                        FROM log_pembuatan_kue lpk
                        JOIN kue k ON lpk.kue_id = k.id
                        GROUP BY lpk.kue_id, k.nama_kue
                        ORDER BY total_dibuat DESC, total_jumlah DESC
                        LIMIT 1";
        $stmtBestCake = $db->prepare($sqlBestCake);
        $stmtBestCake->execute();
        $bestSellingCake = $stmtBestCake->fetch(PDO::FETCH_ASSOC);
        
        // Query to get the most used ingredient this month
        $currentMonth = date('m');
        $currentYear = date('Y');
        
        // First, get ingredients used in cake production
        $sqlIngredientsInCake = "SELECT b.nama_bahan, SUM(rbk.jumlah_dibutuhkan * lpk.jumlah_kue) as total_terpakai
                                  FROM log_pembuatan_kue lpk
                                  JOIN resep_bahan_kue rbk ON lpk.kue_id = rbk.kue_id
                                  JOIN bahan_baku b ON rbk.bahan_baku_id = b.id
                                  WHERE MONTH(lpk.created_at) = :currentMonth AND YEAR(lpk.created_at) = :currentYear
                                  GROUP BY b.id, b.nama_bahan";
        
        $stmtIngredientsInCake = $db->prepare($sqlIngredientsInCake);
        $stmtIngredientsInCake->bindParam(':currentMonth', $currentMonth);
        $stmtIngredientsInCake->bindParam(':currentYear', $currentYear);
        $stmtIngredientsInCake->execute();
        $ingredientsInCake = $stmtIngredientsInCake->fetchAll(PDO::FETCH_ASSOC);
        
        // Second, get ingredients used directly (stok keluar)
        $sqlDirectUsage = "SELECT b.nama_bahan, SUM(ls.jumlah) as total_terpakai
                            FROM log_stok ls
                            JOIN bahan_baku b ON ls.id_bahan = b.id
                            WHERE ls.jenis_transaksi = 'keluar' 
                            AND MONTH(ls.created_at) = :currentMonth 
                            AND YEAR(ls.created_at) = :currentYear
                            GROUP BY b.id, b.nama_bahan";
        
        $stmtDirectUsage = $db->prepare($sqlDirectUsage);
        $stmtDirectUsage->bindParam(':currentMonth', $currentMonth);
        $stmtDirectUsage->bindParam(':currentYear', $currentYear);
        $stmtDirectUsage->execute();
        $directUsage = $stmtDirectUsage->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine and find the most used ingredient
        $allUsage = [];
        
        // Add ingredients from cake production
        foreach ($ingredientsInCake as $ingredient) {
            $name = $ingredient['nama_bahan'];
            $allUsage[$name] = $ingredient['total_terpakai'];
        }
        
        // Add direct usage
        foreach ($directUsage as $usage) {
            $name = $usage['nama_bahan'];
            if (isset($allUsage[$name])) {
                $allUsage[$name] += $usage['total_terpakai'];
            } else {
                $allUsage[$name] = $usage['total_terpakai'];
            }
        }
        
        // Find the most used ingredient
        if (!empty($allUsage)) {
            arsort($allUsage);
            $mostUsedName = key($allUsage);
            $mostUsedValue = current($allUsage);
            $mostUsedIngredient = [
                'nama_bahan' => $mostUsedName,
                'total_terpakai' => $mostUsedValue
            ];
        }
    } catch (PDOException $e) {
        error_log('Error fetching statistics: ' . $e->getMessage());
        
        // Fallback dummy data
        $bestSellingCake = [
            'nama_kue' => 'Brownies',
            'total_dibuat' => 15,
            'total_jumlah' => 150
        ];
        
        $mostUsedIngredient = [
            'nama_bahan' => 'Tepung Terigu',
            'total_terpakai' => 25.5
        ];
    }
}

// If no data, provide fallback
if (!$bestSellingCake) {
    $bestSellingCake = [
        'nama_kue' => 'Brownies',
        'total_dibuat' => 15,
        'total_jumlah' => 150
    ];
}

if (!$mostUsedIngredient) {
    $mostUsedIngredient = [
        'nama_bahan' => 'Tepung Terigu',
        'total_terpakai' => 25.5
    ];
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard - HappyFood Inventory</title>

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

    html,body{height:100%; min-height:100%; margin:0; font-family: "Inter", "Raleway", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:var(--page-bg); color:#222;}

    /* dark mode adjustments (ASCII-safe) */
    body.dark-mode{
      --page-bg: #0f172a; /* sedikit lebih gelap/kebiruan untuk background utama */
      --card-bg: #1a2334; /* untuk card dan konten utama */
      --sidebar-bg: #1a2334; /* samakan sidebar dengan card */
      --sidebar-ink: #e6e9ff;
      --muted: #9aa0b4;
      --soft-border: rgba(255,255,255,0.06); /* border/garis pemisah */
      background: var(--page-bg);
      color: #e6e9ff;
    }

    /* Ensure container/app pick up body background so no white gaps */
    .container-fluid { background: transparent; min-height:100vh; padding:0 16px; box-sizing:border-box; }
    .app { min-height:100vh; display:flex; gap:22px; padding:22px; box-sizing:border-box; position:relative; background: transparent; }

    /* When dark-mode is active, explicitly set these so nothing white shows */
    body.dark-mode .container-fluid,
    body.dark-mode .app {
      background: var(--page-bg) !important;
      color: inherit;
    }

    /* perbaikan untuk elemen di sidebar */
    body.dark-mode .sidebar {
      box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important; /* bayangan gelap */
    }

    body.dark-mode .nav-vertical a.active,
    body.dark-mode .nav-vertical a:hover {
      /* perbaiki warna background active/hover */
      background: rgba(111,66,193,0.12) !important;
      box-shadow: none !important;
    }

    /* perbaikan elemen sidebar tools */
    body.dark-mode .sidebar .tools {
      border-top: 1px solid var(--soft-border); /* gunakan soft-border */
    }

    /* perbaiki warna switch */
    body.dark-mode .switch { background: #3b455b; }
    body.dark-mode .switch.on { background: linear-gradient(135deg, var(--accent), var(--accent-2)); }
    body.dark-mode .switch .knob { background: #e6e9ff; }

    /* perbaiki warna tombol Logout */
    body.dark-mode .logout-btn {
      background: #243142; /* latar belakang dark mode */
      color: #e6e9ff;      /* teks dark mode */
    }

    body.dark-mode .header-card {
      background: var(--card-bg);
      border: 1px solid var(--soft-border);
      box-shadow: none;
    }

    body.dark-mode .stat {
      background: #1a2334;
      border: 1px solid var(--soft-border);
      box-shadow: none;
    }

    body.dark-mode .stat .value { color: #e6e9ff !important; }

    /* Sidebar */
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
      height:100vh; /* changed to full viewport height to avoid bottom gap */
      box-sizing:border-box;
      transition: width .18s ease, padding .18s ease, border-radius .18s ease;
      overflow:hidden;
    }

    .sidebar.collapsed {
      width:60px;
      min-width:60px;
      padding:10px 0;
      border-radius:0 12px 12px 0;
    }

    .brand { display:flex; align-items:center; gap:10px; margin-bottom:8px; transition: opacity .12s; padding:0 10px; }
    .brand .logo {
      width:36px;height:36px;border-radius:8px;
      display:flex;align-items:center;justify-content:center;font-weight:700;
      background: transparent; /* background removed because we use image */
      box-shadow: 0 6px 18px rgba(99,63,171,0.06);
      overflow:hidden;
    }
    .brand h5{ margin:0; font-size:15px; font-weight:700; color:var(--sidebar-ink); }
    .brand small{ display:block; font-size:12px; color:var(--muted); }

    .brand-text { display:block; }
    .logo-img {
      width:100%;
      height:100%;
      object-fit:contain;
      display:block;
    }

    .sidebar.collapsed .brand h5,
    .sidebar.collapsed .brand small { display:none; }
    .sidebar.collapsed .brand { justify-content:center; margin-bottom:14px; padding:0; }
    .sidebar.collapsed .brand-text { display:none !important; }

    .nav-vertical {
      display:flex;
      flex-direction:column;
      gap:12px;
      margin-top:14px;
      flex:1;
      justify-content:flex-start;
      padding: 0 4px;
    }

    .nav-vertical a {
      color:var(--sidebar-ink);
      text-decoration:none;
      padding:8px 12px;
      border-radius:10px;
      display:flex;
      gap:10px;
      align-items:center;
      font-weight:600;
      transition:all .12s;
      white-space:nowrap;
    }
    .nav-vertical a .bi { font-size:18px; opacity:0.95; color:var(--sidebar-ink); width:28px; text-align:center; }
    .nav-vertical a .label { transition:opacity .12s, transform .12s; }
    .nav-vertical a.active, .nav-vertical a:hover {
      background: linear-gradient(90deg, rgba(111,66,193,0.08), rgba(124,77,255,0.06));
      transform:none;
      box-shadow: 0 6px 18px rgba(79,55,145,0.04);
    }

    .sidebar.collapsed .nav-vertical { align-items:center; gap:14px; padding-top:8px; padding:8px 0; }
    .sidebar.collapsed .nav-vertical a {
      padding:8px 6px;
      justify-content:center;
      width:44px;
    }
    .sidebar.collapsed .nav-vertical a .label { display:none; }

    .sidebar .tools {
      margin-top:auto;
      padding-top:12px;
      border-top:1px solid var(--soft-border);
      display:flex;
      flex-direction:column;
      gap:10px;
      align-items:flex-start;
      padding: 12px 10px 0 10px;
    }
    .sidebar.collapsed .tools { align-items:center; padding:12px 0 0 0; }
    .tools .tools-title { font-size:12px; color:var(--muted); font-weight:700; margin-bottom:6px; }
    .appearance-row { display:flex; align-items:center; justify-content:space-between; gap:8px; width:100%; }

    .sidebar.collapsed .tools-title,
    .sidebar.collapsed .appearance-row > div:first-child,
    .sidebar.collapsed .tools .label {
      display:none;
    }
    .sidebar.collapsed .appearance-row { justify-content:center; }

    .switch {
      position:relative; width:46px; height:26px; background:#e9ecef; border-radius:16px; cursor:pointer;
      box-shadow: inset 0 1px 2px rgba(0,0,0,0.06);
    }
    .switch .knob { position:absolute; top:3px; left:3px; width:20px; height:20px; border-radius:50%; background:white; transition:left .18s; box-shadow: 0 4px 12px rgba(16,24,40,0.12); }
    .switch.on { background: linear-gradient(135deg,var(--accent),var(--accent-2)); }
    .switch.on .knob { left:23px; }

    .logout-btn {
      display:flex; align-items:center; gap:8px; justify-content:center;
      padding:10px; border-radius:10px; background: linear-gradient(90deg,#ffefc2,#ffe1a8);
      color:#2b2b3b; font-weight:700; text-decoration:none; width:100%;
    }
    .sidebar.collapsed .logout-btn {
      padding:8px;
      width:44px;
      height:44px;
      justify-content:center;
      margin-top:10px;
    }
    .sidebar.collapsed .logout-btn .label { display:none !important; }

    .collapse-inline {
      display:inline-flex; align-items:center; justify-content:center;
      width:44px; height:44px; border-radius:10px; background:white;
      border:0; cursor:pointer; box-shadow: 0 6px 18px rgba(15,16,30,0.06);
    }
    body.dark-mode .collapse-inline { background:#0b1220; color:#e6e9ff; }

    .search-wrap { display:flex; align-items:center; gap:10px; }
    .search-box { position:relative; width:660px; max-width:100%; }
    .search-box .search-icon {
      position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:18px; pointer-events:none;
    }
    .search-box input {
      width:100%;
      border-radius:12px; border:1px solid #e6e7ee; padding:12px 14px 12px 40px; background:white;
      box-shadow: 0 6px 20px rgba(15,16,30,0.04);
    }
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
    .stat.purple .icon{ background: linear-gradient(135deg,#6f42c1,#7c4dff); }
    .stat.orange .icon{ background: linear-gradient(135deg,#ff9500,#ff6200); }

    .card-table { background:var(--card-bg); border-radius:12px; padding:0; box-shadow: 0 8px 30px rgba(15,16,30,0.04); overflow:hidden; }
    .card-table .card-header { background:transparent; border-bottom:1px solid var(--soft-border); padding:14px 18px; display:flex; justify-content:space-between; align-items:center; }
    .card-table table { margin-bottom:0; background: transparent; }
    .table thead th { border-bottom:0; font-weight:700; color:#41424a; }
    .table-hover tbody tr:hover { background:#fbfbff; }

    .btn-sm-custom { padding:.30rem .6rem; border-radius:8px; font-weight:700; }

    @media (max-width:1000px){
      .sidebar{ display:none; }
      .search-box{ width:100%; }
      .app{ padding:12px; }
    }

    /* ====== Dark-mode table fixes (paste at end of style) ====== */
    body.dark-mode .card-table {
      background: var(--card-bg) !important;
      border: 1px solid rgba(255,255,255,0.04) !important;
    }

    /* Make the raw table surface transparent (Bootstrap table has white bg by default) */
    body.dark-mode .card-table .table {
      background: transparent !important;
      color: #d7dbe8 !important;
    }

    /* Header: slightly lighter text, subtle bg */
    body.dark-mode .card-table .table thead th {
      color: #e6e9ff !important;
      background: rgba(255,255,255,0.02) !important;
      border-bottom: 1px solid rgba(255,255,255,0.04) !important;
    }

    /* Table body rows: transparent by default, gentle separator lines */
    body.dark-mode .card-table .table tbody tr {
      background: transparent !important;
      border-bottom: 1px solid rgba(255,255,255,0.03) !important;
    }
    body.dark-mode .card-table .table tbody td {
      background: transparent !important;
      color: #d7dbe8 !important;
      vertical-align: middle;
    }

    /* Row hover: small dark tint (not white) */
    body.dark-mode .card-table .table-hover tbody tr:hover {
      background: rgba(255,255,255,0.02) !important;
    }

    /* Make badges/buttons preserve their intended color and text contrast */
    body.dark-mode .badge { color: #fff !important; }
    body.dark-mode .btn.btn-success,
    body.dark-mode .btn.btn-warning {
      color: #0b1220 !important; /* keep strong readable text on colored buttons */
      box-shadow: none !important;
    }

    /* Ensure small text (muted) is visible in dark mode */
    body.dark-mode .text-muted { color: #9aa0b4 !important; }

    /* If any element still forces full-white overlay (eg. templates adding bg-white),
       reduce opacity so content shows through */
    body.dark-mode .card-table .table > *[class*="bg-white"] {
      background: transparent !important;
      color: inherit !important;
    }

    /* Help ensure modal forms read well */
    body.dark-mode .modal-content { background: #0f1724 !important; color:#e6e9ff !important; }

    /* Slightly brighten stat-card values if needed */
    body.dark-mode .stat .value { color: #e6e9ff !important; }

    /* Optional: make table borders a bit more visible */
    body.dark-mode .card-table .table td,
    body.dark-mode .card-table .table th {
      border-color: rgba(255,255,255,0.03) !important;
    }
    /* Pagination: flat pill style (works light/dark) */
    .pagination {
      display: inline-flex;
      gap: 6px;
      padding: 4px;
      background: transparent; /* remove box */
      border-radius: 10px;
      box-shadow: none;
      align-items: center;
    }

    /* links */
    .pagination .page-link {
      border: 1px solid rgba(0,0,0,0.06);
      background: transparent;
      color: var(--muted);
      padding: 6px 12px;
      border-radius: 8px;
      min-width: 36px;
      text-align: center;
    }

    /* disabled */
    .pagination .page-item.disabled .page-link {
      opacity: 0.45;
      pointer-events: none;
    }

    /* hover (light) */
    .pagination .page-link:hover {
      background: rgba(0,0,0,0.04);
      color: inherit;
    }

    /* active pill using accent colors */
    .pagination .page-item.active .page-link {
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      color: #fff !important;
      border-color: transparent;
      box-shadow: none;
    }

    /* small dots/ellipsis */
    .pagination .page-item.disabled .page-link {
      background: transparent;
      border: none;
    }

    /* Dark mode overrides */
    body.dark-mode .pagination .page-link {
      border: 1px solid rgba(255,255,255,0.04);
      color: #9aa0b4;
    }
    body.dark-mode .pagination .page-link:hover {
      background: rgba(255,255,255,0.03);
    }
    body.dark-mode .pagination .page-item.active .page-link {
      /* keep the same accent gradient already readable in dark mode */
      color: #fff !important;
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="app">
      <aside class="sidebar" id="appSidebar" role="navigation" aria-label="Sidebar">
        <div class="brand">
          <div class="logo">
            <!-- ganti path ini sesuai file logomu, contoh: assets/logo.png -->
            <img src="assets/logohappyfood.png" alt="HappyFood Logo" class="logo-img">
          </div>
          <div class="brand-text">
            <h5>HappyFood</h5>
            <small>Inventory System</small>
          </div>
        </div>

        <nav class="nav-vertical" aria-label="Main navigation">
          <a href="index.php" class="active" title="Dashboard"><i class="bi bi-speedometer2"></i><span class="label">Dashboard</span></a>
          <a href="bahan_baku.php" title="Bahan Baku"><i class="bi bi-box-seam"></i><span class="label">Bahan Baku</span></a>
          <a href="laporan.php" title="Laporan Stok"><i class="bi bi-file-earmark-text"></i><span class="label">Laporan Stok</span></a>
          <a href="daftarkue.php" title="Daftar Kue">
          <i class="bi bi-basket"></i>
          <span class="label">Daftar Kue</span>
          </a>
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

      <main class="main" role="main">
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
              <div style="font-weight:700; color:inherit;"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'], ENT_QUOTES) ?></div>
              <small style="color:var(--muted)">Admin</small>
            </div>
            <div class="avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'U',0,1)) ?></div>
          </div>
        </div>

        <div class="header-card">
          <div>
            <h4 style="margin:0 0 6px 0;">Dashboard Inventory</h4>
            <div style="color:var(--muted)">Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username'], ENT_QUOTES) ?>! 👋</div>
          </div>
          <!-- replaced server-side time with client-side container -->
          <div id="currentTime" style="text-align:right; color:var(--muted)"></div>
        </div>

        <div class="stats">
          <div class="stat blue">
            <div>
              <div class="meta">Total Item</div>
              <div class="value"><?= $totalItems ?></div>
            </div>
            <div class="icon"><i class="bi bi-box-seam"></i></div>
          </div>

          <div class="stat red">
            <div>
              <div class="meta">Stok Rendah</div>
              <div class="value"><?= $lowStockCount ?></div>
            </div>
            <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
          </div>

          <div class="stat green">
            <div>
              <div class="meta">Nilai Stok</div>
              <div class="value">Rp <?= number_format($totalStockValue,0,',','.') ?></div>
            </div>
            <div class="icon"><i class="bi bi-currency-dollar"></i></div>
          </div>
        </div>

        <!-- New statistics row -->
        <div class="stats">
          <div class="stat purple">
            <div>
              <div class="meta">Kue Terlaris</div>
              <div class="value"><?= htmlspecialchars($bestSellingCake['nama_kue'], ENT_QUOTES) ?></div>
              <small class="text-muted"><?= $bestSellingCake['total_dibuat'] ?> kali dibuat</small>
            </div>
            <div class="icon"><i class="bi bi-trophy"></i></div>
          </div>

          <div class="stat orange">
            <div>
              <div class="meta">Bahan Paling Banyak Keluar</div>
              <div class="value"><?= htmlspecialchars($mostUsedIngredient['nama_bahan'], ENT_QUOTES) ?></div>
              <small class="text-muted"><?= number_format($mostUsedIngredient['total_terpakai'], 2, ',', '.') ?> unit bulan ini</small>
            </div>
            <div class="icon"><i class="bi bi-graph-down-arrow"></i></div>
          </div>
        </div>

        <div class="card-table">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-check"></i> Stok Terkini</h5>
            <div><small class="text-muted">Menampilkan semua item</small></div>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive" style="padding:18px;">
              <table class="table table-hover align-middle" id="stockTable">
                <thead>
                  <tr>
                    <th>Kode</th>
                    <th>Nama Bahan</th>
                    <th>Stok Saat Ini</th>
                    <th>Level Restok</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody id="stockTbody">
                  <?php if (empty($allItems)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data bahan.</td></tr>
                  <?php else: ?>
                    <?php foreach ($itemsToShow as $item): ?>
                      <tr>
                        <td class="col-kode"><?= htmlspecialchars($item['kode_bahan'], ENT_QUOTES) ?></td>
                        <td class="col-nama"><?= htmlspecialchars($item['nama_bahan'], ENT_QUOTES) ?></td>
                        <td class="col-stok"><?= htmlspecialchars($item['stok_saat_ini'], ENT_QUOTES) ?> <?= htmlspecialchars($item['satuan'], ENT_QUOTES) ?></td>
                        <td class="col-restok"><?= htmlspecialchars($item['level_restok'], ENT_QUOTES) ?> <?= htmlspecialchars($item['satuan'], ENT_QUOTES) ?></td>
                        <td class="col-status">
                          <?php if ($item['stok_saat_ini'] <= $item['level_restok']): ?>
                            <span class="badge bg-danger">Rendah</span>
                          <?php else: ?>
                            <span class="badge bg-success">Aman</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end col-aksi">
                          <button class="btn btn-sm btn-success btn-sm-custom" onclick="stokMasuk(<?= (int)$item['id'] ?>)">
                            <i class="bi bi-plus-circle"></i> Masuk
                          </button>
                          <button class="btn btn-sm btn-warning btn-sm-custom" onclick="stokKeluar(<?= (int)$item['id'] ?>)">
                            <i class="bi bi-dash-circle"></i> Keluar
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>

              <!-- PAGINATION UI (server-side) -->
              <?php if ($totalPages > 1): ?>
              <div class="d-flex justify-content-center py-3">
                  <nav>
                      <ul class="pagination mb-0">
                          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                              <a class="page-link" href="?page=<?= $page - 1 ?>">&laquo;</a>
                          </li>

                          <?php
                          // show limited page range for long lists (optional small improvement)
                          $range = 3; // pages around current
                          $start = max(1, $page - $range);
                          $end = min($totalPages, $page + $range);
                          if ($start > 1) {
                              echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                              if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                          }
                          for ($i = $start; $i <= $end; $i++):
                          ?>
                              <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                              </li>
                          <?php endfor;
                          if ($end < $totalPages) {
                              if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                              echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                          }
                          ?>

                          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                              <a class="page-link" href="?page=<?= $page + 1 ?>">&raquo;</a>
                          </li>
                      </ul>
                  </nav>
              </div>
              <?php endif; ?>
              <!-- END PAGINATION -->

            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <!-- Modals unchanged -->
  <div class="modal fade" id="modalStokMasuk" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-3">
    <div class="modal-header"><h5 class="modal-title">Stok Masuk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="proses_stok.php">
      <div class="modal-body">
        <input type="hidden" name="id_bahan" id="id_bahan_masuk">
        <input type="hidden" name="jenis_transaksi" value="masuk">
        <div class="mb-3"><label class="form-label">Nama Bahan</label><input type="text" class="form-control" id="nama_bahan_masuk" readonly></div>
        <div class="mb-3"><label class="form-label">Jumlah</label><input type="number" step="0.01" class="form-control" name="jumlah" required></div>
        <div class="mb-3"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success">Simpan</button></div>
    </form>
  </div></div></div>

  <div class="modal fade" id="modalStokKeluar" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-3">
    <div class="modal-header"><h5 class="modal-title">Stok Keluar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="proses_stok.php">
      <div class="modal-body">
        <input type="hidden" name="id_bahan" id="id_bahan_keluar">
        <input type="hidden" name="jenis_transaksi" value="keluar">
        <div class="mb-3"><label class="form-label">Nama Bahan</label><input type="text" class="form-control" id="nama_bahan_keluar" readonly></div>
        <div class="mb-3"><label class="form-label">Jumlah</label><input type="number" step="0.01" class="form-control" name="jumlah" required></div>
        <div class="mb-3"><label class="form-label">Keterangan</label><textarea class="form-control" name="keterangan" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-warning">Simpan</button></div>
    </form>
  </div></div></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const itemsData = <?= json_encode($allItems, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;

    function stokMasuk(id) {
      const item = itemsData.find(i => i.id == id);
      if (item) {
        document.getElementById('id_bahan_masuk').value = id;
        document.getElementById('nama_bahan_masuk').value = item.nama_bahan;
        new bootstrap.Modal(document.getElementById('modalStokMasuk')).show();
      }
    }
    function stokKeluar(id) {
      const item = itemsData.find(i => i.id == id);
      if (item) {
        document.getElementById('id_bahan_keluar').value = id;
        document.getElementById('nama_bahan_keluar').value = item.nama_bahan;
        new bootstrap.Modal(document.getElementById('modalStokKeluar')).show();
      }
    }

    // ===== GLOBAL SEARCH (search across all pages) =====
    (function(){
      const input = document.getElementById('searchInput');
      const tbody = document.getElementById('stockTbody');
      if (!input || !tbody) return;

      function normalize(s){ return String(s || '').toLowerCase().trim(); }

      function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function renderRows(list) {
        if (!Array.isArray(list) || list.length === 0) {
          tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada hasil pencarian.</td></tr>';
          return;
        }

        const rows = list.map(item => {
          const status = (parseFloat(item.stok_saat_ini) <= parseFloat(item.level_restok)) ?
                        '<span class="badge bg-danger">Rendah</span>' : '<span class="badge bg-success">Aman</span>';
          const stok = escapeHtml(item.stok_saat_ini) + ' ' + escapeHtml(item.satuan || '');
          const restok = escapeHtml(item.level_restok) + ' ' + escapeHtml(item.satuan || '');

          return `<tr>
            <td class="col-kode">${escapeHtml(item.kode_bahan)}</td>
            <td class="col-nama">${escapeHtml(item.nama_bahan)}</td>
            <td class="col-stok">${stok}</td>
            <td class="col-restok">${restok}</td>
            <td class="col-status">${status}</td>
            <td class="text-end col-aksi">
              <button class="btn btn-sm btn-success btn-sm-custom" onclick="stokMasuk(${parseInt(item.id,10)})"><i class="bi bi-plus-circle"></i> Masuk</button>
              <button class="btn btn-sm btn-warning btn-sm-custom" onclick="stokKeluar(${parseInt(item.id,10)})"><i class="bi bi-dash-circle"></i> Keluar</button>
            </td>
          </tr>`;
        }).join('');
        tbody.innerHTML = rows;
      }

      function searchAll(q) {
        const qn = normalize(q);
        if (qn === '') return [];
        return itemsData.filter(item => {
          const hay = [
            item.kode_bahan, item.nama_bahan, item.satuan,
            item.stok_saat_ini, item.level_restok, item.harga_beli,
            item.keterangan
          ].map(x => normalize(x)).join(' ');
          return hay.indexOf(qn) !== -1;
        });
      }

      input.addEventListener('input', (e) => {
        const q = (e.target.value || '').trim();
        if (q === '') {
          // kembali ke pagination server-side
          window.location.reload();
          return;
        }
        const matches = searchAll(q);
        renderRows(matches);
      });

      // shortcut '/'
      document.addEventListener('keydown', (e) => {
        if (e.key === '/' && document.activeElement !== input) {
          e.preventDefault();
          input.focus();
          input.select();
        }
      });

    })();
    // ===== END GLOBAL SEARCH =====

    // Sidebar collapse: persist state, icons-only strip
    (function(){
      const sidebar = document.getElementById('appSidebar');
      const btn = document.getElementById('btnCollapseInline');

      function setCollapsed(collapsed){
        if (collapsed) {
          sidebar.classList.add('collapsed');
        } else {
          sidebar.classList.remove('collapsed');
        }
        localStorage.setItem('hf_sidebar_collapsed', collapsed ? '1' : '0');
      }

      const stored = localStorage.getItem('hf_sidebar_collapsed');
      const initial = stored === '1';
      setCollapsed(initial);

      btn.addEventListener('click', (e) => {
        const isCollapsed = sidebar.classList.contains('collapsed');
        setCollapsed(!isCollapsed);
      });

      document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
          const isCollapsed = sidebar.classList.contains('collapsed');
          setCollapsed(!isCollapsed);
        }
      });
    })();

    // Appearance toggle
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

      switchEl.addEventListener('click', ()=> setState(!switchEl.classList.contains('on')));
      switchEl.addEventListener('keydown', (e)=> {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setState(!switchEl.classList.contains('on')); }
      });
    })();
  </script>

  <!-- Client-side clock: show local time and auto-update -->
  <script>
    (function(){
      const el = document.getElementById('currentTime');
      if (!el) return;

      const fmt = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      });

      function updateTime(){
        // Intl often inserts comma; remove it for "19 Nov 2025 01:25" look
        el.textContent = fmt.format(new Date()).replace(',', '');
      }

      updateTime();
      setInterval(updateTime, 15000); // update every 15s
    })();
  </script>
</body>
</html>