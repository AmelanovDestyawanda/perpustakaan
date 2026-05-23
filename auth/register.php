<?php
// auth/register.php
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: ../anggota/dashboard.php'); exit;
}
$error = '';
$success = '';

if(isset($_SESSION['flash'])){

    if($_SESSION['flash']['type'] === 'error'){
        $error = $_SESSION['flash']['msg'] ?? '';
    }

    if($_SESSION['flash']['type'] === 'success'){
        $success = $_SESSION['flash']['msg'] ?? '';
    }

    unset($_SESSION['flash']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Daftar — Perpustakaan Digital</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --cream:#F5F0E8; --cream-lt:#FEFCF8; --brown:#3B2A1A; --brown2:#5C3D1E; --gold:#C49A3C; --gold-lt:#EDD98A; --muted:#8C7A62; --red:#A83232; --green:#2E6B3E; }
    body {
      min-height: 100vh;
      background: var(--cream);
      background-image: radial-gradient(ellipse 80% 60% at 70% 20%, #D9C9A8 0%, transparent 60%), radial-gradient(ellipse 60% 80% at 10% 90%, #C8B89A 0%, transparent 55%);
      display: flex; align-items: center; justify-content: center;
      font-family: 'DM Sans', sans-serif;
      padding: 2rem;
    }
    .card {
      background: var(--cream-lt);
      border-radius: 20px;
      box-shadow: 0 24px 64px rgba(59,42,26,0.14);
      padding: 2.5rem;
      width: 100%; max-width: 480px;
    }
    .brand { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem; justify-content: center; }
    .brand-icon { width: 40px; height: 40px; background: var(--brown); border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .brand-icon svg { width: 22px; height: 22px; fill: var(--gold); }
    .brand-name { font-family: 'Playfair Display', serif; font-size: 1rem; color: var(--brown); }
    h2 { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: var(--brown); margin-bottom: 0.25rem; }
    p.sub { font-size: 0.85rem; color: var(--muted); margin-bottom: 1.75rem; }
    .form-group { margin-bottom: 1.1rem; }
    label { display: block; font-size: 0.76rem; font-weight: 500; color: var(--brown2); margin-bottom: 0.4rem; letter-spacing: 0.04em; text-transform: uppercase; }
    input {
      width: 100%; padding: 0.7rem 0.9rem;
      border: 1.5px solid #DDD5C5; border-radius: 10px;
      font-size: 0.9rem; font-family: 'DM Sans', sans-serif; color: var(--brown);
      background: #FDFAF5; outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,154,60,0.12); }
    .alert { padding: 0.7rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.2rem; }
    .alert-error { background: rgba(168,50,50,0.08); color: var(--red); border: 1px solid rgba(168,50,50,0.2); }
    .alert-success { background: rgba(46,107,62,0.08); color: var(--green); border: 1px solid rgba(46,107,62,0.2); }
    .btn-register {
      width: 100%; padding: 0.82rem;
      background: var(--brown); color: var(--gold-lt);
      border: none; border-radius: 10px;
      font-size: 0.95rem; font-weight: 500; font-family: 'DM Sans', sans-serif;
      cursor: pointer; margin-top: 0.5rem;
      transition: background 0.2s, box-shadow 0.2s;
      box-shadow: 0 4px 16px rgba(59,42,26,0.15);
    }
    .btn-register:hover { background: var(--brown2); }
    .login-link { text-align: center; margin-top: 1.25rem; font-size: 0.84rem; color: var(--muted); }
    .login-link a { color: var(--gold); font-weight: 500; }
  </style>
</head>
<body>
<div class="card">
  <div class="brand">
    <div class="brand-icon"><svg viewBox="0 0 24 24"><path d="M4 4h7a4 4 0 0 1 4 4v12H4V4z"/><path d="M15 8h1a4 4 0 0 1 4 4v8h-5V8z" opacity=".5"/></svg></div>
    <div class="brand-name">Perpustakaan Digital</div>
  </div>

  <h2>Buat Akun</h2>
  <p class="sub">Daftarkan diri Anda sebagai anggota perpustakaan</p>

  <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form method="POST" action="proses_register.php">
    <div class="form-group">
      <label for="nama">Nama Lengkap</label>
      <input type="text" id="nama" name="nama" placeholder="Nama lengkap Anda" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"/>
    </div>
    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" placeholder="Username unik" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
    </div>
    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" placeholder="email@contoh.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required/>
    </div>
    <div class="form-group">
      <label for="password_confirm">Konfirmasi Password</label>
      <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi password" required/>
    </div>
    <button type="submit" class="btn-register">Daftar Sekarang</button>
  </form>

  <div class="login-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
</div>
</body>
</html>