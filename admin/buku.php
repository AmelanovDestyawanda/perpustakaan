<?php
// admin/buku.php
require_once '../config/koneksi.php';
requireAdmin();

$page_title  = 'Manajemen Buku';
$active_menu = 'buku';

// Handle aksi POST (tambah/edit/hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $judul       = trim($_POST['judul'] ?? '');
        $penulis     = trim($_POST['penulis'] ?? '');
        $penerbit    = trim($_POST['penerbit'] ?? '');
        $tahun       = trim($_POST['tahun_terbit'] ?? '') ?: null;
        $isbn        = trim($_POST['isbn'] ?? '') ?: null;
        $kategori_id = (int)($_POST['kategori_id'] ?? 0) ?: null;
        $stok        = max(1, (int)($_POST['stok'] ?? 1));
        $deskripsi   = trim($_POST['deskripsi'] ?? '');

        if (empty($judul) || empty($penulis)) {
            setFlash('error', 'Judul dan penulis wajib diisi.');
        } elseif ($aksi === 'tambah') {
            $stmt = $pdo->prepare("INSERT INTO buku (judul, penulis, penerbit, tahun_terbit, isbn, kategori_id, stok, stok_tersedia, deskripsi) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$judul, $penulis, $penerbit, $tahun, $isbn, $kategori_id, $stok, $stok, $deskripsi]);
            setFlash('success', 'Buku berhasil ditambahkan.');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE buku SET judul=?, penulis=?, penerbit=?, tahun_terbit=?, isbn=?, kategori_id=?, stok=?, deskripsi=? WHERE id=?");
            $stmt->execute([$judul, $penulis, $penerbit, $tahun, $isbn, $kategori_id, $stok, $deskripsi, $id]);
            setFlash('success', 'Data buku berhasil diperbarui.');
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM buku WHERE id=?")->execute([$id]);
        setFlash('success', 'Buku berhasil dihapus.');
    }
    header('Location: buku.php'); exit;
}

// Data
$search   = trim($_GET['q'] ?? '');
$kategori_filter = (int)($_GET['kategori'] ?? 0);
$where    = ['1=1'];
$params   = [];
if ($search) { $where[] = "(b.judul LIKE ? OR b.penulis LIKE ? OR b.isbn LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
if ($kategori_filter) { $where[] = "b.kategori_id = ?"; $params[] = $kategori_filter; }

$sql   = "SELECT b.*, k.nama AS nama_kategori FROM buku b LEFT JOIN kategori_buku k ON b.kategori_id = k.id WHERE " . implode(' AND ', $where) . " ORDER BY b.created_at DESC";
$stmt  = $pdo->prepare($sql); $stmt->execute($params);
$buku  = $stmt->fetchAll();

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
          <h1>Manajemen Buku</h1>
          <p>Kelola seluruh koleksi buku perpustakaan</p>
        </div>
        <button class="btn btn-primary" data-modal-open="modal-tambah">
          <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Tambah Buku
        </button>
      </div>

      <div class="card">
        <!-- Filter bar -->
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.25rem;">
          <form method="GET" style="display:flex; gap:0.65rem; flex:1; flex-wrap:wrap;">
            <div class="search-bar" style="flex:1; min-width:200px;">
              <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="q" id="table-search" placeholder="Cari judul, penulis, ISBN…" value="<?= e($search) ?>"/>
            </div>
            <select name="kategori" onchange="this.form.submit()" style="padding:0.5rem 0.8rem; border:1.5px solid rgba(196,154,60,0.25); border-radius:10px; background:#FDFAF5; color:var(--brown); font-size:0.875rem; outline:none; cursor:pointer;">
              <option value="">Semua Kategori</option>
              <?php foreach ($kategoris as $k): ?>
              <option value="<?= $k['id'] ?>" <?= $kategori_filter == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>

        <!-- Tabel -->
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Judul Buku</th>
                <th>Penulis</th>
                <th>Kategori</th>
                <th>Tahun</th>
                <th>Stok</th>
                <th>Tersedia</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($buku): ?>
              <?php foreach ($buku as $i => $b): ?>
              <tr>
                <td style="color:var(--muted);font-size:0.8rem;"><?= $i+1 ?></td>
                <td>
                  <div style="font-weight:500;"><?= e($b['judul']) ?></div>
                  <?php if ($b['isbn']): ?><div style="font-size:0.73rem;color:var(--muted);">ISBN: <?= e($b['isbn']) ?></div><?php endif; ?>
                </td>
                <td><?= e($b['penulis']) ?></td>
                <td><span class="badge badge-muted"><?= e($b['nama_kategori'] ?? '—') ?></span></td>
                <td><?= e($b['tahun_terbit'] ?? '—') ?></td>
                <td><?= $b['stok'] ?></td>
                <td><span class="badge <?= $b['stok_tersedia'] > 0 ? 'badge-success' : 'badge-danger' ?>"><?= $b['stok_tersedia'] ?></span></td>
                <td>
                  <div class="td-actions">
                    <!-- Tombol Edit -->
                    <button class="btn btn-outline btn-sm"
                      onclick="openEditModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)">
                      Edit
                    </button>
                    <!-- Hapus -->
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="aksi" value="hapus"/>
                      <input type="hidden" name="id" value="<?= $b['id'] ?>"/>
                      <button type="submit" class="btn btn-danger btn-sm" data-confirm="Hapus buku '<?= e($b['judul']) ?>'?">Hapus</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="8">
                <div class="empty-state">
                  <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                  <p>Tidak ada buku ditemukan</p>
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

<!-- Modal Tambah Buku -->
<div class="modal-overlay" id="modal-tambah">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Tambah Buku Baru</div>
      <button class="modal-close" data-modal-close="modal-tambah">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST">
      <input type="hidden" name="aksi" value="tambah"/>
      <div class="modal-body">
        <?php include '_form_buku.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close="modal-tambah">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Buku</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Buku -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Data Buku</div>
      <button class="modal-close" data-modal-close="modal-edit">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" id="form-edit">
      <input type="hidden" name="aksi" value="edit"/>
      <input type="hidden" name="id" id="edit-id"/>
      <div class="modal-body">
        <?php include '_form_buku.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close="modal-edit">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function openEditModal(data) {
  document.getElementById('edit-id').value = data.id;
  ['judul','penulis','penerbit','tahun_terbit','isbn','stok','deskripsi'].forEach(k => {
    const el = document.querySelector('#form-edit [name="' + k + '"]');
    if (el) el.value = data[k] || '';
  });
  const kat = document.querySelector('#form-edit [name="kategori_id"]');
  if (kat) kat.value = data.kategori_id || '';
  document.getElementById('modal-edit').classList.add('open');
}
</script>
</body>
</html>