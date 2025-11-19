<?php
// bahan_baku.php - tampilannya benar + fungsi "Tambah Bahan" (create) dipastikan ada
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$bahanBaku = new BahanBaku();
$success = '';
$error = '';

// Handle POST requests for CRUD operations (create + update + delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'kode_bahan'    => $_POST['kode_bahan'] ?? '',
                    'nama_bahan'    => $_POST['nama_bahan'] ?? '',
                    'satuan'        => $_POST['satuan'] ?? '',
                    'stok_saat_ini' => floatval($_POST['stok_saat_ini'] ?? 0),
                    'level_restok'  => floatval($_POST['level_restok'] ?? 0),
                    'harga_beli'    => floatval($_POST['harga_beli'] ?? 0),
                    'keterangan'    => $_POST['keterangan'] ?? ''
                ];
                if ($bahanBaku->create($data)) {
                    $success = 'Bahan baku berhasil ditambahkan!';
                } else {
                    $error = 'Gagal menambahkan bahan baku!';
                }
                break;

            case 'update':
                $id = $_POST['id'] ?? 0;
                $data = [
                    'kode_bahan'    => $_POST['kode_bahan'] ?? '',
                    'nama_bahan'    => $_POST['nama_bahan'] ?? '',
                    'satuan'        => $_POST['satuan'] ?? '',
                    'stok_saat_ini' => floatval($_POST['stok_saat_ini'] ?? 0),
                    'level_restok'  => floatval($_POST['level_restok'] ?? 0),
                    'harga_beli'    => floatval($_POST['harga_beli'] ?? 0),
                    'keterangan'    => $_POST['keterangan'] ?? ''
                ];
                if ($bahanBaku->update($id, $data)) {
                    $success = 'Bahan baku berhasil diperbarui!';
                } else {
                    $error = 'Gagal memperbarui bahan baku!';
                }
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                if ($bahanBaku->delete($id)) {
                    $success = 'Bahan baku berhasil dihapus!';
                } else {
                    $error = 'Gagal menghapus bahan baku!';
                }
                break;
        }
    }
}

// ambil semua item dari model
$allItems = $bahanBaku->getAll();

// ---------- PAGINATION (server-side) ----------
$perPage = 10;
$totalItems = count($allItems);
$totalPages = max(1, ceil($totalItems / $perPage));
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
if ($page > $totalPages) $page = $totalPages;

