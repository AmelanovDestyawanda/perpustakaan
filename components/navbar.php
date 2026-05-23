<?php
// components/navbar.php
// Variabel $page_title harus di-set di halaman pemanggil
if (session_status() === PHP_SESSION_NONE) session_start();
$page_title = $page_title ?? 'Perpustakaan';
?>

<header class="topbar">
  <div class="topbar-left">
    <!-- Hamburger (mobile) -->
    <button class="hamburger" id="hamburger" aria-label="Toggle menu">
      <svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>

    <div>
      <div class="topbar-title"><?= e($page_title) ?></div>
      <div style="font-size:0.72rem; color:var(--muted);">
        <?= date('l, d F Y') ?>
      </div>
    </div>
  </div>

  <div class="topbar-right">
    <!-- Notifikasi placeholder -->
    <button style="background:none;border:none;cursor:pointer;padding:6px;color:var(--muted);border-radius:8px;display:flex;transition:background 0.2s;" title="Notifikasi"
      onmouseover="this.style.background='rgba(196,154,60,0.1)'" onmouseout="this.style.background='none'">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
    </button>

    <!-- Role badge -->
    <span class="badge <?= ($_SESSION['user_role'] ?? '') === 'admin' ? 'badge-warning' : 'badge-info' ?>"
      style="font-size:0.7rem;">
      <?= ucfirst($_SESSION['user_role'] ?? '') ?>
    </span>
  </div>
</header>