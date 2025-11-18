<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok - HappyFood Inventory</title>
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
        .badge-masuk {
            background-color: #28a745;
        }
        .badge-keluar {
            background-color: #ffc107;
            color: #000;
        }
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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

    $logStok = new LogStok();
    $bahanBaku = new BahanBaku();

    // Handle filtering
    $filter_bahan = $_GET['filter_bahan'] ?? '';
    $filter_jenis = $_GET['filter_jenis'] ?? '';
    $filter_tanggal = $_GET['filter_tanggal'] ?? '';

    $logs = $logStok->getAll();
    $bahans = $bahanBaku->getAll();

    // Apply filters
    if ($filter_bahan) {
        $logs = array_filter($logs, function($log) use ($filter_bahan) {
            return $log['id_bahan'] == $filter_bahan;
        });
    }

    if ($filter_jenis) {
        $logs = array_filter($logs, function($log) use ($filter_jenis) {
            return $log['jenis_transaksi'] == $filter_jenis;
        });
    }

    if ($filter_tanggal) {
        $logs = array_filter($logs, function($log) use ($filter_tanggal) {
            return date('Y-m-d', strtotime($log['created_at'])) == $filter_tanggal;
        });
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
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="bahan_baku.php">
                            <i class="bi bi-box"></i> Bahan Baku
                        </a>
                        <a class="nav-link active" href="laporan.php">
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
                                <h2 class="mb-1">Laporan Stok</h2>
                                <p class="mb-0">Riwayat pergerakan stok bahan baku</p>
                            </div>
                            <button class="btn btn-light" onclick="window.print()">
                                <i class="bi bi-printer"></i> Cetak
                            </button>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section">
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
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-funnel"></i> Filter
                                        </button>
                                        <a href="laporan.php" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Total Stok Masuk</h6>
                                            <h4 class="mb-0">
                                                <?php 
                                                $totalMasuk = array_sum(array_map(function($log) {
                                                    return $log['jenis_transaksi'] == 'masuk' ? $log['jumlah'] : 0;
                                                }, $logs));
                                                echo number_format($totalMasuk, 2);
                                                ?>
                                            </h4>
                                        </div>
                                        <i class="bi bi-arrow-down-circle fs-1 opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Total Stok Keluar</h6>
                                            <h4 class="mb-0">
                                                <?php 
                                                $totalKeluar = array_sum(array_map(function($log) {
                                                    return $log['jenis_transaksi'] == 'keluar' ? $log['jumlah'] : 0;
                                                }, $logs));
                                                echo number_format($totalKeluar, 2);
                                                ?>
                                            </h4>
                                        </div>
                                        <i class="bi bi-arrow-up-circle fs-1 opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Total Transaksi</h6>
                                            <h4 class="mb-0"><?php echo count($logs); ?></h4>
                                        </div>
                                        <i class="bi bi-list-check fs-1 opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history"></i> Riwayat Transaksi Stok
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light sticky-top">
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
                                                <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                                <td><?php echo $log['nama_bahan']; ?></td>
                                                <td>
                                                    <?php if ($log['jenis_transaksi'] == 'masuk'): ?>
                                                        <span class="badge badge-masuk">
                                                            <i class="bi bi-arrow-down"></i> Masuk
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-keluar">
                                                            <i class="bi bi-arrow-up"></i> Keluar
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-bold">
                                                    <?php if ($log['jenis_transaksi'] == 'masuk'): ?>
                                                        <span class="text-success">+<?php echo $log['jumlah']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-warning">-<?php echo $log['jumlah']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $log['stok_sebelum']; ?></td>
                                                <td class="fw-bold"><?php echo $log['stok_sesudah']; ?></td>
                                                <td><?php echo $log['nama_user']; ?></td>
                                                <td><?php echo $log['keterangan'] ?: '-'; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh setiap 30 detik
        setTimeout(function(){
            window.location.reload();
        }, 30000);
    </script>

    <style>
        @media print {
            .sidebar, .btn, .filter-section {
                display: none !important;
            }
            .col-md-9 {
                width: 100% !important;
            }
            .main-content {
                padding: 0 !important;
            }
        }
    </style>
</body>
</html>