$startIndex = ($page - 1) * $perPage;
$itemsToShow = array_slice($allItems, $startIndex, $perPage);
// ------------------------------------------------
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Manajemen Bahan Baku - HappyFood Inventory</title>

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
      --page-bg: #0f172a;
      --card-bg: #1a2334;
      --sidebar-bg: #1a2334;
      --sidebar-ink: #e6e9ff;
      --muted: #9aa0b4;
      --soft-border: rgba(255,255,255,0.06);
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
      box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
    }

    body.dark-mode .nav-vertical a.active,
    body.dark-mode .nav-vertical a:hover {
      background: rgba(111,66,193,0.12) !important;
      box-shadow: none !important;
    }

    /* perbaikan elemen sidebar tools */
    body.dark-mode .sidebar .tools {
      border-top: 1px solid var(--soft-border);
    }

    /* perbaiki warna switch */
    body.dark-mode .switch { background: #3b455b; }
    body.dark-mode .switch.on { background: linear-gradient(135deg, var(--accent), var(--accent-2)); }
    body.dark-mode .switch .knob { background: #e6e9ff; }

    /* perbaiki warna tombol Logout */
    body.dark-mode .logout-btn { background: #243142; color: #e6e9ff; }

    body.dark-mode .header-card { background: var(--card-bg); border: 1px solid var(--soft-border); box-shadow:none; }
    body.dark-mode .stat { background: #1a2334; border:1px solid var(--soft-border); box-shadow:none; }
    body.dark-mode .stat .value { color:#e6e9ff !important; }

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
      height:100vh;
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
    .sidebar.collapsed .tools .label {
      display:none;
    }
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
    .card-table table { margin-bottom:0; background: transparent; }
    .table thead th { border-bottom:0; font-weight:700; color:#41424a; }
    .table-hover tbody tr:hover { background:#fbfbff; }

    .btn-sm-custom { padding:.30rem .6rem; border-radius:8px; font-weight:700; }

    @media (max-width:1000px){
      .sidebar{ display:none; }
      .search-box{ width:100%; }
      .app{ padding:12px; }
    }

    /* Dark mode table fixes */
    body.dark-mode .card-table { background: var(--card-bg) !important; border: 1px solid rgba(255,255,255,0.04) !important; }
    body.dark-mode .card-table .table { background: transparent !important; color: #d7dbe8 !important; }
    body.dark-mode .card-table .table thead th { color: #e6e9ff !important; background: rgba(255,255,255,0.02) !important; border-bottom: 1px solid rgba(255,255,255,0.04) !important; }
    body.dark-mode .card-table .table tbody tr { background: transparent !important; border-bottom: 1px solid rgba(255,255,255,0.03) !important; }
    body.dark-mode .card-table .table tbody td { background: transparent !important; color: #d7dbe8 !important; vertical-align: middle; }
    body.dark-mode .card-table .table-hover tbody tr:hover { background: rgba(255,255,255,0.02) !important; }
    body.dark-mode .badge { color: #fff !important; }
    body.dark-mode .btn.btn-success, body.dark-mode .btn.btn-warning { color: #0b1220 !important; box-shadow: none !important; }
    body.dark-mode .text-muted { color: #9aa0b4 !important; }
    body.dark-mode .modal-content { background: #0f1724 !important; color:#e6e9ff !important; }
    body.dark-mode .stat .value { color: #e6e9ff !important; }
    body.dark-mode .card-table .table td, body.dark-mode .card-table .table th { border-color: rgba(255,255,255,0.03) !important; }

    /* Pagination: flat pill style (works light/dark) */
    .pagination {
      display: inline-flex;
      gap: 6px;
      padding: 4px;
      background: transparent;
      border-radius: 10px;
      box-shadow: none;
      align-items: center;
    }
    .pagination .page-link {
      border: 1px solid rgba(0,0,0,0.06);
      background: transparent;
      color: var(--muted);
      padding: 6px 12px;
      border-radius: 8px;
      min-width: 36px;
      text-align: center;
    }
    .pagination .page-item.disabled .page-link { opacity: 0.45; pointer-events: none; }
    .pagination .page-link:hover { background: rgba(0,0,0,0.04); color: inherit; }
    .pagination .page-item.active .page-link { background: linear-gradient(135deg, var(--accent), var(--accent-2)); color: #fff !important; border-color: transparent; box-shadow: none; }
    body.dark-mode .pagination .page-link { border: 1px solid rgba(255,255,255,0.04); color: #9aa0b4; }
    body.dark-mode .pagination .page-link:hover { background: rgba(255,255,255,0.03); }
    body.dark-mode .pagination .page-item.active .page-link { color: #fff !important; }

  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="app">
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
          <a href="bahan_baku.php" class="active" title="Bahan Baku"><i class="bi bi-box-seam"></i><span class="label">Bahan Baku</span></a>
          <a href="laporan.php" title="Laporan Stok"><i class="bi bi-file-earmark-text"></i><span class="label">Laporan Stok</span></a>
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
            <h4 style="margin:0 0 6px 0;">Manajemen Bahan Baku</h4>
            <div style="color:var(--muted)">Kelola data master bahan baku</div>
          </div>

          <!-- ADD BUTTON (kembali dari versi lama) -->
          <div>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd" style="font-weight:700; padding:.6rem 1rem; border-radius:8px;">
              <i class="bi bi-plus-circle"></i> Tambah Bahan
            </button>
          </div>
        </div>

        <div class="stats" style="display:none;"></div> <!-- hide stats for bahan_baku page (keperluan konsistensi layout) -->

        <div class="card-table">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Data Bahan Baku</h5>
            <div><small class="text-muted">Menampilkan semua item</small></div>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive" style="padding:18px;">
              <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
              <?php endif; ?>
              <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
              <?php endif; ?>

              <table class="table table-hover align-middle" id="stockTable">
                <thead>
                  <tr>
                    <th>Kode</th>
                    <th>Nama Bahan</th>
                    <th>Satuan</th>
                    <th>Stok Saat Ini</th>
                    <th>Level Restok</th>
                    <th>Harga Beli</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody id="stockTbody">
                  <?php if (empty($allItems)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada data bahan.</td></tr>
                  <?php else: ?>
                    <?php foreach ($itemsToShow as $item): ?>
                      <tr>
                        <td class="col-kode"><?= htmlspecialchars($item['kode_bahan'], ENT_QUOTES) ?></td>
                        <td class="col-nama"><?= htmlspecialchars($item['nama_bahan'], ENT_QUOTES) ?></td>
                        <td class="col-satuan"><?= htmlspecialchars($item['satuan'], ENT_QUOTES) ?></td>
                        <td class="col-stok"><?= htmlspecialchars($item['stok_saat_ini'], ENT_QUOTES) ?></td>
                        <td class="col-restok"><?= htmlspecialchars($item['level_restok'], ENT_QUOTES) ?></td>
                        <td class="col-harga">Rp <?= number_format($item['harga_beli'],0,',','.') ?></td>
                        <td class="col-status">
                          <?php if ($item['stok_saat_ini'] <= $item['level_restok']): ?>
                            <span class="badge bg-danger">Rendah</span>
                          <?php else: ?>
                            <span class="badge bg-success">Aman</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end col-aksi">
                          <button class="btn btn-sm btn-primary" onclick='editItem(<?= htmlspecialchars(json_encode($item), JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                            <i class="bi bi-pencil"></i>
                          </button>
                          <button class="btn btn-sm btn-danger" onclick="deleteItem(<?= (int)$item['id'] ?>)">
                            <i class="bi bi-trash"></i>
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
                          // show limited page range for long lists (small improvement)
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

  <!-- Modal Add -->
  <div class="modal fade" id="modalAdd" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Tambah Bahan Baku</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6"><div class="mb-3"><label class="form-label">Kode Bahan</label><input type="text" class="form-control" name="kode_bahan" required></div></div>
          <div class="col-md-6"><div class="mb-3"><label class="form-label">Nama Bahan</label><input type="text" class="form-control" name="nama_bahan" required></div></div>
        </div>
        <div class="row">
          <div class="col-md-4"><div class="mb-3"><label class="form-label">Satuan</label>
            <select class="form-control" name="satuan" required>
              <option value="">Pilih Satuan</option>
              <option value="kg">kg</option><option value="liter">liter</option><option value="pcs">pcs</option><option value="gram">gram</option><option value="ml">ml</option>
            </select>
          </div></div>
          <div class="col-md-4"><div class="mb-3"><label class="form-label">Stok Awal</label><input type="number" step="0.01" class="form-control" name="stok_saat_ini" value="0" required></div></div>
          <div class="col-md-4"><div class="mb-3"><label class="form-label">Level Restok</label><input type="number" step="0.01" class="form-control" name="level_restok" required></div></div>
        </div>
        <div class="row">
          <div class="col-md-6"><div class="mb-3"><label class="form-label">Harga Beli</label><input type="number" step="0.01" class="form-control" name="harga_beli" required></div></div>
          <div class="col-md-6"><div class="mb-3"><label class="form-label">Keterangan</label><input type="text" class="form-control" name="keterangan"></div></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
    </form>
  </div></div></div>

  <!-- Modal Edit -->
  <div class="modal fade" id="modalEdit" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Edit Bahan Baku</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6"><div class="mb-3"><label class="form-label">Kode Bahan</label><input type="text" class="form-control" name="kode_bahan" id="edit_kode_bahan" required></div></div>
          <div class="col-md-6"><div class="mb-3"><label class="form-label">Nama Bahan</label><input type="text" class="form-control" name="nama_bahan" id="edit_nama_bahan" required></div></div>
        </div>
        <div class="row">
          <div class="col-md-4"><div class="mb-3"><label class="form-label">Satuan</label>
            <select class="form-control" name="satuan" id="edit_satuan" required>
              <option value="">Pilih Satuan</option><option value="kg">kg</option><option value="liter">liter</option><option value="pcs">pcs</option><option value="gram">gram</option><option value="ml">ml</option>
            </select>
          </div></div>
          <div class="col-md-4"><div class="mb-3"><label class="form-label">Stok Saat Ini</label><input type="number" step="0.01" class="form-control" name="stok_saat_ini" id="edit_stok_saat_ini" required></div></div>
          <div class="col-md-4"><div class="mb-3"><label class="form-label">Level Restok</label><input type="number" step="0.01" class="form-control" name="level_restok" id="edit_level_restok" required></div></div>
        </div>
        <div class="row">
          <div class="col-md-6"><div class="mb-3"><label class="form-label">Harga Beli</label><input type="number" step="0.01" class="form-control" name="harga_beli" id="edit_harga_beli" required></div></div>
          <div class="col-md-6"><div class="mb-3"><label class="form-label">Keterangan</label><input type="text" class="form-control" name="keterangan" id="edit_keterangan"></div></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div></div></div>

  <!-- Delete form -->
  <form method="POST" action="" id="deleteForm"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"></form>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const itemsData = <?= json_encode($allItems, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;

    function editItem(item) {
        document.getElementById('edit_id').value = item.id;
        document.getElementById('edit_kode_bahan').value = item.kode_bahan;
        document.getElementById('edit_nama_bahan').value = item.nama_bahan;
        document.getElementById('edit_satuan').value = item.satuan;
        document.getElementById('edit_stok_saat_ini').value = item.stok_saat_ini;
        document.getElementById('edit_level_restok').value = item.level_restok;
        document.getElementById('edit_harga_beli').value = item.harga_beli;
        document.getElementById('edit_keterangan').value = item.keterangan;
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    // helper: cari item berdasarkan id lalu panggil editItem
    function editItemById(id) {
      const it = itemsData.find(i => parseInt(i.id,10) === parseInt(id,10));
      if (it) editItem(it);
    }

    function deleteItem(id) {
        if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    // ====== GLOBAL SEARCH (search across all pages) ======
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
          tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada hasil pencarian.</td></tr>';
          return;
        }

        const rows = list.map(item => {
          const harga = (item.harga_beli !== undefined && item.harga_beli !== null && item.harga_beli !== '') ? 'Rp ' + Number(item.harga_beli).toLocaleString('id-ID') : '-';
          const status = (parseFloat(item.stok_saat_ini) <= parseFloat(item.level_restok)) ? '<span class="badge bg-danger">Rendah</span>' : '<span class="badge bg-success">Aman</span>';

          return `<tr>
            <td class="col-kode">${escapeHtml(item.kode_bahan)}</td>
            <td class="col-nama">${escapeHtml(item.nama_bahan)}</td>
            <td class="col-satuan">${escapeHtml(item.satuan)}</td>
            <td class="col-stok">${escapeHtml(item.stok_saat_ini)}</td>
            <td class="col-restok">${escapeHtml(item.level_restok)}</td>
            <td class="col-harga">${harga}</td>
            <td class="col-status">${status}</td>
            <td class="text-end col-aksi">
              <button class="btn btn-sm btn-primary" onclick="editItemById(${parseInt(item.id,10)})"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-danger" onclick="deleteItem(${parseInt(item.id,10)})"><i class="bi bi-trash"></i></button>
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

      // ketika user mengetik: jika kosong -> reload (kembalikan pagination server-side)
      input.addEventListener('input', (e) => {
        const q = (e.target.value || '').trim();
        if (q === '') {
          // kembalikan tampilan paginasi server-side
          // Reload aman karena perubahan CRUD sudah disubmit via POST sebelumnya
          window.location.reload();
          return;
        }

        const matches = searchAll(q);
        renderRows(matches);
      });

      // shortcut '/' untuk fokus
      document.addEventListener('keydown', (e) => {
        if (e.key === '/' && document.activeElement !== input) {
          e.preventDefault();
          input.focus();
          input.select();
        }
      });

    })();
    // ====== END GLOBAL SEARCH ======

    // Sidebar collapse: persist state, icons-only strip (same behavior as index)
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

      if (btn) {
        btn.addEventListener('click', (e) => {
          const isCollapsed = sidebar.classList.contains('collapsed');
          setCollapsed(!isCollapsed);
        });
      }

      document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
          const isCollapsed = sidebar.classList.contains('collapsed');
          setCollapsed(!isCollapsed);
        }
      });
    })();

    // Appearance toggle (dark-mode) - same keys as index (hf_appearance_dark)
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
  </script>
</body>
</html>