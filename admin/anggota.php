<?php
// admin/anggota.php
require_once '../config/koneksi.php';
requireAdmin();

$page_title  = 'Manajemen Anggota';
$active_menu = 'anggota';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'toggle_status') {
        $id     = (int)$_POST['id'];
        $status = $_POST['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        $pdo->prepare("UPDATE users SET status=? WHERE id=? AND role='anggota'")->execute([$status, $id]);
        setFlash('success', 'Status anggota berhasil diubah.');
    } elseif ($aksi === 'hapus') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM users WHERE id=? AND role='anggota'")->execute([$id]);
        setFlash('success', 'Anggota berhasil dihapus.');
    }
    header('Location: anggota.php'); exit;
}

$search = trim($_GET['q'] ?? '');
$where  = ["role='anggota'"];
$params = [];
if ($search) { $where[] = "(nama LIKE ? OR username LIKE ? OR email LIKE ?)"; $params = ["%$search%", "%$search%", "%$search%"]; }

$stmt     = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM peminjaman p WHERE p.user_id = u.id AND p.status='dipinjam') AS aktif_pinjam FROM users u WHERE " . implode(' AND ', $where) . " ORDER BY u.created_at DESC");
$stmt->execute($params);
$anggota  = $stmt->fetchAll();
$flash    = getFlash();
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
          <h1>Manajemen Anggota</h1>
          <p>Daftar seluruh anggota perpustakaan</p>
        </div>
        <span class="badge badge-info" style="font-size:0.85rem; padding:0.4rem 0.9rem;"><?= count($anggota) ?> anggota</span>
      </div>

      <div class="card">
        <div style="margin-bottom:1.25rem;">
          <form method="GET">
            <div class="search-bar" style="max-width:320px;">
              <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="q" id="table-search" placeholder="Cari nama, username, email…" value="<?= e($search) ?>"/>
            </div>
          </form>
        </div>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Anggota</th>
                <th>Email</th>
                <th>No. Telepon</th>
                <th>Aktif Pinjam</th>
                <th>Status</th>
                <th>Bergabung</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($anggota): ?>
              <?php foreach ($anggota as $i => $a): ?>
              <?php $initials = strtoupper(substr($a['nama'], 0, 1)); ?>
              <tr>
                <td style="color:var(--muted);font-size:0.8rem;"><?= $i+1 ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:0.6rem;">
                    <div style="width:32px;height:32px;border-radius:50%;background:rgba(196,154,60,0.15);border:1px solid rgba(196,154,60,0.3);display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:500;color:var(--gold);flex-shrink:0;"><?= $initials ?></div>
                    <div>
                      <div style="font-weight:500;font-size:0.875rem;"><?= e($a['nama']) ?></div>
                      <div style="font-size:0.73rem;color:var(--muted);">@<?= e($a['username']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="font-size:0.85rem;"><?= e($a['email']) ?></td>
                <td style="font-size:0.85rem;"><?= e($a['no_telp'] ?? '—') ?></td>
                <td style="text-align:center;">
                  <span class="badge <?= $a['aktif_pinjam'] > 0 ? 'badge-info' : 'badge-muted' ?>"><?= $a['aktif_pinjam'] ?> buku</span>
                </td>
                <td>
                  <span class="badge <?= $a['status'] === 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                    <?= ucfirst($a['status']) ?>
                  </span>
                </td>
                <td style="font-size:0.8rem;color:var(--muted);"><?= tglIndo($a['created_at']) ?></td>
                <td>
                  <div class="td-actions">
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="aksi" value="toggle_status"/>
                      <input type="hidden" name="id" value="<?= $a['id'] ?>"/>
                      <input type="hidden" name="status" value="<?= $a['status'] ?>"/>
                      <button type="submit" class="btn btn-outline btn-sm">
                        <?= $a['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                      </button>
                    </form>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="aksi" value="hapus"/>
                      <input type="hidden" name="id" value="<?= $a['id'] ?>"/>
                      <button type="submit" class="btn btn-danger btn-sm" data-confirm="Hapus anggota <?= e($a['nama']) ?>? Data peminjaman juga akan terhapus.">Hapus</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php else: ?>
              <tr><td colspan="8">
                <div class="empty-state">
                  <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                  <p>Tidak ada anggota ditemukan</p>
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

<script src="../assets/js/main.js"></script>
</body>
</html>