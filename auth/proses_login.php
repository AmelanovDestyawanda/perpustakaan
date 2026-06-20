<?php
// auth/proses_login.php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    setFlash('error', 'Username dan password wajib diisi.');
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'aktif' LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    setFlash('error', 'Username atau password salah.');
    header('Location: login.php');
    exit;
}

// Set session
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_nama'] = $user['nama'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['username']  = $user['username'];

session_regenerate_id(true);

// Redirect sesuai role
if ($user['role'] === 'admin') {
    header('Location: ../admin/dashboard.php');
} else {
    header('Location: ../anggota/dashboard.php');
}
exit;