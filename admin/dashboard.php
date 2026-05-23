<?php
// admin/dashboard.php
require_once '../config/koneksi.php';
requireAdmin();

$page_title  = 'Dashboard Admin';
$active_menu = 'dashboard';

// Statistik
$totalBuku     = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalAnggota  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='anggota'")->fetchColumn();
$totalPinjam   = $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam'")->fetchColumn();
$totalTerlambat= $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='terlambat'")->fetchColumn();

// Peminjaman terbaru
$recentPinjam = $pdo->query("
    SELECT p.kode, p.tgl_pinjam, p.tgl_kembali, p.status,
           u.nama AS nama_anggota, b.judul AS judul_buku
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN buku b  ON p.buku_id = b.id
    ORDER BY p.created_at DESC LIMIT 6
")->fetchAll();

// Buku populer (paling sering dipinjam)
$popularBuku = $pdo->query("
    SELECT b.judul, b.penulis, COUNT(p.id) AS jumlah,
           b.stok_tersedia, b.stok
    FROM buku b
    LEFT JOIN peminjaman p ON b.id = p.buku_id
    GROUP BY b.id
    ORDER BY jumlah DESC LIMIT 5
")->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= e($page_title) ?> — Perpustakaan Digital</title>
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
        <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= e($flash['msg']) ?>
      </div>
      <?php endif; ?>

      <div class="page-header">
        <div>
          <h1>Dashboard</h1>
          <p>Selamat datang kembali, <?= e($_SESSION['user_nama']) ?>!</p>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon brown">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalBuku ?></div>
            <div class="stat-label">Total Koleksi Buku</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon gold">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalAnggota ?></div>
            <div class="stat-label">Total Anggota</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalPinjam ?></div>
            <div class="stat-label">Sedang Dipinjam</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon red">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalTerlambat ?></div>
            <div class="stat-label">Terlambat Dikembalikan</div>
          </div>
        </div>
      </div>

      <!-- Dua kolom: Peminjaman terbaru + Buku populer -->
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem;">

        <!-- Peminjaman Terbaru -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Peminjaman Terbaru</div>
            <a href="peminjaman.php" class="btn btn-outline btn-sm">Lihat semua</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Anggota</th>
                  <th>Buku</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($recentPinjam): ?>
                  <?php foreach ($recentPinjam as $row): ?>
                  <tr>
                    <td>
                      <div style="font-weight:500;font-size:0.85rem;"><?= e($row['nama_anggota']) ?></div>
                      <div style="font-size:0.75rem;color:var(--muted);"><?= tglIndo($row['tgl_pinjam']) ?></div>
                    </td>
                    <td style="font-size:0.85rem;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($row['judul_buku']) ?></td>
                    <td>
                      <?php
                        $badgeClass = match($row['status']) {
                          'dipinjam'     => 'badge-info',
                          'dikembalikan' => 'badge-success',
                          'terlambat'    => 'badge-danger',
                          default        => 'badge-muted'
                        };
                        $statusLabel = match($row['status']) {
                          'dipinjam'     => 'Dipinjam',
                          'dikembalikan' => 'Kembali',
                          'terlambat'    => 'Terlambat',
                          default        => ucfirst($row['status'])
                        };
                      ?>
                      <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="3"><div class="empty-state"><p>Belum ada data peminjaman</p></div></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Buku Populer -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Buku Paling Diminati</div>
            <a href="buku.php" class="btn btn-outline btn-sm">Kelola buku</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Judul Buku</th>
                  <th>Dipinjam</th>
                  <th>Stok</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($popularBuku as $b): ?>
                <tr>
                  <td>
                    <div style="font-weight:500;font-size:0.85rem;"><?= e($b['judul']) ?></div>
                    <div style="font-size:0.75rem;color:var(--muted);"><?= e($b['penulis']) ?></div>
                  </td>
                  <td style="text-align:center;"><?= $b['jumlah'] ?>×</td>
                  <td>
                    <span class="badge <?= $b['stok_tersedia'] > 0 ? 'badge-success' : 'badge-danger' ?>">
                      <?= $b['stok_tersedia'] ?>/<?= $b['stok'] ?>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>