CREATE DATABASE happyfood_inventory;
USE happyfood_inventory;

-- Tabel Users untuk otentikasi
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('owner', 'admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Bahan Baku (Master Item)
CREATE TABLE bahan_baku (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_bahan VARCHAR(20) UNIQUE NOT NULL,
    nama_bahan VARCHAR(100) NOT NULL,
    satuan VARCHAR(20) NOT NULL COMMENT 'kg, liter, pcs, dll',
    stok_saat_ini DECIMAL(10,2) DEFAULT 0,
    level_restok DECIMAL(10,2) DEFAULT 0,
    harga_beli DECIMAL(10,2) DEFAULT 0,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Log Stok untuk mencatat pergerakan stok
CREATE TABLE log_stok (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_bahan INT NOT NULL,
    jenis_transaksi ENUM('masuk', 'keluar') NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    stok_sebelum DECIMAL(10,2) NOT NULL,
    stok_sesudah DECIMAL(10,2) NOT NULL,
    keterangan VARCHAR(255),
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_bahan) REFERENCES bahan_baku(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert user default (Owner Shofa)
INSERT INTO users (username, password, nama_lengkap, role) VALUES 
('shofa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Shofa Owner', 'owner');

-- Insert contoh data bahan baku
INSERT INTO bahan_baku (kode_bahan, nama_bahan, satuan, stok_saat_ini, level_restok, harga_beli) VALUES 
('BK001', 'Cokelat', 'kg', 5.5, 2.0, 75000),
('BK002', 'Mentega', 'kg', 3.2, 1.5, 85000),
('BK003', 'Telur', 'pcs', 50, 20, 2500),
('BK004', 'Minyak Goreng', 'liter', 8.0, 3.0, 15000),
('BK005', 'Krim', 'liter', 2.5, 1.0, 45000),
('BK006', 'Gula', 'kg', 10.0, 5.0, 12000),
('BK007', 'Tepung', 'kg', 15.0, 7.0, 10000),
('BK008', 'Fondant', 'kg', 1.8, 0.5, 120000);