<?php
// buat_kue.php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sesi berakhir, silakan login ulang.'
    ]);
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);

// Baca JSON dari fetch()
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$cake_id    = isset($data['cake_id']) ? (int)$data['cake_id'] : 0;
$jumlah_kue = isset($data['jumlah'])  ? (int)$data['jumlah']  : 0;
$keterangan = isset($data['keterangan']) ? trim($data['keterangan']) : '';

if ($cake_id <= 0 || $jumlah_kue <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Data pembuatan kue tidak valid.'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();
    if (!$db) {
        throw new Exception('Koneksi database gagal.');
    }

    $db->beginTransaction();

    // --- Ambil nama kue ---
    $sqlKue = "SELECT nama_kue FROM kue WHERE id = :id";
    $stmtKue = $db->prepare($sqlKue);
    $stmtKue->bindParam(':id', $cake_id, PDO::PARAM_INT);
    $stmtKue->execute();
    $kue = $stmtKue->fetch(PDO::FETCH_ASSOC);

    if (!$kue) {
        throw new Exception('Kue tidak ditemukan.');
    }
    $nama_kue = $kue['nama_kue'];

    // --- Ambil resep kue + stok bahan ---
    $sqlResep = "SELECT rk.bahan_id,
                        rk.qty_per_pcs,
                        rk.satuan,
                        bb.nama_bahan,
                        bb.stok_saat_ini
                 FROM resep_kue rk
                 JOIN bahan_baku bb ON rk.bahan_id = bb.id
                 WHERE rk.kue_id = :kue_id";
    $stmtResep = $db->prepare($sqlResep);
    $stmtResep->bindParam(':kue_id', $cake_id, PDO::PARAM_INT);
    $stmtResep->execute();
    $resep = $stmtResep->fetchAll(PDO::FETCH_ASSOC);

    if (!$resep) {
        throw new Exception('Resep untuk kue ini belum diatur.');
    }

    // --- 1. CEK STOK CUKUP ---
    foreach ($resep as $row) {
        $total_pakai   = (float)$row['qty_per_pcs'] * $jumlah_kue;
        $stok_saat_ini = (float)$row['stok_saat_ini'];

        if ($stok_saat_ini < $total_pakai) {
            throw new Exception(
                "Stok bahan {$row['nama_bahan']} tidak cukup. " .
                "Butuh {$total_pakai} {$row['satuan']}, tersedia {$stok_saat_ini}."
            );
        }
    }

    // --- 2. UPDATE bahan_baku + catat ke log_stok ---
    // catatan penting: di DB-mu kolomnya adalah id_bahan, bukan bahan_id

    $sqlSelectStok = "SELECT stok_saat_ini
                      FROM bahan_baku
                      WHERE id = :bahan_id
                      FOR UPDATE";
    $stmtSelectStok = $db->prepare($sqlSelectStok);

    $sqlUpdateStok = "UPDATE bahan_baku
                      SET stok_saat_ini = stok_saat_ini - :qty
                      WHERE id = :bahan_id";
    $stmtUpdateStok = $db->prepare($sqlUpdateStok);

    // PERHATIKAN: pakai kolom id_bahan di log_stok (bukan bahan_id)
    $sqlLogStok = "INSERT INTO log_stok
                   (id_bahan, jenis_transaksi, jumlah,
                    stok_sebelum, stok_sesudah, user_id, keterangan)
                   VALUES
                   (:id_bahan, 'keluar', :jumlah,
                    :stok_sebelum, :stok_sesudah, :user_id, :keterangan)";
    $stmtLogStok = $db->prepare($sqlLogStok);

    foreach ($resep as $row) {
        $id_bahan    = (int)$row['bahan_id'];
        $total_pakai = (float)$row['qty_per_pcs'] * $jumlah_kue;

        // lock + baca stok sebelum
        $stmtSelectStok->bindParam(':bahan_id', $id_bahan, PDO::PARAM_INT);
        $stmtSelectStok->execute();
        $stok_sebelum = (float)$stmtSelectStok->fetchColumn();

        $stok_sesudah = $stok_sebelum - $total_pakai;

        // update stok
        $stmtUpdateStok->bindParam(':qty', $total_pakai);
        $stmtUpdateStok->bindParam(':bahan_id', $id_bahan, PDO::PARAM_INT);
        $stmtUpdateStok->execute();

        // catat log stok
        $ketLog = 'Buat kue ' . $nama_kue;
        $stmtLogStok->bindParam(':id_bahan', $id_bahan, PDO::PARAM_INT);
        $stmtLogStok->bindParam(':jumlah', $total_pakai);
        $stmtLogStok->bindParam(':stok_sebelum', $stok_sebelum);
        $stmtLogStok->bindParam(':stok_sesudah', $stok_sesudah);
        $stmtLogStok->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmtLogStok->bindParam(':keterangan', $ketLog);
        $stmtLogStok->execute();
    }

    // --- 3. catat ke log_pembuatan_kue ---
    $sqlLogKue = "INSERT INTO log_pembuatan_kue
                  (kue_id, jumlah_kue, user_id, keterangan)
                  VALUES (:kue_id, :jumlah_kue, :user_id, :keterangan)";
    $stmtLogKue = $db->prepare($sqlLogKue);

    $ketPembuatan = $keterangan !== ''
        ? $keterangan
        : ('Buat kue ' . $nama_kue);

    $stmtLogKue->bindParam(':kue_id', $cake_id, PDO::PARAM_INT);
    $stmtLogKue->bindParam(':jumlah_kue', $jumlah_kue, PDO::PARAM_INT);
    $stmtLogKue->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtLogKue->bindParam(':keterangan', $ketPembuatan);
    $stmtLogKue->execute();

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pembuatan kue berhasil.'
    ]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'SQLSTATE error: ' . $e->getMessage()
    ]);
}
