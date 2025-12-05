<?php
// kue_tambah.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// --- PROSES TAMBAH KUE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->connect();

    // Ambil data dari form
    $kode_kue = $_POST['kode_kue'];
    $nama_kue = $_POST['nama_kue'];
    $deskripsi = $_POST['deskripsi'];
    $harga_jual = $_POST['harga_jual'];
    
    // --- LOGIKA UPLOAD FOTO ---
    $nama_foto = null; // Default null jika tidak ada foto diupload

    // Periksa apakah ada file yang diupload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = basename($_FILES['foto']['name']);
        $file_size = $_FILES['foto']['size'];
        $file_error = $_FILES['foto']['error'];
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validasi tipe file (hanya gambar)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_type, $allowed_types)) {
            
            // Buat nama file unik untuk menghindari duplikat
            $new_file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
            $upload_dir = 'uploads/kue/';
            
            // Pindahkan file dari folder temporary ke folder uploads/kue/
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $nama_foto = $new_file_name; // Simpan nama file baru
            } else {
                // Jika gagal memindahkan, set pesan error (opsional)
                $error_upload = "Maaf, terjadi kesalahan saat mengupload foto.";
            }
        } else {
            // Jika tipe file tidak sesuai, set pesan error (opsional)
            $error_upload = "Maaf, hanya file gambar (JPG, PNG, GIF) yang diperbolehkan.";
        }
    }

    // --- SIMPAN DATA KE DATABASE ---
    // Gunakan prepared statement untuk keamanan
    $sql = "INSERT INTO kue (kode_kue, nama_kue, deskripsi, foto, harga_jual) 
            VALUES (:kode_kue, :nama_kue, :deskripsi, :foto, :harga_jual)";
            
    $stmt = $db->prepare($sql);
    
    // Bind parameter
    $stmt->bindParam(':kode_kue', $kode_kue);
    $stmt->bindParam(':nama_kue', $nama_kue);
    $stmt->bindParam(':deskripsi', $nama_kue);
    $stmt->bindParam(':foto', $nama_foto); // Bisa null jika tidak ada foto
    $stmt->bindParam(':harga_jual', $harga_jual);

    // Eksekusi query
    if ($stmt->execute()) {
        // Jika berhasil, redirect ke halaman daftar kue
        header('Location: daftarkue.php?status=success');
        exit();
    } else {
        // Jika gagal, tampilkan pesan error
        $error_db = "Gagal menyimpan data kue.";
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Tambah Kue - HappyFood Inventory</title>
    <!-- Gunakan CSS yang sama dengan daftarkue.php untuk konsistensi -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Salin CSS dari daftarkue.php atau buat yang serupa */
        :root{--accent:#6f42c1;--accent-2:#7c4dff;--muted:#6c757d;--page-bg:#f6f7fb;--card-bg:#ffffff;--sidebar-bg:#ffffff;--sidebar-ink:#2b2b3b;--soft-border:#eef0f4;}
        html,body{height:100%; min-height:100%; margin:0; font-family: "Inter", "Raleway", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:var(--page-bg); color:#222;}
        /* ... salin semua CSS lainnya dari daftarkue.php agar tampilan konsisten ... */
        .container-fluid { background: transparent; min-height:100vh; padding:0 16px; box-sizing:border-box; }
        .app { min-height:100vh; display:flex; gap:22px; padding:22px; box-sizing:border-box; position:relative; background: transparent; }
        /* ... dan seterusnya ... */
        .main { flex:1; min-height:100vh; display:flex; flex-direction:column; gap:18px; }
        .card { background:var(--card-bg); border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(15,16,30,0.04); }
        .form-control, .form-select { border-radius:8px; border:1px solid var(--soft-border); }
        .form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25); }
        .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-2)); border: none; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="app">
            <!-- Salin Sidebar dari daftarkue.php -->
            <aside class="sidebar" id="appSidebar" role="navigation" aria-label="Sidebar">
                <!-- ... isi sidebar ... -->
            </aside>

            <main class="main" role="main">
                <div class="card">
                    <h2>Tambah Kue Baru</h2>
                    <p>Isi form di bawah untuk menambahkan kue baru ke koleksi Anda.</p>

                    <!-- Tampilkan pesan error jika ada -->
                    <?php if (isset($error_db)): ?>
                        <div class="alert alert-danger"><?= $error_db ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_upload)): ?>
                        <div class="alert alert-warning"><?= $error_upload ?></div>
                    <?php endif; ?>

                    <!-- Form untuk menambah kue -->
                    <!-- PENTING: tambahkan enctype="multipart/form-data" -->
                    <form action="kue_tambah.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="kode_kue" class="form-label">Kode Kue</label>
                            <input type="text" class="form-control" id="kode_kue" name="kode_kue" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama_kue" class="form-label">Nama Kue</label>
                            <input type="text" class="form-control" id="nama_kue" name="nama_kue" required>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="harga_jual" class="form-label">Harga Jual</label>
                            <input type="number" class="form-control" id="harga_jual" name="harga_jual" min="0" step="0.01">
                        </div>
                        
                        <!-- INI ADALAH BAGIAN UNTUK UPLOAD FOTO -->
                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto Kue</label>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <div class="form-text">Pilih file gambar (JPG, PNG, GIF). Maksimal 2MB.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Simpan Kue
                        </button>
                        <a href="daftarkue.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Batal
                        </a>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <!-- Salin JavaScript dari daftarkue.php -->
</body>
</html>