<?php
// anggota/dashboard.php
require_once '../config/koneksi.php';
requireAnggota();

$page_title  = 'Dashboard Anggota';
$active_menu = 'dashboard';
$user_id     = $_SESSION['user_id'];

// Statistik anggota
$sedangPinjam = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id=? AND status='dipinjam'");
$sedangPinjam->execute([$user_id]);
$sedangPinjam = $sedangPinjam->fetchColumn();

$totalPinjam = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id=?");
$totalPinjam->execute([$user_id]);
$totalPinjam = $totalPinjam->fetchColumn();

$terlambat = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE user_id=? AND status='terlambat'");
$terlambat->execute([$user_id]);
$terlambat = $terlambat->fetchColumn();

$totalDenda = $pdo->prepare("SELECT SUM(denda) FROM peminjaman WHERE user_id=?");
$totalDenda->execute([$user_id]);
$totalDenda = (float)$totalDenda->fetchColumn();

// Buku sedang dipinjam
$aktif = $pdo->prepare("
    SELECT p.*, b.judul, b.penulis
    FROM peminjaman p JOIN buku b ON p.buku_id = b.id
    WHERE p.user_id = ? AND p.status IN ('dipinjam','terlambat')
    ORDER BY p.tgl_kembali ASC
");
$aktif->execute([$user_id]);
$aktif = $aktif->fetchAll();

// Katalog terbaru
$katalog = $pdo->query("SELECT * FROM buku WHERE stok_tersedia > 0 ORDER BY created_at DESC LIMIT 4")->fetchAll();
$flash   = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= e($page_title) ?> — Perpustakaan</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>
<div class="layout">
  <?php include '../components/sidebar.php'; ?>
  <div class="main-content">
    <?php include '../components/navbar.php'; ?>
    <div class="page-body">

      <?php if ($flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        <?= e($flash['msg']) ?>
      </div>
      <?php endif; ?>

      <div class="page-header">
        <div>
          <h1>Dashboard</h1>
          <p>Halo, <?= e($_SESSION['user_nama']) ?>! Selamat membaca.</p>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-number"><?= $sedangPinjam ?></div>
            <div class="stat-label">Sedang Dipinjam</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon brown">
            <svg viewBox="0 0 24 24"><polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4.5"/><polyline points="3 3 3 7 7 7"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalPinjam ?></div>
            <div class="stat-label">Total Riwayat Pinjam</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-number"><?= $terlambat ?></div>
            <div class="stat-label">Terlambat Dikembalikan</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon gold">
            <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalDenda > 0 ? rupiah($totalDenda) : 'Rp 0' ?></div>
            <div class="stat-label">Total Denda</div>
          </div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:1.25rem;">

        <!-- Buku yang sedang dipinjam -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Buku Dipinjam</div>
            <a href="riwayat.php" class="btn btn-outline btn-sm">Lihat riwayat</a>
          </div>
          <?php if ($aktif): ?>
          <?php foreach ($aktif as $p): ?>
          <div style="display:flex;align-items:center;gap:0.85rem;padding:0.75rem 0;border-bottom:1px solid rgba(196,154,60,0.1);">
            <div style="width:40px;height:52px;background:var(--brown);border-radius:5px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="var(--gold-lt)" style="opacity:0.7;"><path d="M4 4h7a4 4 0 0 1 4 4v12H4V4z"/></svg>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-weight:500;font-size:0.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($p['judul']) ?></div>
              <div style="font-size:0.75rem;color:var(--muted);"><?= e($p['penulis']) ?></div>
              <div style="font-size:0.73rem;margin-top:3px;">
                Kembali: <span style="color:<?= $p['status']==='terlambat' ? 'var(--red)' : 'var(--green)' ?>;font-weight:500;"><?= tglIndo($p['tgl_kembali']) ?></span>
              </div>
            </div>
            <span class="badge <?= $p['status']==='terlambat' ? 'badge-danger' : 'badge-info' ?>"><?= $p['status']==='terlambat' ? 'Terlambat' : 'Dipinjam' ?></span>
          </div>
          <?php endforeach; ?>
          <?php else: ?>
          <div class="empty-state" style="padding:2rem 1rem;">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <p>Tidak ada buku yang sedang dipinjam</p>
          </div>
          <?php endif; ?>
        </div>

        <!-- Buku tersedia terbaru -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Buku Tersedia</div>
            <a href="katalog.php" class="btn btn-outline btn-sm">Lihat katalog</a>
          </div>
          <?php foreach ($katalog as $b): ?>
          <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0;border-bottom:1px solid rgba(196,154,60,0.1);">
            <div style="width:8px;height:8px;border-radius:50%;background:var(--green);flex-shrink:0;"></div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:0.85rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($b['judul']) ?></div>
              <div style="font-size:0.73rem;color:var(--muted);"><?= e($b['penulis']) ?></div>
            </div>
            <span style="font-size:0.72rem;color:var(--green);"><?= $b['stok_tersedia'] ?> stok</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>