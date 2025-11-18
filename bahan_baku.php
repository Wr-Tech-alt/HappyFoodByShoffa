<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Bahan Baku - HappyFood Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .header-title {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .btn-add {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);
        }
    </style>
</head>
<body>
    <?php
    session_start();
    require_once 'config/database.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    $bahanBaku = new BahanBaku();
    $success = '';
    $error = '';

    // Handle POST requests for CRUD operations
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $data = [
                        'kode_bahan' => $_POST['kode_bahan'],
                        'nama_bahan' => $_POST['nama_bahan'],
                        'satuan' => $_POST['satuan'],
                        'stok_saat_ini' => floatval($_POST['stok_saat_ini']),
                        'level_restok' => floatval($_POST['level_restok']),
                        'harga_beli' => floatval($_POST['harga_beli']),
                        'keterangan' => $_POST['keterangan']
                    ];
                    if ($bahanBaku->create($data)) {
                        $success = 'Bahan baku berhasil ditambahkan!';
                    } else {
                        $error = 'Gagal menambahkan bahan baku!';
                    }
                    break;

                case 'update':
                    $id = $_POST['id'];
                    $data = [
                        'kode_bahan' => $_POST['kode_bahan'],
                        'nama_bahan' => $_POST['nama_bahan'],
                        'satuan' => $_POST['satuan'],
                        'stok_saat_ini' => floatval($_POST['stok_saat_ini']),
                        'level_restok' => floatval($_POST['level_restok']),
                        'harga_beli' => floatval($_POST['harga_beli']),
                        'keterangan' => $_POST['keterangan']
                    ];
                    if ($bahanBaku->update($id, $data)) {
                        $success = 'Bahan baku berhasil diperbarui!';
                    } else {
                        $error = 'Gagal memperbarui bahan baku!';
                    }
                    break;

                case 'delete':
                    $id = $_POST['id'];
                    if ($bahanBaku->delete($id)) {
                        $success = 'Bahan baku berhasil dihapus!';
                    } else {
                        $error = 'Gagal menghapus bahan baku!';
                    }
                    break;
            }
        }
    }

    $allItems = $bahanBaku->getAll();
    ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0">
                <div class="sidebar">
                    <div class="p-3">
                        <h5>🍔 HappyFood</h5>
                        <small>Inventory System</small>
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="bahan_baku.php">
                            <i class="bi bi-box"></i> Bahan Baku
                        </a>
                        <a class="nav-link" href="laporan.php">
                            <i class="bi bi-file-text"></i> Laporan Stok
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <div class="header-title">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-1">Manajemen Bahan Baku</h2>
                                <p class="mb-0">Kelola data master bahan baku</p>
                            </div>
                            <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalAdd">
                                <i class="bi bi-plus-circle"></i> Tambah Bahan
                            </button>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-list-ul"></i> Data Bahan Baku
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Bahan</th>
                                            <th>Satuan</th>
                                            <th>Stok Saat Ini</th>
                                            <th>Level Restok</th>
                                            <th>Harga Beli</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allItems as $item): ?>
                                        <tr>
                                            <td><?php echo $item['kode_bahan']; ?></td>
                                            <td><?php echo $item['nama_bahan']; ?></td>
                                            <td><?php echo $item['satuan']; ?></td>
                                            <td><?php echo $item['stok_saat_ini']; ?></td>
                                            <td><?php echo $item['level_restok']; ?></td>
                                            <td>Rp <?php echo number_format($item['harga_beli'], 0, ',', '.'); ?></td>
                                            <td>
                                                <?php if ($item['stok_saat_ini'] <= $item['level_restok']): ?>
                                                    <span class="badge bg-danger">Rendah</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Aman</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add -->
    <div class="modal fade" id="modalAdd" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Bahan Baku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kode Bahan</label>
                                    <input type="text" class="form-control" name="kode_bahan" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Bahan</label>
                                    <input type="text" class="form-control" name="nama_bahan" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Satuan</label>
                                    <select class="form-control" name="satuan" required>
                                        <option value="">Pilih Satuan</option>
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="liter">Liter</option>
                                        <option value="pcs">Pieces (pcs)</option>
                                        <option value="gram">Gram</option>
                                        <option value="ml">Mililiter (ml)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stok Awal</label>
                                    <input type="number" step="0.01" class="form-control" name="stok_saat_ini" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Level Restok</label>
                                    <input type="number" step="0.01" class="form-control" name="level_restok" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Beli</label>
                                    <input type="number" step="0.01" class="form-control" name="harga_beli" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <input type="text" class="form-control" name="keterangan">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="modalEdit" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Bahan Baku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kode Bahan</label>
                                    <input type="text" class="form-control" name="kode_bahan" id="edit_kode_bahan" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Bahan</label>
                                    <input type="text" class="form-control" name="nama_bahan" id="edit_nama_bahan" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Satuan</label>
                                    <select class="form-control" name="satuan" id="edit_satuan" required>
                                        <option value="">Pilih Satuan</option>
                                        <option value="kg">Kilogram (kg)</option>
                                        <option value="liter">Liter</option>
                                        <option value="pcs">Pieces (pcs)</option>
                                        <option value="gram">Gram</option>
                                        <option value="ml">Mililiter (ml)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stok Saat Ini</label>
                                    <input type="number" step="0.01" class="form-control" name="stok_saat_ini" id="edit_stok_saat_ini" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Level Restok</label>
                                    <input type="number" step="0.01" class="form-control" name="level_restok" id="edit_level_restok" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Harga Beli</label>
                                    <input type="number" step="0.01" class="form-control" name="harga_beli" id="edit_harga_beli" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Keterangan</label>
                                    <input type="text" class="form-control" name="keterangan" id="edit_keterangan">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Delete -->
    <form method="POST" action="" id="deleteForm">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        function deleteItem(id) {
            if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>