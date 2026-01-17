<?php
// get_bahan_baku.php
require_once 'config/database.php';

// Set header untuk respons JSON
header('Content-Type: application/json');

 $database = new Database();
 $db = $database->connect();

 $response = ['success' => false, 'data' => []];

if ($db) {
    // Ambil id, nama, dan satuan dari tabel bahan_baku
    $sql = "SELECT id, nama_bahan, satuan FROM bahan_baku ORDER BY nama_bahan ASC";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $response['message'] = 'Gagal mengambil data bahan baku.';
    }
} else {
    $response['message'] = 'Koneksi database gagal.';
}

echo json_encode($response);
?>