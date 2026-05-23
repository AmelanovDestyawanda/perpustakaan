<?php
// admin/peminjaman.php
require_once '../config/koneksi.php';
requireAdmin();

$page_title  = 'Manajemen Peminjaman';
$active_menu = 'peminjaman';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $user_id    = (int)$_POST['user_id'];
        $buku_id    = (int)$_POST['buku_id'];
        $tgl_pinjam = $_POST['tgl_pinjam'];
        $tgl_kembali= $_POST['tgl_kembali'];

        // Cek stok
        $stok = $pdo->prepare("SELECT stok_tersedia FROM buku WHERE id=?");
        $stok->execute([$buku_id]);
        $s = $stok->fetchColumn();

        if ($s < 1) {
            setFlash('error', 'Stok buku tidak tersedia.');
        } else {
            $kode = generateKodePinjam($pdo);
            $pdo->prepare("INSERT INTO peminjaman (kode, user_id, buku_id, tgl_pinjam, tgl_kembali) VALUES (?,?,?,?,?)")
                ->execute([$kode, $user_id, $buku_id, $tgl_pinjam, $tgl_kembali]);
            $pdo->prepare("UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id=?")->execute([$buku_id]);
            setFlash('success', "Peminjaman $kode berhasil dicatat.");
        }
    } elseif ($aksi === 'kembalikan') {
        $id       = (int)$_POST['id'];
        $buku_id  = (int)$_POST['buku_id'];
        $tgl_aktual = date('Y-m-d');
        $tgl_kembali= $_POST['tgl_kembali'];
        $denda    = 0;

        if ($tgl_aktual > $tgl_kembali) {
            $diff  = (strtotime($tgl_aktual) - strtotime($tgl_kembali)) / 86400;
            $denda = $diff * DENDA_PER_HARI;
        }

        $pdo->prepare("UPDATE peminjaman SET status='dikembalikan', tgl_kembali_aktual=?, denda=? WHERE id=?")
            ->execute([$tgl_aktual, $denda, $id]);
        $pdo->prepare("UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id=?")->execute([$buku_id]);
        setFlash('success', $denda > 0 ? 'Buku dikembalikan. Denda: ' . rupiah($denda) : 'Buku berhasil dikembalikan.');
    }
    header('Location: peminjaman.php'); exit;
}

$filter = $_GET['status'] ?? '';
$where  = ['1=1']; $params = [];
if ($filter && in_array($filter, ['dipinjam','dikembalikan','terlambat'])) {
    $where[] = "p.status = ?"; $params[] = $filter;
}

// Update status terlambat otomatis
$pdo->query("UPDATE peminjaman SET status='terlambat' WHERE status='dipinjam' AND tgl_kembali < CURDATE()");

