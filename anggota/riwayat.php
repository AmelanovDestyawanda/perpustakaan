<?php
// anggota/riwayat.php
require_once '../config/koneksi.php';
requireAnggota();

$page_title  = 'Riwayat Peminjaman';
$active_menu = 'riwayat';
$user_id     = $_SESSION['user_id'];

// Filter status
$filter = $_GET['status'] ?? '';
$validFilters = ['', 'dipinjam', 'dikembalikan', 'terlambat'];
if (!in_array($filter, $validFilters)) $filter = '';

$where  = ['p.user_id = ?'];
$params = [$user_id];

if ($filter) {
    $where[]  = "p.status = ?";
    $params[] = $filter;
}

$stmt = $pdo->prepare("
    SELECT p.*, b.judul, b.penulis, b.penerbit, k.nama AS nama_kategori
    FROM peminjaman p
    JOIN buku b ON p.buku_id = b.id
    LEFT JOIN kategori_buku k ON b.kategori_id = k.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$riwayat = $stmt->fetchAll();

// Ringkasan cepat
$stmtRing = $pdo->prepare("
    SELECT
      COUNT(*) AS total,
      SUM(status IN ('dipinjam','terlambat')) AS aktif,
      SUM(status = 'dikembalikan') AS selesai,
      SUM(status = 'terlambat') AS terlambat,
      COALESCE(SUM(denda),0) AS total_denda
    FROM peminjaman WHERE user_id = ?
");
$stmtRing->execute([$user_id]);
$ring = $stmtRing->fetch();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= e($page_title) ?> — Perpustakaan</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <style>
    /* ── Timeline List ─────────────────────────────── */
    .timeline { display: flex; flex-direction: column; gap: 0; }

    .timeline-item {
      display: flex;
      gap: 1rem;
      padding: 1.1rem 0;
      border-bottom: 1px solid rgba(196,154,60,0.1);
      position: relative;
      transition: background 0.15s;
    }

    .timeline-item:last-child { border-bottom: none; }
    .timeline-item:hover { background: rgba(196,154,60,0.03); border-radius: 8px; padding-left: 8px; padding-right: 8px; margin: 0 -8px; }

    /* Ikon kiri */
    .tl-icon {
      width: 40px; height: 40px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      margin-top: 2px;
    }
    .tl-icon svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .tl-icon.dipinjam     { background: rgba(27,94,166,0.1);  color: var(--blue); }
    .tl-icon.dikembalikan { background: rgba(46,107,62,0.1);  color: var(--green); }
    .tl-icon.terlambat    { background: rgba(168,50,50,0.1);  color: var(--red); }

    /* Konten */
    .tl-body { flex: 1; min-width: 0; }
    .tl-title {
      font-weight: 500;
      font-size: 0.92rem;
      color: var(--brown);
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
      margin-bottom: 0.25rem;
    }
    .tl-sub { font-size: 0.78rem; color: var(--muted); }

    /* Grid info tanggal */
    .tl-dates {
      display: flex;
      gap: 1.5rem;
      margin-top: 0.5rem;
      flex-wrap: wrap;
    }
    .tl-date-item { display: flex; flex-direction: column; }
    .tl-date-label { font-size: 0.68rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
    .tl-date-val   { font-size: 0.82rem; font-weight: 500; color: var(--brown); }

    /* Kanan: badge + denda */
    .tl-right {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 0.4rem;
      flex-shrink: 0;
    }

    .denda-tag {
      font-size: 0.76rem;
      font-weight: 500;
      color: var(--red);
      background: rgba(168,50,50,0.08);
      border: 1px solid rgba(168,50,50,0.15);
      padding: 0.2rem 0.6rem;
      border-radius: 20px;
    }

    /* ── Ringkasan Box ─────────────────────────────── */
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0.75rem;
      margin-bottom: 1.25rem;
    }

    .summary-box {
      background: var(--cream-lt);
      border: 1px solid rgba(196,154,60,0.15);
      border-radius: var(--radius-lg);
      padding: 1rem;
      text-align: center;
      box-shadow: var(--shadow-sm);
    }

    .summary-num {
      font-size: 1.5rem;
      font-weight: 500;
      color: var(--brown);
      line-height: 1.2;
    }

    .summary-label {
      font-size: 0.72rem;
      color: var(--muted);
      margin-top: 3px;
    }

    /* ── Filter chips ─────────────────────────────── */
    .filter-bar {
      display: flex;
      gap: 0.45rem;
      flex-wrap: wrap;
      margin-bottom: 1.25rem;
    }

    .f-chip {
      padding: 0.3rem 0.85rem;
      border-radius: 20px;
      font-size: 0.8rem;
      border: 1.5px solid rgba(196,154,60,0.25);
      background: var(--cream-lt);
      color: var(--muted);
      cursor: pointer;
      transition: all 0.15s;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    .f-chip .dot {
      width: 6px; height: 6px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .f-chip:hover { border-color: var(--brown); color: var(--brown); }

    .f-chip.active-all         { background: var(--brown);  color: var(--gold-lt); border-color: var(--brown); }
    .f-chip.active-dipinjam    { background: rgba(27,94,166,0.1);  color: var(--blue);  border-color: rgba(27,94,166,0.3); }
    .f-chip.active-dikembalikan{ background: rgba(46,107,62,0.1);  color: var(--green); border-color: rgba(46,107,62,0.3); }
    .f-chip.active-terlambat   { background: rgba(168,50,50,0.1);  color: var(--red);   border-color: rgba(168,50,50,0.3); }

    @media (max-width: 600px) {
      .summary-grid { grid-template-columns: 1fr 1fr; }
      .tl-dates { gap: 0.75rem; }
    }
  </style>
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
          <h1>Riwayat Peminjaman</h1>
          <p>Semua transaksi peminjaman buku Anda</p>
        </div>
      </div>

      <!-- Ringkasan -->
      <div class="summary-grid">
        <div class="summary-box">
          <div class="summary-num"><?= $ring['total'] ?></div>
          <div class="summary-label">Total Pinjam</div>
        </div>
        <div class="summary-box">
          <div class="summary-num" style="color:var(--blue);"><?= $ring['aktif'] ?></div>
          <div class="summary-label">Sedang Dipinjam</div>
        </div>
        <div class="summary-box">
          <div class="summary-num" style="color:var(--green);"><?= $ring['selesai'] ?></div>
          <div class="summary-label">Dikembalikan</div>
        </div>
        <div class="summary-box">
          <div class="summary-num" style="color:var(--red);"><?= $ring['terlambat'] ?></div>
          <div class="summary-label">Pernah Terlambat</div>
        </div>
      </div>

      <!-- Denda total jika ada -->
      <?php if ($ring['total_denda'] > 0): ?>
      <div class="flash flash-error" style="margin-bottom:1.25rem;">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Total denda yang pernah dikenakan: <strong><?= rupiah($ring['total_denda']) ?></strong>. Harap kembalikan buku tepat waktu.
      </div>
      <?php endif; ?>

      <!-- Filter bar -->
      <div class="filter-bar">
        <?php
        $chips = [
          ''             => ['label' => 'Semua', 'dot' => 'var(--brown)',   'active' => 'active-all'],
          'dipinjam'     => ['label' => 'Dipinjam',     'dot' => 'var(--blue)',  'active' => 'active-dipinjam'],
          'dikembalikan' => ['label' => 'Dikembalikan', 'dot' => 'var(--green)', 'active' => 'active-dikembalikan'],
          'terlambat'    => ['label' => 'Terlambat',    'dot' => 'var(--red)',   'active' => 'active-terlambat'],
        ];
        foreach ($chips as $val => $chip):
          $isActive = $filter === $val;
        ?>
        <a href="?status=<?= $val ?>" class="f-chip <?= $isActive ? $chip['active'] : '' ?>">
          <span class="dot" style="background:<?= $chip['dot'] ?>;"></span>
          <?= $chip['label'] ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Daftar Riwayat -->
      <div class="card">
        <?php if ($riwayat): ?>
        <div class="timeline">
          <?php foreach ($riwayat as $r): ?>
          <?php
            $statusKey = $r['status'];
            $ikonPaths = [
              'dipinjam'     => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
              'dikembalikan' => '<polyline points="20 6 9 17 4 12"/>',
              'terlambat'    => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
            ];
            $statusLabel = [
              'dipinjam'     => 'Dipinjam',
              'dikembalikan' => 'Dikembalikan',
              'terlambat'    => 'Terlambat',
            ];
            $badgeClass = [
              'dipinjam'     => 'badge-info',
              'dikembalikan' => 'badge-success',
              'terlambat'    => 'badge-danger',
            ];
          ?>
          <div class="timeline-item">
            <!-- Ikon -->
            <div class="tl-icon <?= $statusKey ?>">
              <svg viewBox="0 0 24 24"><?= $ikonPaths[$statusKey] ?? '' ?></svg>
            </div>

            <!-- Konten -->
            <div class="tl-body">
              <div class="tl-title"><?= e($r['judul']) ?></div>
              <div class="tl-sub"><?= e($r['penulis']) ?>
                <?php if ($r['nama_kategori']): ?>
                · <span style="color:var(--gold);"><?= e($r['nama_kategori']) ?></span>
                <?php endif; ?>
              </div>

              <div class="tl-dates">
                <div class="tl-date-item">
                  <span class="tl-date-label">Kode</span>
                  <span class="tl-date-val" style="font-family:monospace;font-size:0.78rem;"><?= e($r['kode']) ?></span>
                </div>
                <div class="tl-date-item">
                  <span class="tl-date-label">Tgl Pinjam</span>
                  <span class="tl-date-val"><?= tglIndo($r['tgl_pinjam']) ?></span>
                </div>
                <div class="tl-date-item">
                  <span class="tl-date-label">Batas Kembali</span>
                  <span class="tl-date-val" style="color:<?= $statusKey === 'terlambat' ? 'var(--red)' : 'inherit' ?>">
                    <?= tglIndo($r['tgl_kembali']) ?>
                  </span>
                </div>
                <?php if ($r['tgl_kembali_aktual']): ?>
                <div class="tl-date-item">
                  <span class="tl-date-label">Dikembalikan</span>
                  <span class="tl-date-val" style="color:var(--green);"><?= tglIndo($r['tgl_kembali_aktual']) ?></span>
                </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Kanan: badge + denda -->
            <div class="tl-right">
              <span class="badge <?= $badgeClass[$statusKey] ?? 'badge-muted' ?>">
                <?= $statusLabel[$statusKey] ?? $statusKey ?>
              </span>
              <?php if ($r['denda'] > 0): ?>
              <span class="denda-tag">Denda: <?= rupiah($r['denda']) ?></span>
              <?php endif; ?>
              <?php if ($statusKey === 'terlambat'): ?>
              <?php
                $hariTerlambat = (int)((time() - strtotime($r['tgl_kembali'])) / 86400);
                $dendaBerjalan = $hariTerlambat * DENDA_PER_HARI;
              ?>
              <span style="font-size:0.72rem; color:var(--red);"><?= $hariTerlambat ?> hari terlambat</span>
              <span style="font-size:0.72rem; color:var(--red); font-weight:500;"><?= rupiah($dendaBerjalan) ?> (berjalan)</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="empty-state" style="padding:3.5rem 1rem;">
          <svg viewBox="0 0 24 24">
            <polyline points="12 8 12 12 14 14"/>
            <path d="M3.05 11a9 9 0 1 0 .5-4.5"/>
            <polyline points="3 3 3 7 7 7"/>
          </svg>
          <p>
            <?php if ($filter): ?>
              Tidak ada peminjaman dengan status <strong><?= $statusLabel[$filter] ?? $filter ?></strong>
            <?php else: ?>
              Anda belum pernah meminjam buku.
            <?php endif; ?>
          </p>
          <?php if (!$filter): ?>
          <a href="katalog.php" style="margin-top:0.75rem; display:inline-block; font-size:0.85rem; color:var(--gold);">
            Jelajahi katalog buku →
          </a>
          <?php else: ?>
          <a href="riwayat.php" style="margin-top:0.75rem; display:inline-block; font-size:0.85rem; color:var(--gold);">
            Tampilkan semua riwayat →
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>