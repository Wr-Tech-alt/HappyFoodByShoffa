<?php
// get_resep_kue.php
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

$kue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($kue_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID kue tidak valid.'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->connect();

    if (!$db) {
        throw new Exception('Koneksi database gagal.');
    }

    // Ambil resep + nama bahan
    $sql = "SELECT rk.bahan_id,
                   rk.qty_per_pcs,
                   rk.satuan,
                   bb.nama_bahan
            FROM resep_kue rk
            JOIN bahan_baku bb ON rk.bahan_id = bb.id
            WHERE rk.kue_id = :kue_id
            ORDER BY bb.nama_bahan ASC";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':kue_id', $kue_id, PDO::PARAM_INT);
    $stmt->execute();
    $resep = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$resep) {
        echo json_encode([
            'success' => false,
            'message' => 'Resep untuk kue ini belum diatur.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'resep'   => $resep
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
