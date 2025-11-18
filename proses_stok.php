<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_bahan = $_POST['id_bahan'];
    $jenis_transaksi = $_POST['jenis_transaksi'];
    $jumlah = floatval($_POST['jumlah']);
    $keterangan = $_POST['keterangan'];
    $user_id = $_SESSION['user_id'];

    $bahanBaku = new BahanBaku();
    $logStok = new LogStok();

    // Get current stock
    $currentItem = $bahanBaku->getById($id_bahan);
    $stok_sebelum = $currentItem['stok_saat_ini'];

    // Calculate new stock
    if ($jenis_transaksi == 'masuk') {
        $stok_sesudah = $stok_sebelum + $jumlah;
    } else {
        $stok_sesudah = $stok_sebelum - $jumlah;
        if ($stok_sesudah < 0) {
            $_SESSION['error'] = 'Stok tidak mencukupi!';
            header('Location: index.php');
            exit();
        }
    }

    // Update stock
    $bahanBaku->updateStock($id_bahan, $stok_sesudah);

    // Create log
    $logData = [
        'id_bahan' => $id_bahan,
        'jenis_transaksi' => $jenis_transaksi,
        'jumlah' => $jumlah,
        'stok_sebelum' => $stok_sebelum,
        'stok_sesudah' => $stok_sesudah,
        'keterangan' => $keterangan,
        'user_id' => $user_id
    ];
    $logStok->create($logData);

    $_SESSION['success'] = 'Transaksi stok berhasil disimpan!';
    header('Location: index.php');
    exit();
}
?>