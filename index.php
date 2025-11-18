<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HappyFood Inventory</title>
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
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .alert-stock {
            border-left: 4px solid #dc3545;
            background: linear-gradient(to right, #fff5f5, #ffffff);
        }
        .header-title {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
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
    $lowStockItems = $bahanBaku->getLowStock();
    $allItems = $bahanBaku->getAll();
    
    $totalItems = count($allItems);
    $lowStockCount = count($lowStockItems);
    $totalStockValue = 0;
    foreach ($allItems as $item) {
        $totalStockValue += $item['stok_saat_ini'] * $item['harga_beli'];
    }
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
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="bahan_baku.php">
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
                                <h2 class="mb-1">Dashboard Inventory</h2>
                                <p class="mb-0">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>! 👋</p>
                            </div>
                            <div class="text-end">
                                <small><?php echo date('d M Y H:i'); ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Stok Rendah -->
                    <?php if ($lowStockCount > 0): ?>
                    <div class="alert alert-danger alert-stock mb-4" role="alert">
                        <h5 class="alert-heading">
                            <i class="bi bi-exclamation-triangle-fill"></i> 
                            Peringatan Stok Rendah!
                        </h5>
                        <p class="mb-2">Ada <?php echo $lowStockCount; ?> item yang perlu segera di-restok:</p>
                        <div class="row">
                            <?php foreach (array_slice($lowStockItems, 0, 4) as $item): ?>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <small class="d-block">
                                    <strong><?php echo $item['nama_bahan']; ?></strong><br>
                                    Stok: <?php echo $item['stok_saat_ini']; ?> <?php echo $item['satuan']; ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($lowStockCount > 4): ?>
                        <small class="text-muted">... dan <?php echo $lowStockCount - 4; ?> item lainnya</small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Statistik Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Total Item</h6>
                                            <h3 class="mb-0"><?php echo $totalItems; ?></h3>
                                        </div>
                                        <div class="fs-1 opacity-50">
                                            <i class="bi bi-box"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Stok Rendah</h6>
                                            <h3 class="mb-0"><?php echo $lowStockCount; ?></h3>
                                        </div>
                                        <div class="fs-1 opacity-50">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Nilai Stok</h6>
                                            <h3 class="mb-0">Rp <?php echo number_format($totalStockValue, 0, ',', '.'); ?></h3>
                                        </div>
                                        <div class="fs-1 opacity-50">
                                            <i class="bi bi-currency-dollar"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Stok Terkini -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-list-check"></i> Stok Terkini
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Bahan</th>
                                            <th>Stok Saat Ini</th>
                                            <th>Level Restok</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allItems as $item): ?>
                                        <tr>
                                            <td><?php echo $item['kode_bahan']; ?></td>
                                            <td><?php echo $item['nama_bahan']; ?></td>
                                            <td>
                                                <?php echo $item['stok_saat_ini']; ?> <?php echo $item['satuan']; ?>
                                            </td>
                                            <td>
                                                <?php echo $item['level_restok']; ?> <?php echo $item['satuan']; ?>
                                            </td>
                                            <td>
                                                <?php if ($item['stok_saat_ini'] <= $item['level_restok']): ?>
                                                    <span class="badge bg-danger">Rendah</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Aman</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-success" onclick="stokMasuk(<?php echo $item['id']; ?>)">
                                                    <i class="bi bi-plus-circle"></i> Masuk
                                                </button>
                                                <button class="btn btn-sm btn-warning" onclick="stokKeluar(<?php echo $item['id']; ?>)">
                                                    <i class="bi bi-dash-circle"></i> Keluar
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

    <!-- Modal Stok Masuk -->
    <div class="modal fade" id="modalStokMasuk" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Stok Masuk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="proses_stok.php">
                    <div class="modal-body">
                        <input type="hidden" name="id_bahan" id="id_bahan_masuk">
                        <input type="hidden" name="jenis_transaksi" value="masuk">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Bahan</label>
                            <input type="text" class="form-control" id="nama_bahan_masuk" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input type="number" step="0.01" class="form-control" name="jumlah" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Stok Keluar -->
    <div class="modal fade" id="modalStokKeluar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Stok Keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="proses_stok.php">
                    <div class="modal-body">
                        <input type="hidden" name="id_bahan" id="id_bahan_keluar">
                        <input type="hidden" name="jenis_transaksi" value="keluar">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Bahan</label>
                            <input type="text" class="form-control" id="nama_bahan_keluar" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input type="number" step="0.01" class="form-control" name="jumlah" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const itemsData = <?php echo json_encode($allItems); ?>;
        
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
    </script>
</body>
</html>