$stmt = $pdo->prepare("
    SELECT p.*, u.nama AS nama_anggota, b.judul AS judul_buku, b.id AS buku_id_ref
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN buku b  ON p.buku_id = b.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$peminjaman = $stmt->fetchAll();

$anggota_list = $pdo->query("SELECT id, nama, username FROM users WHERE role='anggota' AND status='aktif' ORDER BY nama")->fetchAll();
$buku_list    = $pdo->query("SELECT id, judul, stok_tersedia FROM buku WHERE stok_tersedia > 0 ORDER BY judul")->fetchAll();
$flash        = getFlash();
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
          <h1>Peminjaman</h1>
          <p>Kelola transaksi peminjaman dan pengembalian buku</p>
        </div>
        <button class="btn btn-primary" data-modal-open="modal-tambah">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Catat Peminjaman
        </button>
      </div>

      <div class="card">
        <!-- Filter status -->
        <div style="display:flex; gap:0.5rem; margin-bottom:1.25rem; flex-wrap:wrap;">
          <?php
          $statuses = ['' => 'Semua', 'dipinjam' => 'Dipinjam', 'dikembalikan' => 'Dikembalikan', 'terlambat' => 'Terlambat'];
          foreach ($statuses as $val => $label):
          ?>
          <a href="?status=<?= $val ?>"
            class="btn btn-sm <?= $filter === $val ? 'btn-primary' : 'btn-outline' ?>">
            <?= $label ?>
          </a>
          <?php endforeach; ?>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Kode</th>
                <th>Anggota</th>
                <th>Buku</th>
                <th>Tgl Pinjam</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
                <th>Denda</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($peminjaman): ?>
              <?php foreach ($peminjaman as $p): ?>
              <tr>
                <td style="font-family:monospace;font-size:0.8rem;color:var(--muted);"><?= e($p['kode']) ?></td>
                <td style="font-weight:500;font-size:0.875rem;"><?= e($p['nama_anggota']) ?></td>
                <td style="font-size:0.875rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($p['judul_buku']) ?></td>
                <td style="font-size:0.82rem;"><?= tglIndo($p['tgl_pinjam']) ?></td>
                <td style="font-size:0.82rem;"><?= tglIndo($p['tgl_kembali']) ?></td>
                <td>
                  <?php
                    $bc = match($p['status']) {
                      'dipinjam'     => 'badge-info',
                      'dikembalikan' => 'badge-success',
                      'terlambat'    => 'badge-danger',
                      default        => 'badge-muted'
                    };
                    $bl = match($p['status']) {
                      'dipinjam'     => 'Dipinjam',
                      'dikembalikan' => 'Dikembalikan',
                      'terlambat'    => 'Terlambat',
                      default        => $p['status']
                    };
                  ?>
                  <span class="badge <?= $bc ?>"><?= $bl ?></span>
                </td>
                <td style="font-size:0.85rem;">
                  <?= $p['denda'] > 0 ? '<span style="color:var(--red);font-weight:500;">' . rupiah($p['denda']) . '</span>' : '—' ?>
                </td>
                <td>
                  <?php if (in_array($p['status'], ['dipinjam', 'terlambat'])): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="aksi" value="kembalikan"/>
                    <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
                    <input type="hidden" name="buku_id" value="<?= $p['buku_id_ref'] ?>"/>
                    <input type="hidden" name="tgl_kembali" value="<?= $p['tgl_kembali'] ?>"/>
                    <button type="submit" class="btn btn-gold btn-sm" data-confirm="Konfirmasi pengembalian buku ini?">
                      Kembalikan
                    </button>
                  </form>
                  <?php else: ?>
                  <span style="font-size:0.78rem;color:var(--muted);"><?= tglIndo($p['tgl_kembali_aktual']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="8">
                <div class="empty-state">
                  <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                  <p>Tidak ada data peminjaman</p>
                </div>
              </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah Peminjaman -->
<div class="modal-overlay" id="modal-tambah">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Catat Peminjaman Baru</div>
      <button class="modal-close" data-modal-close="modal-tambah">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST">
      <input type="hidden" name="aksi" value="tambah"/>
      <div class="modal-body">
        <div class="form-group">
          <label>Anggota *</label>
          <select name="user_id" required>
            <option value="">— Pilih Anggota —</option>
            <?php foreach ($anggota_list as $a): ?>
            <option value="<?= $a['id'] ?>"><?= e($a['nama']) ?> (@<?= e($a['username']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Buku *</label>
          <select name="buku_id" required>
            <option value="">— Pilih Buku —</option>
            <?php foreach ($buku_list as $b): ?>
            <option value="<?= $b['id'] ?>"><?= e($b['judul']) ?> (stok: <?= $b['stok_tersedia'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Tanggal Pinjam *</label>
            <input type="date" name="tgl_pinjam" value="<?= date('Y-m-d') ?>" required/>
          </div>
          <div class="form-group">
            <label>Tanggal Kembali *</label>
            <input type="date" name="tgl_kembali" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close="modal-tambah">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>