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
$no_telp = trim($_POST['no_telp'] ?? '') ?: null;
$alamat  = trim($_POST['alamat'] ?? '') ?: null;

// Validasi wajib
if (empty($nama) || empty($username) || empty($email) || empty($pass)) {
    setFlash('error', 'Nama, username, email, dan password wajib diisi.');
    header('Location: register.php'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Format email tidak valid.');
    header('Location: register.php'); exit;
}
if (strlen($pass) < 6) {
    setFlash('error', 'Password minimal 6 karakter.');
    header('Location: register.php'); exit;
}
if ($pass !== $confirm) {
    setFlash('error', 'Konfirmasi password tidak cocok.');
    header('Location: register.php'); exit;
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    setFlash('error', 'Username hanya boleh huruf, angka, dan underscore.');
    header('Location: register.php'); exit;
}

// Cek duplikat username / email
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    setFlash('error', 'Username atau email sudah digunakan. Silakan gunakan yang lain.');
    header('Location: register.php'); exit;
}

// Simpan ke database
$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    "INSERT INTO users (nama, username, email, password, role, no_telp, alamat)
     VALUES (?, ?, ?, ?, 'anggota', ?, ?)"
);
$stmt->execute([$nama, $username, $email, $hash, $no_telp, $alamat]);

setFlash('success', 'Akun berhasil dibuat! Silakan login.');
header('Location: login.php');
exit;