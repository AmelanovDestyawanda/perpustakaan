<?php
// auth/login.php
session_start();
if (!empty($_SESSION['user_id'])) {
    $redirect = $_SESSION['user_role'] === 'admin' ? '../admin/dashboard.php' : '../anggota/dashboard.php';
    header("Location: $redirect");
    exit;
}
$error = $_SESSION['flash']['msg'] ?? '';
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — Perpustakaan Digital</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --cream: #F5F0E8; --cream-lt: #FEFCF8;
      --brown: #3B2A1A; --brown2: #5C3D1E;
      --gold: #C49A3C;  --gold-lt: #EDD98A;
      --muted: #8C7A62; --red: #A83232;
    }
    body {
      min-height: 100vh;
      background: var(--cream);
      background-image:
        radial-gradient(ellipse 80% 60% at 70% 20%, #D9C9A8 0%, transparent 60%),
        radial-gradient(ellipse 60% 80% at 10% 90%, #C8B89A 0%, transparent 55%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'DM Sans', sans-serif;
      padding: 2rem;
    }
    .wrapper {
      display: flex;
      width: 100%; max-width: 860px;
      min-height: 500px;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 24px 64px rgba(59,42,26,0.18), 0 4px 16px rgba(59,42,26,0.10);
    }
    .panel-left {
      flex: 1;
      background: var(--brown);
      background-image:
        radial-gradient(ellipse 90% 70% at 20% 30%, #5C3D1E 0%, transparent 60%),
        radial-gradient(ellipse 70% 90% at 80% 80%, #1E1208 0%, transparent 55%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem 2.5rem;
      position: relative;
      overflow: hidden;
    }
    .panel-left::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        repeating-linear-gradient(0deg, transparent, transparent 34px, rgba(196,154,60,0.07) 34px, rgba(196,154,60,0.07) 35px),
        repeating-linear-gradient(90deg, transparent, transparent 34px, rgba(196,154,60,0.07) 34px, rgba(196,154,60,0.07) 35px);
    }
    .logo-icon {
      width: 72px; height: 72px;
      background: rgba(196,154,60,0.15);
      border: 1.5px solid rgba(196,154,60,0.4);
      border-radius: 18px;
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 1.5rem;
      position: relative; z-index: 1;
    }
    .logo-icon svg { width: 38px; height: 38px; fill: var(--gold); }
    .panel-left h1 {
      font-family: 'Playfair Display', serif;
      font-size: 1.9rem;
      color: var(--cream-lt);
      text-align: center; line-height: 1.3;
      position: relative; z-index: 1;
    }
    .panel-left p {
      margin-top: 0.75rem;
      font-size: 0.85rem;
      color: var(--gold-lt);
      opacity: 0.75;
      text-align: center; line-height: 1.6;
      position: relative; z-index: 1;
    }
    .ornament {
      display: flex; align-items: center; gap: 0.6rem;
      margin: 1.5rem 0;
      position: relative; z-index: 1;
    }
    .ornament span { display: block; height: 1px; width: 50px; background: rgba(196,154,60,0.4); }
    .ornament i { color: var(--gold); font-size: 0.6rem; }
    .panel-right {
      flex: 1.1;
      background: var(--cream-lt);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 3rem;
    }
    .form-header { margin-bottom: 2rem; }
    .form-header h2 {
      font-family: 'Playfair Display', serif;
      font-size: 1.7rem; color: var(--brown);
      margin-bottom: 0.35rem;
    }
    .form-header p { font-size: 0.875rem; color: var(--muted); }
    .form-group { margin-bottom: 1.3rem; }
    .form-group label {
      display: block;
      font-size: 0.78rem; font-weight: 500;
      color: var(--brown2);
      margin-bottom: 0.45rem;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }
    .input-wrap { position: relative; }
    .input-wrap .ico {
      position: absolute; left: 14px; top: 50%;
      transform: translateY(-50%);
      width: 18px; height: 18px;
      stroke: var(--muted); fill: none;
      stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
      pointer-events: none;
      transition: stroke 0.2s;
    }
    .input-wrap input {
      width: 100%;
      padding: 0.75rem 1rem 0.75rem 2.75rem;
      border: 1.5px solid #DDD5C5;
      border-radius: 10px;
      font-size: 0.95rem; font-family: 'DM Sans', sans-serif;
      color: var(--brown); background: #FDFAF5;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .input-wrap input:focus { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(196,154,60,0.15); background: var(--cream-lt); }
    .input-wrap input:focus ~ .ico { stroke: var(--gold); }
    .error-box {
      background: #FFF0F0; border: 1px solid #F5C6C6;
      border-radius: 8px;
      padding: 0.65rem 1rem;
      font-size: 0.85rem; color: var(--red);
      margin-bottom: 1.3rem;
      display: flex; align-items: center; gap: 0.5rem;
    }
    .error-box svg { flex-shrink: 0; width: 16px; height: 16px; stroke: var(--red); fill: none; stroke-width: 2; stroke-linecap: round; }
    .btn-login {
      width: 100%;
      padding: 0.85rem;
      background: var(--brown); color: var(--gold-lt);
      border: none; border-radius: 10px;
      font-size: 0.95rem; font-weight: 500; font-family: 'DM Sans', sans-serif;
      cursor: pointer; letter-spacing: 0.02em;
      transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
      margin-top: 0.5rem;
      box-shadow: 0 4px 16px rgba(59,42,26,0.18);
    }
    .btn-login:hover { background: var(--brown2); box-shadow: 0 6px 20px rgba(59,42,26,0.25); }
    .btn-login:active { transform: scale(0.98); }
    .register-link {
      margin-top: 1.5rem;
      text-align: center;
      font-size: 0.84rem;
      color: var(--muted);
    }
    .register-link a { color: var(--gold); font-weight: 500; text-decoration: none; }
    .register-link a:hover { color: var(--brown2); }
    .footer-note { margin-top: 1.5rem; font-size: 0.76rem; color: var(--muted); text-align: center; }
    @media (max-width: 620px) {
      .wrapper { flex-direction: column; }
      .panel-left { padding: 2rem 1.5rem; }
      .panel-right { padding: 2rem 1.5rem; }
    }
  </style>
</head>
<body>
<div class="wrapper">
  <!-- Panel Kiri -->
  <div class="panel-left">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24"><path d="M4 4h7a4 4 0 0 1 4 4v12H4V4z"/><path d="M15 8h1a4 4 0 0 1 4 4v8h-5V8z" opacity=".5"/><rect x="6" y="8" width="4" height="1.5" rx=".75"/><rect x="6" y="11" width="6" height="1.5" rx=".75"/></svg>
    </div>
    <h1>Perpustakaan<br/>Digital</h1>
    <div class="ornament"><span></span>✦<span></span></div>
    <p>Sumber ilmu tak terbatas<br/>dalam genggaman Anda.</p>
  </div>

  <!-- Panel Kanan -->
  <div class="panel-right">
    <div class="form-header">
      <h2>Selamat Datang</h2>
      <p>Masuk untuk mengakses sistem perpustakaan</p>
    </div>

    <?php if ($error): ?>
    <div class="error-box">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="proses_login.php">
      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-wrap">
          <input type="text" id="username" name="username"
            placeholder="Masukkan username"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            required autocomplete="username"/>
          <svg class="ico" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <input type="password" id="password" name="password"
            placeholder="Masukkan password"
            required autocomplete="current-password"/>
          <svg class="ico" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
      </div>

      <button type="submit" class="btn-login">Masuk</button>
    </form>

    <div class="register-link">
      Belum punya akun? <a href="register.php">Daftar di sini</a>
    </div>

    <p class="footer-note">© <?= date('Y') ?> Perpustakaan Digital — Hak cipta dilindungi</p>
  </div>
</div>
</body>
</html>