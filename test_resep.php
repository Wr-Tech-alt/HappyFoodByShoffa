<?php
// test_resep.php
require_once 'config/database.php';

echo "<h3>Testing Query Resep Kue</h3>";

 $database = new Database();
 $db = $database->connect();

if (!$db) {
    die("Koneksi database gagal.");
}

// --- GANTI 1 dengan ID kue yang ingin Anda test ---
// Misalnya, jika Sponge Cake ID-nya 1, biarkan sebagai 1
 $cake_id_to_test = 1; 

echo "<p>Mencoba mengambil resep untuk kue dengan ID: <strong>{$cake_id_to_test}</strong></p>";

// Query yang sama dengan yang kita gunakan di daftarkue.php
 $resep_sql = "SELECT rk.bahan_id, rk.qty_per_pcs, rk.satuan, bb.nama_bahan 
              FROM resep_kue rk 
              JOIN bahan_baku bb ON rk.bahan_id = bb.id 
              WHERE rk.kue_id = :kue_id";

try {
    $resep_stmt = $db->prepare($resep_sql);
    $resep_stmt->bindParam(':kue_id', $cake_id_to_test, PDO::PARAM_INT);
    $resep_stmt->execute();
    
    $resep_result = $resep_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h4>Hasil Query:</h4>";
    if (empty($resep_result)) {
        echo "<p style='color:orange;'>Tidak ditemukan resep untuk kue ID {$cake_id_to_test}. <br>";
        echo "<strong>Kemungkinan:</strong> Tidak ada data di tabel 'resep_kue' untuk ID ini, atau ada masalah integritas data (bahan_id di tabel resep_kue tidak ditemukan di tabel 'bahan_baku').</p>";
    } else {
        echo "<pre>" . print_r($resep_result, true) . "</pre>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red;'><strong>TERJADI ERROR SQL:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Apa artinya:</strong></p>";
    echo "<ul>";
    echo "<li>Jika ada 'Base table or view not found', berarti nama tabel 'resep_kue' atau 'bahan_baku' salah. Periksa ejaan dan huruf besar/kecilnya.</li>";
    echo "<li>Jika ada 'Unknown column', berarti nama kolom (bahan_id, qty_per_pcs, satuans, nama_bahan) salah. Periksa kembali struktur tabel Anda di phpMyAdmin.</li>";
    echo "</ul>";
}
?>