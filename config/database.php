<?php
class Database {
    private $host = 'localhost';
    private $dbname = 'happyfood_inventory';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->conn;
        } catch(PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            return null;
        }
    }
}

class User {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function login($username, $password) {
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function getUserById($id) {
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

class BahanBaku {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getAll() {
        $query = "SELECT * FROM bahan_baku ORDER BY nama_bahan";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM bahan_baku WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO bahan_baku (kode_bahan, nama_bahan, satuan, stok_saat_ini, level_restok, harga_beli, keterangan) 
                  VALUES (:kode_bahan, :nama_bahan, :satuan, :stok_saat_ini, :level_restok, :harga_beli, :keterangan)";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':kode_bahan', $data['kode_bahan']);
        $stmt->bindParam(':nama_bahan', $data['nama_bahan']);
        $stmt->bindParam(':satuan', $data['satuan']);
        $stmt->bindParam(':stok_saat_ini', $data['stok_saat_ini']);
        $stmt->bindParam(':level_restok', $data['level_restok']);
        $stmt->bindParam(':harga_beli', $data['harga_beli']);
        $stmt->bindParam(':keterangan', $data['keterangan']);
        
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE bahan_baku SET 
                  kode_bahan = :kode_bahan,
                  nama_bahan = :nama_bahan,
                  satuan = :satuan,
                  stok_saat_ini = :stok_saat_ini,
                  level_restok = :level_restok,
                  harga_beli = :harga_beli,
                  keterangan = :keterangan
                  WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':kode_bahan', $data['kode_bahan']);
        $stmt->bindParam(':nama_bahan', $data['nama_bahan']);
        $stmt->bindParam(':satuan', $data['satuan']);
        $stmt->bindParam(':stok_saat_ini', $data['stok_saat_ini']);
        $stmt->bindParam(':level_restok', $data['level_restok']);
        $stmt->bindParam(':harga_beli', $data['harga_beli']);
        $stmt->bindParam(':keterangan', $data['keterangan']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM bahan_baku WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getLowStock() {
        $query = "SELECT * FROM bahan_baku WHERE stok_saat_ini <= level_restok ORDER BY stok_saat_ini";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStock($id, $newStock) {
        $query = "UPDATE bahan_baku SET stok_saat_ini = :stok_saat_ini WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':stok_saat_ini', $newStock);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}

class LogStok {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function create($data) {
        $query = "INSERT INTO log_stok (id_bahan, jenis_transaksi, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id) 
                  VALUES (:id_bahan, :jenis_transaksi, :jumlah, :stok_sebelum, :stok_sesudah, :keterangan, :user_id)";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id_bahan', $data['id_bahan']);
        $stmt->bindParam(':jenis_transaksi', $data['jenis_transaksi']);
        $stmt->bindParam(':jumlah', $data['jumlah']);
        $stmt->bindParam(':stok_sebelum', $data['stok_sebelum']);
        $stmt->bindParam(':stok_sesudah', $data['stok_sesudah']);
        $stmt->bindParam(':keterangan', $data['keterangan']);
        $stmt->bindParam(':user_id', $data['user_id']);
        
        return $stmt->execute();
    }

    public function getAll() {
        $query = "SELECT ls.*, bb.nama_bahan, u.nama_lengkap as nama_user 
                  FROM log_stok ls 
                  JOIN bahan_baku bb ON ls.id_bahan = bb.id 
                  JOIN users u ON ls.user_id = u.id 
                  ORDER BY ls.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByBahanId($id_bahan) {
        $query = "SELECT ls.*, u.nama_lengkap as nama_user 
                  FROM log_stok ls 
                  JOIN users u ON ls.user_id = u.id 
                  WHERE ls.id_bahan = :id_bahan 
                  ORDER BY ls.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_bahan', $id_bahan);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>