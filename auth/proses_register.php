<?php
// auth/proses_register.php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php'); exit;
}

$nama    = trim($_POST['nama'] ?? '');
$username = trim($_POST['username'] ?? '');
$email   = trim($_POST['email'] ?? '');
$pass    = $_POST['password'] ?? '';
$confirm = $_POST['password_confirm'] ?? '';

// Validasi
if (empty($nama) || empty($username) || empty($email) || empty($pass)) {
    setFlash('error', 'Semua kolom wajib diisi.'); header('Location: register.php'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Format email tidak valid.'); header('Location: register.php'); exit;
}
if (strlen($pass) < 6) {
    setFlash('error', 'Password minimal 6 karakter.'); header('Location: register.php'); exit;
}
if ($pass !== $confirm) {
    setFlash('error', 'Konfirmasi password tidak cocok.'); header('Location: register.php'); exit;
}

// Cek duplikat
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    setFlash('error', 'Username atau email sudah digunakan.'); header('Location: register.php'); exit;
}

// Simpan
$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (nama, username, email, password, role) VALUES (?, ?, ?, ?, 'anggota')");
$stmt->execute([$nama, $username, $email, $hash]);

setFlash('success', 'Akun berhasil dibuat! Silakan login.');
header('Location: login.php');
exit;