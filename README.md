# HappyFood Inventory System

Sistem Inventory Stok Berbasis Website untuk HappyFood

## 🚀 Fitur Utama

### ✅ Otentikasi Pengguna
- Halaman login dengan session management
- User default: `shofa` / `password`
- Logout yang aman

### ✅ Dashboard Interaktif
- Ringkasan stok real-time
- Peringatan otomatis untuk stok rendah
- Statistik total item, stok rendah, dan nilai inventory
- Akses cepat untuk stok masuk/keluar

### ✅ Manajemen Bahan Baku (CRUD Lengkap)
- **Create**: Tambah bahan baku baru
- **Read**: Lihat daftar semua bahan baku
- **Update**: Edit data bahan baku
- **Delete**: Hapus bahan baku
- Validasi data otomatis

### ✅ Manajemen Stok
- **Stok Masuk**: Tambah stok dengan logging otomatis
- **Stok Keluar**: Kurangi stok dengan validasi
- **Logging Otomatis**: Setiap transaksi tersimpan di log_stok
- Real-time stock update

### ✅ Laporan Komprehensif
- Riwayat semua transaksi stok
- Filter berdasarkan bahan, jenis, dan tanggal
- Summary statistik (total masuk, keluar, transaksi)
- Fitur cetak laporan

## 📋 Struktur Database

### Tabel `users`
- id, username, password, nama_lengkap, role, created_at

### Tabel `bahan_baku`
- id, kode_bahan, nama_bahan, satuan, stok_saat_ini, level_restok, harga_beli, keterangan, created_at, updated_at

### Tabel `log_stok`
- id, id_bahan, jenis_transaksi, jumlah, stok_sebelum, stok_sesudah, keterangan, user_id, created_at

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP Native dengan OOP
- **Frontend**: Bootstrap 5 dengan Bootstrap Icons
- **Database**: MySQL/MariaDB dengan PDO
- **Styling**: Custom CSS dengan gradient modern
- **Session Management**: PHP Native Sessions

## 📁 Struktur File

```
happyfood-inventory/
├── config/
│   └── database.php          # Koneksi & Class OOP
├── login.php                 # Halaman Login
├── logout.php                # Proses Logout
├── index.php                 # Dashboard
├── bahan_baku.php            # Manajemen Bahan Baku
├── laporan.php               # Laporan Stok
├── proses_stok.php           # Proses Transaksi Stok
├── database.sql              # SQL Database Schema
└── README.md                 # Dokumentasi
```

## 🚀 Cara Instalasi

### 1. Database Setup
```sql
-- Import database.sql ke MySQL/MariaDB
mysql -u root -p happyfood_inventory < database.sql
```

### 2. Konfigurasi Koneksi
Edit file `config/database.php` sesuai konfigurasi database:
```php
private $host = 'localhost';
private $dbname = 'happyfood_inventory';
private $username = 'root';
private $password = '';
```

### 3. Akses Aplikasi
- Buka browser: `http://localhost/happyfood-inventory/`
- Login dengan: `shofa` / `password`

## 👤 User Access

- **Username**: shofa
- **Password**: password
- **Role**: Owner

## 🎯 Cara Penggunaan

### 1. Login
- Masukkan username dan password
- System akan redirect ke dashboard

### 2. Dashboard
- Lihat overview stok
- Peringatan stok rendah otomatis
- Quick access untuk stok masuk/keluar

### 3. Manajemen Bahan Baku
- Klik "Tambah Bahan" untuk item baru
- Edit data existing items
- Hapus item tidak terpakai

### 4. Transaksi Stok
- **Stok Masuk**: Tombol hijau "Masuk"
- **Stok Keluar**: Tombol kuning "Keluar"
- Input jumlah dan keterangan
- System update stok otomatis

### 5. Laporan
- Akses menu "Laporan Stok"
- Filter data sesuai kebutuhan
- Cetak laporan untuk dokumentasi

## 🔒 Keamanan

- Password hashing dengan `password_hash()`
- Session validation pada setiap halaman
- SQL Injection prevention dengan PDO prepared statements
- Input validation dan sanitization

## 🎨 Desain Features

- Responsive design untuk mobile & desktop
- Modern gradient backgrounds
- Interactive hover effects
- Bootstrap 5 components
- Custom animations dan transitions
- Color-coded status indicators

## 📊 Sample Data

System sudah include sample data:
- Cokelat, Mentega, Telur, Minyak Goreng
- Krim, Gula, Tepung, Fondant
- Dengan stok awal dan level restok

## 🔄 Auto Features

- Auto-refresh dashboard (30 seconds)
- Real-time stock updates
- Automatic low stock alerts
- Transaction logging
- Timestamp recording

## 📝 Notes

- Tidak ada fitur penjualan/POS sesuai requirement
- Focus murni pada inventory management
- Scalable untuk tambahan fitur future
- Clean code structure dengan OOP PHP

---

**HappyFood Inventory System** - Solusi lengkap untuk manajemen stok bahan baku! 🍔✨