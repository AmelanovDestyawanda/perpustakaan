<?php
// anggota/katalog.php
require_once '../config/koneksi.php';
requireAnggota();

$page_title  = 'Katalog Buku';
$active_menu = 'katalog';
$user_id     = $_SESSION['user_id'];

// Filter & Search
$search          = trim($_POST['q'] ?? '');
$kategori_filter = (int)($_POST['kategori'] ?? 0);
$status_filter   = $_POST['stok'] ?? ''; // 'tersedia' | ''

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = "(b.judul LIKE ? OR b.penulis LIKE ? OR b.penerbit LIKE ?)";
    $params   = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($kategori_filter) {
    $where[]  = "b.kategori_id = ?";
    $params[] = $kategori_filter;
}
if ($status_filter === 'tersedia') {
    $where[] = "b.stok_tersedia > 0";
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die('CSRF detected');
    }
}

$sql = "
    SELECT b.*, k.nama AS nama_kategori
    FROM buku b
    LEFT JOIN kategori_buku k ON b.kategori_id = k.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY b.judul ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bukuList = $stmt->fetchAll();

$kategoris = $pdo->query("SELECT * FROM kategori_buku ORDER BY nama")->fetchAll();
$flash     = getFlash();
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
    /* ── Book Grid ──────────────────────────────────── */
    .book-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
      gap: 1.1rem;
    }

    .book-card {
      background: var(--cream-lt);
      border: 1px solid rgba(196,154,60,0.15);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      transition: transform 0.18s, box-shadow 0.18s;
    }

    .book-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
    }

    /* Cover buku */
    .book-cover {
      height: 160px;
      background: var(--brown);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    .book-cover::before {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(92,61,30,0.8) 0%, rgba(30,18,8,0.6) 100%);
    }

    .book-cover-pattern {
      position: absolute; inset: 0;
      background-image:
        repeating-linear-gradient(45deg, transparent, transparent 8px, rgba(196,154,60,0.06) 8px, rgba(196,154,60,0.06) 9px);
    }

    .book-cover-spine {
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 10px;
      background: rgba(0,0,0,0.3);
      border-right: 1px solid rgba(196,154,60,0.15);
    }

    .book-cover-icon {
      position: relative; z-index: 1;
      display: flex; flex-direction: column; align-items: center; gap: 0.4rem;
    }

    .book-cover-icon svg {
      width: 36px; height: 36px;
      fill: rgba(237,217,138,0.5);
    }

    .book-cover-label {
      font-family: 'Playfair Display', serif;
      font-size: 0.62rem;
      color: rgba(237,217,138,0.5);
      letter-spacing: 0.1em;
      text-transform: uppercase;
    }

    /* Warna cover berdasarkan index */
    .book-cover.c0 { background: #3B2A1A; }
    .book-cover.c1 { background: #1A2E3B; }
    .book-cover.c2 { background: #1A3B2A; }
    .book-cover.c3 { background: #2E1A3B; }
    .book-cover.c4 { background: #3B1A1A; }
    .book-cover.c5 { background: #1A3B3B; }

    .book-body {
      padding: 0.9rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .book-title {
      font-weight: 500;
      font-size: 0.88rem;
      color: var(--brown);
      line-height: 1.3;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .book-author {
      font-size: 0.76rem;
      color: var(--muted);
    }

    .book-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: auto;
      padding-top: 0.65rem;
    }

    .book-stok {
      font-size: 0.72rem;
      font-weight: 500;
    }

    .book-stok.ada   { color: var(--green); }
    .book-stok.habis { color: var(--red); }

    .btn-detail {
      font-size: 0.75rem;
      padding: 0.3rem 0.7rem;
      border-radius: 6px;
      background: var(--brown);
      color: var(--gold-lt);
      border: none;
      cursor: pointer;
      transition: background 0.15s;
    }
    .btn-detail:hover { background: var(--brown2); }

    /* ── Filter Chip ─────────────────────────────── */
    .filter-chips {
      display: flex;
      gap: 0.45rem;
      flex-wrap: wrap;
      align-items: center;
    }

    .chip {
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.78rem;
      border: 1.5px solid rgba(196,154,60,0.25);
      background: var(--cream-lt);
      color: var(--brown);
      cursor: pointer;
      transition: all 0.15s;
      text-decoration: none;
      white-space: nowrap;
    }

    .chip:hover, .chip.active {
      background: var(--brown);
      color: var(--gold-lt);
      border-color: var(--brown);
    }

    /* ── Modal detail buku ───────────────────────── */
    .detail-cover {
      height: 100px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      margin-bottom: 1.25rem;
    }

    .detail-cover svg { position: relative; z-index: 1; width: 40px; height: 40px; fill: rgba(237,217,138,0.5); }

    .detail-row {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      padding: 0.5rem 0;
      border-bottom: 1px solid rgba(196,154,60,0.08);
      font-size: 0.875rem;
    }

    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: var(--muted); min-width: 110px; font-size: 0.8rem; }
    .detail-val   { color: var(--brown); font-weight: 500; }
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
          <h1>Katalog Buku</h1>
          <p>Jelajahi koleksi <?= count($bukuList) ?> buku di perpustakaan kami</p>
        </div>
      </div>

      <!-- Filter & Search Bar -->
      <div class="card" style="margin-bottom:1.25rem; padding:1rem 1.25rem;">
        <form method="POST" id="filter-form">
          <div style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">

            <!-- Search -->
            <div class="search-bar" style="flex:1; min-width:220px;">
              <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="q" placeholder="Cari judul, penulis, penerbit…" value="<?= e($search) ?>" style="width:100%;"/>
            </div>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- Filter kategori -->
            <select name="kategori" onchange="this.form.submit()"
              style="padding:0.5rem 0.8rem; border:1.5px solid rgba(196,154,60,0.25); border-radius:10px; background:#FDFAF5; color:var(--brown); font-size:0.875rem; outline:none; cursor:pointer;">
              <option value="">Semua Kategori</option>
              <?php foreach ($kategoris as $k): ?>
              <option value="<?= $k['id'] ?>" <?= $kategori_filter == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
              <?php endforeach; ?>
            </select>

            <!-- Filter stok -->
            <div class="filter-chips">
              <button type="submit" name="stok" value=""
                  class="chip <?= $status_filter === '' ? 'active' : '' ?>">
                  Semua
              </button>
              <button type="submit" name="stok" value="tersedia"
                  class="chip <?= $status_filter === 'tersedia' ? 'active' : '' ?>">
                  Tersedia
              </button>
            </div>

            <!-- Tombol cari -->
            <button type="submit" class="btn btn-primary" style="padding:0.5rem 1rem;">
              <svg viewBox="0 0 24 24" style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              Cari
            </button>
            <?php if ($search || $kategori_filter || $status_filter): ?>
            <a href="katalog.php" class="btn btn-outline" style="padding:0.5rem 1rem;">Reset</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Book Grid -->
      <?php if ($bukuList): ?>
      <div class="book-grid">
        <?php foreach ($bukuList as $i => $b): ?>
        <?php $colorClass = 'c' . ($i % 6); ?>
        <div class="book-card">
          <!-- Cover -->
          <div class="book-cover <?= $colorClass ?>">
            <div class="book-cover-pattern"></div>
            <div class="book-cover-spine"></div>
            <div class="book-cover-icon">
              <svg viewBox="0 0 24 24"><path d="M4 4h7a4 4 0 0 1 4 4v12H4V4z"/><path d="M15 8h1a4 4 0 0 1 4 4v8h-5V8z" opacity=".5"/></svg>
              <span class="book-cover-label"><?= e(substr($b['nama_kategori'] ?? 'Umum', 0, 10)) ?></span>
            </div>
            <!-- Badge stok -->
            <div style="position:absolute;top:8px;right:8px;z-index:2;">
              <span class="badge <?= $b['stok_tersedia'] > 0 ? 'badge-success' : 'badge-danger' ?>" style="font-size:0.65rem;">
                <?= $b['stok_tersedia'] > 0 ? 'Tersedia' : 'Habis' ?>
              </span>
            </div>
          </div>

          <!-- Info -->
          <div class="book-body">
            <div class="book-title"><?= e($b['judul']) ?></div>
            <div class="book-author"><?= e($b['penulis']) ?></div>
            <?php if ($b['penerbit']): ?>
            <div style="font-size:0.72rem; color:var(--muted);"><?= e($b['penerbit']) ?> <?= $b['tahun_terbit'] ? '· ' . $b['tahun_terbit'] : '' ?></div>
            <?php endif; ?>
            <div class="book-meta">
              <span class="book-stok <?= $b['stok_tersedia'] > 0 ? 'ada' : 'habis' ?>">
                <?= $b['stok_tersedia'] > 0
                  ? '✓ ' . $b['stok_tersedia'] . ' tersedia'
                  : '✗ Tidak tersedia' ?>
              </span>
              <button class="btn-detail"
                onclick='openDetail(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)'>
                Detail
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php else: ?>
      <div class="card">
        <div class="empty-state" style="padding:3.5rem 1rem;">
          <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          <p>Tidak ada buku yang ditemukan</p>
          <a href="katalog.php" style="margin-top:0.75rem; display:inline-block; font-size:0.85rem; color:var(--gold);">Tampilkan semua buku →</a>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- Modal Detail Buku -->
<div class="modal-overlay" id="modal-detail">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <div class="modal-title" id="detail-modal-title">Detail Buku</div>
      <button class="modal-close" data-modal-close="modal-detail">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <!-- Cover mini -->
      <div class="detail-cover" id="detail-cover" style="background:var(--brown);">
        <div style="position:absolute;inset:0;background-image:repeating-linear-gradient(45deg,transparent,transparent 8px,rgba(196,154,60,0.06) 8px,rgba(196,154,60,0.06) 9px);"></div>
        <svg viewBox="0 0 24 24"><path d="M4 4h7a4 4 0 0 1 4 4v12H4V4z"/><path d="M15 8h1a4 4 0 0 1 4 4v8h-5V8z" opacity=".5"/></svg>
      </div>

      <div id="detail-content">
        <div class="detail-row"><span class="detail-label">Judul</span>       <span class="detail-val" id="d-judul">—</span></div>
        <div class="detail-row"><span class="detail-label">Penulis</span>     <span class="detail-val" id="d-penulis">—</span></div>
        <div class="detail-row"><span class="detail-label">Penerbit</span>    <span class="detail-val" id="d-penerbit">—</span></div>
        <div class="detail-row"><span class="detail-label">Tahun Terbit</span><span class="detail-val" id="d-tahun">—</span></div>
        <div class="detail-row"><span class="detail-label">Kategori</span>    <span class="detail-val" id="d-kategori">—</span></div>
        <div class="detail-row"><span class="detail-label">ISBN</span>        <span class="detail-val" id="d-isbn">—</span></div>
        <div class="detail-row"><span class="detail-label">Stok Tersedia</span><span class="detail-val" id="d-stok">—</span></div>
        <div class="detail-row" id="d-desc-row" style="display:none;">
          <span class="detail-label">Deskripsi</span>
          <span class="detail-val" id="d-deskripsi" style="font-weight:400;line-height:1.5;font-size:0.83rem;">—</span>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" data-modal-close="modal-detail">Tutup</button>
      <span id="detail-stok-info" style="font-size:0.8rem;color:var(--muted);align-self:center;"></span>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
  // Warna cover berdasarkan class
  const coverColors = ['#3B2A1A','#1A2E3B','#1A3B2A','#2E1A3B','#3B1A1A','#1A3B3B'];

  function openDetail(data) {
    document.getElementById('detail-modal-title').textContent = data.judul || 'Detail Buku';
    document.getElementById('d-judul').textContent    = data.judul    || '—';
    document.getElementById('d-penulis').textContent  = data.penulis  || '—';
    document.getElementById('d-penerbit').textContent = data.penerbit || '—';
    document.getElementById('d-tahun').textContent    = data.tahun_terbit || '—';
    document.getElementById('d-kategori').textContent = data.nama_kategori || '—';
    document.getElementById('d-isbn').textContent     = data.isbn     || '—';

    const stok = parseInt(data.stok_tersedia);
    const stokEl = document.getElementById('d-stok');
    stokEl.textContent = stok + ' dari ' + data.stok + ' eksemplar';
    stokEl.style.color = stok > 0 ? 'var(--green)' : 'var(--red)';

    const descRow = document.getElementById('d-desc-row');
    if (data.deskripsi) {
      document.getElementById('d-deskripsi').textContent = data.deskripsi;
      descRow.style.display = 'flex';
    } else {
      descRow.style.display = 'none';
    }

    // Warna cover
    const idx = parseInt(data.id) % 6;
    document.getElementById('detail-cover').style.background = coverColors[idx];

    document.getElementById('detail-stok-info').textContent =
      stok > 0 ? '✓ Buku tersedia untuk dipinjam' : '✗ Stok sedang habis';
    document.getElementById('detail-stok-info').style.color =
      stok > 0 ? 'var(--green)' : 'var(--red)';

    document.getElementById('modal-detail').classList.add('open');
  }
</script>
</body>
</html>