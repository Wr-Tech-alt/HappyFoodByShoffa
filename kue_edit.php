<?php
// kue_edit.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ambil ID kue dari query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID kue tidak valid.');
}
$kue_id = (int)$_GET['id'];

$database = new Database();
$db = $database->connect();
if (!$db) {
    die('Gagal koneksi ke database.');
}

$success_message = '';
$error_message   = '';

// Ambil data kue
$sql_kue = "SELECT id, kode_kue, nama_kue, deskripsi, foto, harga_jual 
            FROM kue WHERE id = :id";
$stmt_kue = $db->prepare($sql_kue);
$stmt_kue->bindParam(':id', $kue_id, PDO::PARAM_INT);
$stmt_kue->execute();
$kue = $stmt_kue->fetch(PDO::FETCH_ASSOC);

if (!$kue) {
    die('Data kue tidak ditemukan.');
}

// Ambil resep kue
$sql_resep = "SELECT bahan_id, qty_per_pcs, satuan 
              FROM resep_kue WHERE kue_id = :id";
$stmt_resep = $db->prepare($sql_resep);
$stmt_resep->bindParam(':id', $kue_id, PDO::PARAM_INT);
$stmt_resep->execute();
$resep_rows = $stmt_resep->fetchAll(PDO::FETCH_ASSOC);

// Map resep berdasarkan bahan_id biar gampang isi form
$resep_map = [];
foreach ($resep_rows as $row) {
    $resep_map[$row['bahan_id']] = $row;
}

// Ambil semua bahan baku untuk pilihan resep
$sql_bahan = "SELECT id, nama_bahan, satuan 
              FROM bahan_baku ORDER BY nama_bahan ASC";
$stmt_bahan = $db->prepare($sql_bahan);
$stmt_bahan->execute();
$bahan_list = $stmt_bahan->fetchAll(PDO::FETCH_ASSOC);

