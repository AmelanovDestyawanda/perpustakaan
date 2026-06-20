<?php
// ============================================================
//  config/koneksi.php
//  Konfigurasi koneksi database menggunakan PDO
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'perpustakaan');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHAR', 'utf8mb4');

define('SITE_NAME', 'Perpustakaan Digital');
define('SITE_URL',  'http://localhost/perpustakaan');
define('DENDA_PER_HARI', 1000); // Rp 1.000 per hari

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Koneksi database gagal.');
}

// ============================================================
//  Helper: session check
// ============================================================
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: ' . SITE_URL . '/anggota/dashboard.php');
        exit;
    }
}

function requireAnggota(): void {
    requireLogin();
    if ($_SESSION['user_role'] !== 'anggota') {
        header('Location: ' . SITE_URL . '/admin/dashboard.php');
        exit;
    }
}

// ============================================================
//  Helper: flash message
// ============================================================
function setFlash(string $type, string $msg): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

// ============================================================
//  Helper: sanitize output
// ============================================================
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ============================================================
//  Helper: format tanggal Indonesia
// ============================================================
function tglIndo(string $date): string {
    if (!$date) return '-';
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
              'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    [$y, $m, $d] = explode('-', substr($date, 0, 10));
    return (int)$d . ' ' . $bulan[(int)$m] . ' ' . $y;
}

// ============================================================
//  Helper: format rupiah
// ============================================================
function rupiah(float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// ============================================================
//  Helper: generate kode peminjaman
// ============================================================
function generateKodePinjam(PDO $pdo): string {
    $tahun = date('Y');
    $stmt  = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE YEAR(created_at) = $tahun");
    $no    = (int)$stmt->fetchColumn() + 1;
    return 'PJM-' . $tahun . str_pad($no, 4, '0', STR_PAD_LEFT);
}