<?php
// components/sidebar.php
// Variabel $active_menu harus di-set di halaman pemanggil
// Contoh: $active_menu = 'dashboard';
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['user_role'] ?? 'anggota';
$nama = $_SESSION['user_nama'] ?? 'Pengguna';
$initials = strtoupper(substr($nama, 0, 1));
// Ambil huruf pertama nama kedua jika ada
$namaParts = explode(' ', $nama);
if (count($namaParts) > 1) $initials .= strtoupper(substr($namaParts[1], 0, 1));

$base = ($role === 'admin') ? '../admin' : '../anggota';
?>

<aside class="sidebar" id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="sidebar-brand-icon">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M4 4h7a4 4 0 0 1 4 4v12H4V4z"/>
        <path d="M15 8h1a4 4 0 0 1 4 4v8h-5V8z" opacity=".5"/>
        <rect x="6" y="8" width="4" height="1.5" rx=".75"/>
        <rect x="6" y="11" width="6" height="1.5" rx=".75"/>
      </svg>
    </div>
    <div class="sidebar-brand-text">
      Perpustakaan
      <small>Digital System</small>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <?php if ($role === 'admin'): ?>

      <div class="nav-section-label">Utama</div>

      <a href="../admin/dashboard.php" class="nav-item <?= ($active_menu ?? '') === 'dashboard' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>

      <div class="nav-section-label">Manajemen</div>

      <a href="../admin/buku.php" class="nav-item <?= ($active_menu ?? '') === 'buku' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Manajemen Buku
      </a>

      <a href="../admin/anggota.php" class="nav-item <?= ($active_menu ?? '') === 'anggota' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Manajemen Anggota
      </a>

      <a href="../admin/peminjaman.php" class="nav-item <?= ($active_menu ?? '') === 'peminjaman' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Peminjaman
      </a>

    <?php else: ?>

      <div class="nav-section-label">Menu</div>

      <a href="../anggota/dashboard.php" class="nav-item <?= ($active_menu ?? '') === 'dashboard' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>

      <a href="../anggota/katalog.php" class="nav-item <?= ($active_menu ?? '') === 'katalog' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        Katalog Buku
      </a>

      <a href="../anggota/riwayat.php" class="nav-item <?= ($active_menu ?? '') === 'riwayat' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4.5"/><polyline points="3 3 3 7 7 7"/></svg>
        Riwayat Pinjam
      </a>

    <?php endif; ?>

  </nav>

  <!-- Footer user card -->
  <div class="sidebar-footer">
    <div class="user-card">
      <div class="user-avatar"><?= e($initials) ?></div>
      <div class="user-info">
        <div class="user-name"><?= e($nama) ?></div>
        <div class="user-role"><?= $role === 'admin' ? 'Administrator' : 'Anggota' ?></div>
      </div>
      <a href="../auth/logout.php" class="logout-btn" title="Keluar">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </div>

</aside>

<!-- Backdrop mobile -->
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>