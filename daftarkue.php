<?php
// daftarkue.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

 $cakes = [];
 $database = new Database();
 $db = $database->connect();

if ($db) {
    // --- PERUBAHAN 1: Query sekarang mengambil kolom deskripsi, foto, dan harga_jual ---
    $sql = "SELECT id, kode_kue, nama_kue, deskripsi, foto, harga_jual FROM kue ORDER BY nama_kue ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    $cakes_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop untuk mengambil resep setiap kue
    foreach ($cakes_data as $index => $cake) {
        $resep_sql = "SELECT rk.bahan_id, rk.qty_per_pcs, rk.satuan, bb.nama_bahan 
                      FROM resep_kue rk 
                      JOIN bahan_baku bb ON rk.bahan_id = bb.id 
                      WHERE rk.kue_id = :kue_id";
        
        try {
            $resep_stmt = $db->prepare($resep_sql); 
            $resep_stmt->bindParam(':kue_id', $cake['id'], PDO::PARAM_INT);
            $resep_stmt->execute();
            $cakes_data[$index]['resep'] = $resep_stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $cakes_data[$index]['resep'] = [];
        }
    }
    $cakes = $cakes_data;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Daftar Kue - HappyFood Inventory</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
      /* ... (CSS tidak berubah, saya akan menyalinnya utuh) ... */
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
      body.dark-mode .app {
        background: var(--page-bg) !important;
        color: inherit;
      }

      body.dark-mode .sidebar {
        box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
      }

      body.dark-mode .nav-vertical a.active,
      body.dark-mode .nav-vertical a:hover {
        background: rgba(111,66,193,0.12) !important;
        box-shadow: none !important;
      }

      body.dark-mode .sidebar .tools {
        border-top: 1px solid var(--soft-border);
      }

      body.dark-mode .switch { background: #3b455b; }
      body.dark-mode .switch.on { background: linear-gradient(135deg, var(--accent), var(--accent-2)); }
      body.dark-mode .switch .knob { background: #e6e9ff; }

      body.dark-mode .logout-btn {
        background: #243142;
        color: #e6e9ff;
      }

      body.dark-mode .header-card {
        background: var(--card-bg);
        border: 1px solid var(--soft-border);
        box-shadow: none;
      }

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
      .brand .logo {
        width:36px;height:36px;border-radius:8px;
        display:flex;align-items:center;justify-content:center;font-weight:700;
        background: transparent;
        box-shadow: 0 6px 18px rgba(99,63,171,0.06);
        overflow:hidden;
      }
      .brand h5{ margin:0; font-size:15px; font-weight:700; color:var(--sidebar-ink); }
      .brand small{ display:block; font-size:12px; color:var(--muted); }
      .brand-text { display:block; }
      .logo-img { width:100%; height:100%; object-fit:contain; display:block; }

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

      @media (max-width:1000px){
        .sidebar{ display:none; }
        .search-box{ width:100%; }
        .app{ padding:12px; }
      }

      /* ============ DAFTAR KUE STYLES ============ */

      .cakes-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
        margin-top:10px;
        margin-bottom:18px;
      }
      .cakes-header h4 {
        margin:0 0 4px 0;
      }
      .cakes-header p {
        margin:0;
        color:var(--muted);
        font-size:14px;
      }

      .btn-add-cake {
        border-radius:999px;
        font-weight:600;
        padding:8px 16px;
        border:none;
        background:linear-gradient(135deg, #f97373, #fb7185);
        color:#fff;
        display:inline-flex;
        align-items:center;
        gap:8px;
        box-shadow:0 10px 25px rgba(248,113,113,0.28);
      }

      .cake-grid {
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
        gap:18px;
      }

      .cake-card {
        background:var(--card-bg);
        border-radius:18px;
        padding:16px;
        box-shadow:0 10px 26px rgba(15,16,30,0.05);
        display:flex;
        flex-direction:column;
        gap:12px;
        position:relative;
        overflow:hidden;
        transition:transform .15s ease, box-shadow .15s ease;
      }
      .cake-card:hover {
        transform:translateY(-4px);
        box-shadow:0 16px 30px rgba(15,16,30,0.08);
      }

      .cake-thumb-wrap {
        background:linear-gradient(135deg, #fee2e2, #fee4e2);
        border-radius:16px;
        padding:10px;
        display:flex;
        justify-content:center;
        align-items:center;
        position:relative;
        overflow:hidden;
        min-height:160px;
      }
      .cake-thumb-wrap img{
        max-width:100%;
        max-height:160px;
        object-fit:cover;
        border-radius:12px;
      }

      .badge-best {
        position:absolute;
        left:10px;
        top:10px;
        border-radius:999px;
        padding:4px 10px;
        background:rgba(251,146,60,0.96);
        color:#fff;
        font-size:11px;
        font-weight:700;
        display:flex;
        align-items:center;
        gap:4px;
      }

      .cake-info-title {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:8px;
        margin-top:4px;
      }
      .cake-name {
        font-weight:700;
        font-size:16px;
      }
      .cake-price {
        font-weight:800;
        font-size:16px;
      }

      .cake-desc {
        font-size:13px;
        color:var(--muted);
        margin:3px 0 4px 0;
      }

      .cake-resep {
        background-color: rgba(111,66,193,0.05);
        border-radius:12px;
        padding:10px;
        margin-bottom:10px;
      }
      .cake-resep-title {
        font-size:13px;
        font-weight:700;
        margin-bottom:6px;
        color:var(--accent);
      }
      .cake-resep-list {
        font-size:12px;
        color:var(--muted);
        margin:0;
        padding-left:20px;
      }
      .cake-resep-list li {
        margin-bottom:4px;
      }

      .cake-footer {
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-top:4px;
      }
      .cake-meta-small {
        font-size:11px;
        color:var(--muted);
        display:flex;
        align-items:center;
        gap:4px;
      }

      .btn-cake-detail {
        border-radius:999px;
        padding:6px 12px;
        font-size:12px;
        font-weight:600;
        border:none;
        background:#eef2ff;
        color:#4f46e5;
        display:inline-flex;
        align-items:center;
        gap:4px;
      }

      .btn-buat-kue {
        border-radius:999px;
        padding:6px 12px;
        font-size:12px;
        font-weight:600;
        border:none;
        background:linear-gradient(135deg, #10b981, #059669);
        color:#fff;
        display:inline-flex;
        align-items:center;
        gap:4px;
        cursor:pointer;
      }

      body.dark-mode .cake-card {
        background:#1a2334;
        box-shadow:none;
        border:1px solid rgba(255,255,255,0.04);
      }
      body.dark-mode .cake-thumb-wrap {
        background:linear-gradient(135deg, #1f2937, #111827);
      }
      body.dark-mode .cake-desc,
      body.dark-mode .cake-meta-small {
        color:#9aa0b4;
      }
      body.dark-mode .cake-resep {
        background-color: rgba(111,66,193,0.1);
      }
      body.dark-mode .btn-cake-detail {
        background:#0b1220;
        color:#e6e9ff;
      }
      body.dark-mode .btn-buat-kue {
        background:linear-gradient(135deg, #059669, #047857);
      }

      /* Modal untuk konfirmasi pembuatan kue */
      .modal-content {
        border-radius:16px;
        border:none;
      }
      .modal-header {
        border-bottom:1px solid var(--soft-border);
        background:var(--card-bg);
        border-radius:16px 16px 0 0;
      }
      .modal-body {
        background:var(--card-bg);
      }
      .modal-footer {
        border-top:1px solid var(--soft-border);
        background:var(--card-bg);
        border-radius:0 0 16px 16px;
      }
      body.dark-mode .modal-content {
        background:var(--card-bg);
        color:var(--sidebar-ink);
      }
      body.dark-mode .modal-header,
      body.dark-mode .modal-body,
      body.dark-mode .modal-footer {
        background:var(--card-bg);
        border-color:var(--soft-border);
      }
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
                    <a href="bahan_baku.php" title="Bahan Baku"><i class="bi bi-box-seam"></i><span class="label">Bahan Baku</span></a>
                    <a href="laporan.php" title="Laporan Stok"><i class="bi bi-file-earmark-text"></i><span class="label">Laporan Stok</span></a>
                    <a href="daftarkue.php" class="active" title="Daftar Kue">
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
                            <input type="search" placeholder="Cari kue... (tekan '/' untuk fokus)" id="searchInput" />
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
                        <h4 style="margin:0 0 6px 0;">Daftar Kue</h4>
                        <div style="color:var(--muted)">Kelola koleksi kue yang dijual di toko kamu 🍰</div>
                    </div>
                    <div id="currentTime" style="text-align:right; color:var(--muted)"></div>
                </div>

                <section class="mt-2">
                    <div class="cakes-header">
                        <div>
                            <h4>Koleksi Kue</h4>
                            <p>Lihat detail dan buat kue sesuai kebutuhan.</p>
                        </div>
                        <button class="btn-add-cake" onclick="window.location.href='kue_tambah.php'">
                            <i class="bi bi-plus-circle"></i>
                            Tambah Kue
                        </button>
                    </div>

                    <div class="cake-grid" id="cakeGrid">
                        <?php if (empty($cakes)): ?>
                            <div class="text-muted" style="font-size:14px;">Belum ada data kue. Tambahkan kue pertama kamu dengan tombol <b>Tambah Kue</b>.</div>
                        <?php else: ?>
                            <?php foreach ($cakes as $cake): 
                                // --- PERUBAHAN 2: Variabel sekarang mengambil dari database, dengan nilai default jika kosong ---
                                $harga = is_numeric($cake['harga_jual']) ? number_format($cake['harga_jual'],0,',','.') : 'Rp -';
                                $gambar = !empty($cake['foto']) ? 'uploads/kue/' . $cake['foto'] : 'assets/cake-placeholder.png';
                                $deskripsi_tampil = $cake['deskripsi'] ?: 'Belum ada deskripsi.';
                            ?>
                            <article class="cake-card" 
                                     data-name="<?= htmlspecialchars(mb_strtolower($cake['nama_kue']), ENT_QUOTES) ?>"> 
                                <div class="cake-thumb-wrap">
                                    <img src="<?= htmlspecialchars($gambar, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($cake['nama_kue'], ENT_QUOTES) ?>">
                                    <?php if (!empty($cake['deskripsi']) && stripos($cake['deskripsi'], 'best') !== false): ?>
                                      <div class="badge-best"><i class="bi bi-heart-fill"></i> Best Seller</div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <div class="cake-info-title">
                                        <div>
                                            <div class="cake-name"><?= htmlspecialchars($cake['nama_kue'], ENT_QUOTES) ?></div>
                                        </div>
                                        <div class="cake-price">Rp <?= $harga ?></div>
                                    </div>
                                    <div class="cake-desc">
                                        <?= htmlspecialchars($deskripsi_tampil, ENT_QUOTES) ?>
                                    </div>
                                    
                                    <!-- Resep Kue -->
                                    <div class="cake-resep">
                                        <div class="cake-resep-title">
                                            <i class="bi bi-journal-text"></i> Resep Kue
                                        </div>
                                        <?php if (!empty($cake['resep'])): ?>
                                            <ul class="cake-resep-list">
                                                <?php foreach ($cake['resep'] as $item): ?>
                                                    <li><?= htmlspecialchars($item['nama_bahan'], ENT_QUOTES) ?>: <?= htmlspecialchars($item['qty_per_pcs'], ENT_QUOTES) ?> <?= htmlspecialchars($item['satuan'], ENT_QUOTES) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <div style="font-size:12px; color:var(--muted);">Belum ada resep untuk kue ini.</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="cake-footer">
                                        <div class="cake-meta-small">
                                            <i class="bi bi-basket"></i> Stok Tersedia
                                        </div>
                                        <div>
                                            <button class="btn-cake-detail" onclick="window.location.href='kue_detail.php?id=<?= (int)$cake['id'] ?>'">
                                                Detail <i class="bi bi-arrow-right-short"></i>
                                            </button>
                                            <button class="btn-buat-kue" onclick="showBuatKueModal(<?= (int)$cake['id'] ?>, '<?= htmlspecialchars($cake['nama_kue'], ENT_QUOTES) ?>')">
                                                <i class="bi bi-check-circle"></i> Buat Kue
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
                </main>
        </div>
    </div>

    <!-- Modal Konfirmasi Pembuatan Kue -->
    <div class="modal fade" id="buatKueModal" tabindex="-1" aria-labelledby="buatKueModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buatKueModalLabel">Buat Kue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin membuat <strong id="namaKueModal"></strong>?</p>
                    <p>Stok bahan akan dikurangi sesuai dengan resep kue.</p>
                    <div class="mb-3">
                        <label for="jumlahKue" class="form-label">Jumlah Kue</label>
                        <input type="number" class="form-control" id="jumlahKue" min="1" value="1">
                    </div>
                    <div id="detailResep"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="confirmBuatKue">Buat Kue</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      // Sidebar collapse
      (function(){
        const sidebar = document.getElementById('appSidebar');
        const btn = document.getElementById('btnCollapseInline');

        function setCollapsed(collapsed){
          if (collapsed) sidebar.classList.add('collapsed');
          else sidebar.classList.remove('collapsed');
          localStorage.setItem('hf_sidebar_collapsed', collapsed ? '1' : '0');
        }

        const stored = localStorage.getItem('hf_sidebar_collapsed');
        const initial = stored === '1';
        setCollapsed(initial);

        btn.addEventListener('click', () => {
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

      // Client-side clock
      (function(){
        const el = document.getElementById('currentTime');
        if (!el) return;
        const fmt = new Intl.DateTimeFormat('id-ID', {
          day:'2-digit', month:'short', year:'numeric',
          hour:'2-digit', minute:'2-digit', hour12:false
        });
        function updateTime(){ el.textContent = fmt.format(new Date()).replace(',', ''); }
        updateTime();
        setInterval(updateTime, 15000);
      })();

      // Search untuk kartu kue
      (function(){
        const input = document.getElementById('searchInput');
        const cards = Array.from(document.querySelectorAll('#cakeGrid .cake-card'));
        const emptyState = document.createElement('div');
        emptyState.className = 'text-muted';
        emptyState.style.fontSize = '14px';
        emptyState.textContent = 'Tidak ada kue yang cocok dengan kriteria pencarian.';
        emptyState.style.display = 'none';
        document.getElementById('cakeGrid').appendChild(emptyState);

        function applySearch() {
          const q = (input.value || '').toLowerCase().trim();
          let visibleCount = 0;

          cards.forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();

            const matchName = q === '' || name.indexOf(q) !== -1;

            if (matchName) {
              card.style.display = '';
              visibleCount++;
            } else {
              card.style.display = 'none';
            }
          });
          
          emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        if (input) {
          input.addEventListener('input', applySearch);
          document.addEventListener('keydown', (e) => {
            if (e.key === '/' && document.activeElement !== input) {
              e.preventDefault();
              input.focus();
              input.select();
            }
          });
        }
      })();

      // Modal untuk pembuatan kue
      let currentCakeId = null;
      let currentResep = [];
      
      function showBuatKueModal(cakeId, namaKue) {
        currentCakeId = cakeId;
        
        // Ambil data resep kue dari server
        fetch(`get_resep_kue.php?id=${cakeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentResep = data.resep;
                    document.getElementById('namaKueModal').textContent = namaKue;
                    
                    // Tampilkan detail resep
                    const detailResepEl = document.getElementById('detailResep');
                    detailResepEl.innerHTML = '<h6>Resep yang akan digunakan:</h6><ul>';
                    
                    data.resep.forEach(item => {
                        // --- PERUBAHAN 3: Sesuaikan dengan nama kolom 'satuan' ---
                        detailResepEl.innerHTML += `<li>${item.nama_bahan}: ${item.qty_per_pcs} ${item.satuan} per kue</li>`;
                    });
                    
                    detailResepEl.innerHTML += '</ul>';
                    
                    // Tampilkan modal
                    const modal = new bootstrap.Modal(document.getElementById('buatKueModal'));
                    modal.show();
                } else {
                    alert('Gagal memuat resep kue: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat memuat resep kue');
            });
      }
      
      // Konfirmasi pembuatan kue
      document.getElementById('confirmBuatKue').addEventListener('click', function() {
          const jumlahKue = parseInt(document.getElementById('jumlahKue').value);
          
          if (jumlahKue < 1) {
              alert('Jumlah kue harus lebih dari 0');
              return;
          }
          
          // Kirim permintaan ke server untuk membuat kue
          fetch('buat_kue.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                  cake_id: currentCakeId,
                  jumlah: jumlahKue
              })
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  // Tutup modal
                  const modal = bootstrap.Modal.getInstance(document.getElementById('buatKueModal'));
                  modal.hide();
                  
                  // Tampilkan pesan sukses
                  alert(`Berhasil membuat ${jumlahKue} ${document.getElementById('namaKueModal').textContent}! Stok bahan telah diperbarui.`);
                  
                  // Refresh halaman untuk menampilkan data terbaru
                  window.location.reload();
              } else {
                  alert('Gagal membuat kue: ' + data.message);
              }
          })
          .catch(error => {
              console.error('Error:', error);
              alert('Terjadi kesalahan saat membuat kue');
          });
      });
    </script>
</body>
</html>