// Proses UPDATE saat form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_kue   = $_POST['kode_kue'] ?? '';
    $nama_kue   = $_POST['nama_kue'] ?? '';
    $deskripsi  = $_POST['deskripsi'] ?? '';
    $harga_jual = $_POST['harga_jual'] ?? 0;

    // Foto lama
    $old_foto = $kue['foto'];
    $nama_foto_baru = $old_foto;

    // Cek apakah ada upload foto baru
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['foto']['tmp_name'];
        $file_name = basename($_FILES['foto']['name']);
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_type, $allowed_types)) {
            $new_file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
            $upload_dir = 'uploads/kue/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $nama_foto_baru = $new_file_name;

                // Opsional: hapus foto lama jika ada dan berbeda
                if (!empty($old_foto) && $old_foto !== $nama_foto_baru) {
                    $old_path = $upload_dir . $old_foto;
                    if (is_file($old_path)) {
                        @unlink($old_path);
                    }
                }
            }
        }
    }

    try {
        $db->beginTransaction();

        // Update data kue (ID tidak diubah)
        $sql_update_kue = "UPDATE kue
                           SET kode_kue = :kode_kue,
                               nama_kue = :nama_kue,
                               deskripsi = :deskripsi,
                               foto = :foto,
                               harga_jual = :harga_jual
                           WHERE id = :id";
        $stmt_update = $db->prepare($sql_update_kue);
        $stmt_update->bindParam(':kode_kue', $kode_kue);
        $stmt_update->bindParam(':nama_kue', $nama_kue);
        $stmt_update->bindParam(':deskripsi', $deskripsi);
        $stmt_update->bindParam(':foto', $nama_foto_baru);
        $stmt_update->bindParam(':harga_jual', $harga_jual);
        $stmt_update->bindParam(':id', $kue_id, PDO::PARAM_INT);
        $stmt_update->execute();

        // Hapus resep lama
        $sql_delete_resep = "DELETE FROM resep_kue WHERE kue_id = :id";
        $stmt_del = $db->prepare($sql_delete_resep);
        $stmt_del->bindParam(':id', $kue_id, PDO::PARAM_INT);
        $stmt_del->execute();

        // Tambah resep baru dari input
        if (!empty($_POST['ingredients']) && is_array($_POST['ingredients'])) {
            $sql_insert_resep = "INSERT INTO resep_kue (kue_id, bahan_id, qty_per_pcs, satuan)
                                 VALUES (:kue_id, :bahan_id, :qty_per_pcs, :satuan)";
            $stmt_insert_resep = $db->prepare($sql_insert_resep);

            foreach ($_POST['ingredients'] as $bahan_id => $qty) {
                $qty = trim($qty);
                if ($qty === '' || !is_numeric($qty) || $qty <= 0) {
                    continue;
                }

                // Ambil satuan dari tabel bahan_baku
                $unit_sql = "SELECT satuan FROM bahan_baku WHERE id = :bahan_id";
                $unit_stmt = $db->prepare($unit_sql);
                $unit_stmt->bindParam(':bahan_id', $bahan_id, PDO::PARAM_INT);
                $unit_stmt->execute();
                $satuan = $unit_stmt->fetchColumn();

                if ($satuan === false) {
                    $satuan = ''; // fallback
                }

                $stmt_insert_resep->bindParam(':kue_id', $kue_id, PDO::PARAM_INT);
                $stmt_insert_resep->bindParam(':bahan_id', $bahan_id, PDO::PARAM_INT);
                $stmt_insert_resep->bindParam(':qty_per_pcs', $qty);
                $stmt_insert_resep->bindParam(':satuan', $satuan);
                $stmt_insert_resep->execute();
            }
        }

        $db->commit();

        $success_message = 'Data kue dan resep berhasil diperbarui.';

        // Refresh data kue & resep untuk tampilkan nilai terbaru di form
        $stmt_kue->execute();
        $kue = $stmt_kue->fetch(PDO::FETCH_ASSOC);

        $stmt_resep->execute();
        $resep_rows = $stmt_resep->fetchAll(PDO::FETCH_ASSOC);
        $resep_map = [];
        foreach ($resep_rows as $row) {
            $resep_map[$row['bahan_id']] = $row;
        }

    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message = 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage();
    }
}

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Edit Kue - HappyFood Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
      :root{--accent:#6f42c1;--accent-2:#7c4dff;--muted:#6c757d;--page-bg:#f6f7fb;--card-bg:#ffffff;--sidebar-bg:#ffffff;--sidebar-ink:#2b2b3b;--soft-border:#eef0f4;}
      html,body{height:100%; min-height:100%; margin:0; font-family: "Inter", "Raleway", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:var(--page-bg); color:#222;}
      body.dark-mode{--page-bg: #0f172a;--card-bg: #1a2334;--sidebar-bg: #1a2334;--sidebar-ink: #e6e9ff;--muted: #9aa0b4;--soft-border: rgba(255,255,255,0.06); background: var(--page-bg); color: #e6e9ff;}
      .container-fluid { background: transparent; min-height:100vh; padding:0 16px; box-sizing:border-box; }
      .app { min-height:100vh; display:flex; gap:22px; padding:22px; box-sizing:border-box; position:relative; background: transparent; }
      body.dark-mode .container-fluid, body.dark-mode .app { background: var(--page-bg) !important; color: inherit; }
      body.dark-mode .sidebar { box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important; }
      body.dark-mode .nav-vertical a.active, body.dark-mode .nav-vertical a:hover { background: rgba(111,66,193,0.12) !important; box-shadow: none !important; }
      body.dark-mode .sidebar .tools { border-top: 1px solid var(--soft-border); }
      body.dark-mode .switch { background: #3b455b; }
      body.dark-mode .switch.on { background: linear-gradient(135deg, var(--accent), var(--accent-2)); }
      body.dark-mode .switch .knob { background: #e6e9ff; }
      body.dark-mode .logout-btn { background: #243142; color: #e6e9ff; }
      .sidebar { width:220px; min-width:220px; background:var(--sidebar-bg); color:var(--sidebar-ink); padding:18px; border-radius:0 14px 14px 0; box-shadow: 0 10px 30px rgba(20,20,40,0.04); display:flex; flex-direction:column; height:100vh; box-sizing:border-box; transition: width .18s ease, padding .18s ease, border-radius .18s ease; overflow:hidden; }
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
      .sidebar.collapsed .tools-title, .sidebar.collapsed .appearance-row > div:first-child, .sidebar.collapsed .tools .label { display:none; }
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

      .main { flex:1; min-height:100vh; display:flex; flex-direction:column; gap:18px; }
      .topbar { display:flex; justify-content:space-between; gap:12px; align-items:center; }
      .userbox { display:flex; gap:12px; align-items:center; }
      .avatar { width:40px; height:40px; border-radius:50%; display:flex;align-items:center;justify-content:center; background:linear-gradient(135deg,var(--accent),var(--accent-2)); color:#fff; font-weight:700; }
      .header-card { background: linear-gradient(90deg, rgba(249,115,172,0.06), rgba(124,77,255,0.06)); border-radius:12px; padding:16px; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 8px 24px rgba(18,17,40,0.03); }
      body.dark-mode .header-card { background: var(--card-bg); border: 1px solid var(--soft-border); box-shadow: none; }
      .search-wrap{display:flex;align-items:center;gap:10px;}
      .search-box input{display:none;} /* ga perlu search di halaman edit */

      .form-section-card {
        background:var(--card-bg);
        border-radius:16px;
        padding:18px;
        box-shadow:0 10px 26px rgba(15,16,30,0.05);
        margin-bottom:16px;
        border:1px solid var(--soft-border);
      }
      body.dark-mode .form-section-card {
        background:#1a2334;
        box-shadow:none;
        border:1px solid rgba(255,255,255,0.04);
      }
      .ingredients-wrapper{
        max-height:420px;
        overflow-y:auto;
        padding-right:4px;
      }

      .form-text{
        color: var(--muted);
      }
      body.dark-mode .form-text{
        color: #e6e9ff;
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
                <a href="daftarkue.php" class="active" title="Daftar Kue"><i class="bi bi-basket"></i><span class="label">Daftar Kue</span></a>
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
                        <input type="search" />
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

            <div class="header-card mb-3">
                <div>
                    <h4 style="margin:0 0 6px 0;">Edit Kue</h4>
                    <div style="color:var(--muted)">Ubah informasi kue dan resepnya. ID kue tidak dapat diubah.</div>
                </div>
                <div>
                    <a href="daftarkue.php" class="btn btn-sm btn-primary" style="border-radius:999px;">
                        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Kue
                    </a>
                </div>
            </div>

            <div class="mb-2">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message, ENT_QUOTES) ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message, ENT_QUOTES) ?></div>
                <?php endif; ?>
            </div>

            <form method="post" enctype="multipart/form-data" class="mb-5">
                <div class="row">
                    <div class="col-lg-5">
                        <div class="form-section-card">
                            <h5 class="mb-3">Informasi Kue</h5>
                            <div class="mb-3">
                                <label class="form-label">ID Kue</label>
                                <input type="text" class="form-control" value="<?= (int)$kue['id'] ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="kode_kue" class="form-label">Kode Kue</label>
                                <input type="text" class="form-control" id="kode_kue" name="kode_kue"
                                       value="<?= htmlspecialchars($kue['kode_kue'], ENT_QUOTES) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="nama_kue" class="form-label">Nama Kue</label>
                                <input type="text" class="form-control" id="nama_kue" name="nama_kue"
                                       value="<?= htmlspecialchars($kue['nama_kue'], ENT_QUOTES) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= htmlspecialchars($kue['deskripsi'], ENT_QUOTES) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="harga_jual" class="form-label">Harga Jual</label>
                                <input type="number" class="form-control" id="harga_jual" name="harga_jual"
                                       min="0" step="0.01"
                                       value="<?= htmlspecialchars($kue['harga_jual'], ENT_QUOTES) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Foto Saat Ini</label>
                                <div>
                                    <?php
                                        $gambar = !empty($kue['foto']) ? 'uploads/kue/' . $kue['foto'] : 'assets/cake-placeholder.png';
                                    ?>
                                    <img src="<?= htmlspecialchars($gambar, ENT_QUOTES) ?>" alt="Foto kue" style="max-width:100%; border-radius:12px;">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="foto" class="form-label">Ganti Foto (Opsional)</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                                <div class="form-text">Jika tidak diisi, foto tetap menggunakan yang lama.</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="form-section-card">
                            <h5 class="mb-3">Resep Kue</h5>
                            <p class="text-muted" style="font-size:13px;">
                                Pilih bahan-bahan yang digunakan untuk membuat <strong><?= htmlspecialchars($kue['nama_kue'], ENT_QUOTES) ?></strong>
                                dan atur jumlah pemakaian per 1 pcs kue.
                            </p>
                            <div class="ingredients-wrapper">
                                <div class="row">
                                    <?php foreach ($bahan_list as $ingredient):
                                        $id_bahan = (int)$ingredient['id'];
                                        $checked  = isset($resep_map[$id_bahan]);
                                        $qty_val  = $checked ? $resep_map[$id_bahan]['qty_per_pcs'] : '';
                                        $display  = $checked ? 'flex' : 'none';
                                    ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input ingredient-checkbox"
                                                type="checkbox"
                                                value="<?= $id_bahan ?>"
                                                id="ingredient_<?= $id_bahan ?>"
                                                <?= $checked ? 'checked' : '' ?>
                                            >
                                            <label class="form-check-label" for="ingredient_<?= $id_bahan ?>">
                                                <?= htmlspecialchars($ingredient['nama_bahan'], ENT_QUOTES) ?>
                                            </label>
                                            <div class="input-group input-group-sm mt-1 ingredient-qty-group"
                                                 style="display: <?= $display ?>;">
                                                <input
                                                    type="number"
                                                    class="form-control"
                                                    name="ingredients[<?= $id_bahan ?>]"
                                                    placeholder="Jumlah per pcs"
                                                    min="0"
                                                    step="0.01"
                                                    value="<?= htmlspecialchars($qty_val, ENT_QUOTES) ?>"
                                                >
                                                <span class="input-group-text">
                                                    <?= htmlspecialchars($ingredient['satuan'], ENT_QUOTES) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Simpan Perubahan
                                </button>
                                <a href="daftarkue.php" class="btn btn-secondary">
                                    Batal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

  // Sidebar collapse
  (function(){
    const sidebar = document.getElementById('appSidebar');
    const btn = document.getElementById('btnCollapseInline');
    if (!sidebar || !btn) return;
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
    if (!switchEl) return;
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
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        setState(!switchEl.classList.contains('on'));
      }
    });
  })();

  // Toggle qty input saat ceklis bahan
  document.querySelectorAll('.ingredient-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const qtyGroup = this.closest('.form-check').querySelector('.ingredient-qty-group');
      if (!qtyGroup) return;
      if (this.checked) {
        qtyGroup.style.display = 'flex';
        const input = qtyGroup.querySelector('input');
        if (input && !input.value) input.focus();
      } else {
        qtyGroup.style.display = 'none';
        const input = qtyGroup.querySelector('input');
        if (input) input.value = '';
      }
    });
  });

});
</script>
</body>
</html>
