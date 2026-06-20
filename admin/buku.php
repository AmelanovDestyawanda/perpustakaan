<?php
// admin/buku.php
require_once '../config/koneksi.php';
require_once '../config/SimpleXlsxReader.php';
requireAdmin();

$page_title  = 'Manajemen Buku';
$active_menu = 'buku';

if (($_GET['aksi'] ?? '') === 'template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_buku_induk.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); //BOM agar excel baca UTF-8 dengan benar
    fputcsv($out, ['NO','INVENTARIS','JUDUL BUKU','JILID/EDISI','PENGARANG','THN','KOTA TERBIT','PENERBIT','TANGGAL MASUK','NO. KLAS','JMLH TOTAL','TGL CEK','DESKRIPSI','BIDANG']);
    fputcsv($out, ['1','13802/SMKGRISA/SB/2025','Sejarah Tari Jejak Langkah Tari Di Pura Mangkunagaran','','Wahyu Santosa Prabowo','2007','Surakarta','ISI','2025-05-04','793319','1','','xi, 238 hlm; 14,5 x 20 cm isbn: 979-8217-61-6','']);
    fputcsv($out, ['2','13803/SMKGRISA/SB/2025','Clean Code','','Robert C. Martin','2008','Boston','Prentice Hall','2025-05-04','005.1','2','','Panduan menulis kode yang baik','Sains & Teknologi']);
    fclose($out);
    exit;
}

// Handle aksi POST (tambah/edit/hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $no_inventaris = trim($_POST['no_inventaris'] ?? '') ?: null;
        $judul         = trim($_POST['judul'] ?? '');
        $jilid_edisi   = trim($_POST['jilid_edisi'] ?? '') ?: null;
        $penulis       = trim($_POST['penulis'] ?? '');
        $tahun         = trim($_POST['tahun_terbit'] ?? '') ?: null;
        $kota_terbit   = trim($_POST['kota_terbit'] ?? '') ?: null;
        $penerbit      = trim($_POST['penerbit'] ?? '');
        $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? '') ?: null;
        $isbn          = trim($_POST['isbn'] ?? '') ?: null;
        $no_klas       = trim($_POST['no_klas'] ?? '') ?: null;
        $kategori_id   = (int)($_POST['kategori_id'] ?? 0) ?: null;
        $stok          = max(1, (int)($_POST['stok'] ?? 1));
        $tgl_cek       = trim($_POST['tgl_cek'] ?? '') ?: null;
        $deskripsi     = trim($_POST['deskripsi'] ?? '');
        $bidang        = null;
        if ($kategori_id) {
            $stmtBidang = $pdo->prepare("SELECT nama FROM kategori_buku WHERE id = ?");
            $stmtBidang->execute([$kategori_id]);
            $bidang = $stmtBidang->fetchColumn() ?: null;
        }

        if (empty($judul) || empty($penulis)) {
            setFlash('error', 'Judul dan pengarang wajib diisi.');
        } elseif ($aksi === 'tambah') {
            $stmt = $pdo->prepare(
                "INSERT INTO buku
                    (no_inventaris, judul, jilid_edisi, penulis, tahun_terbit, kota_terbit, penerbit, tanggal_masuk, isbn, no_klas, kategori_id, stok, stok_tersedia, tgl_cek, deskripsi, bidang)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $no_inventaris, $judul, $jilid_edisi, $penulis, $tahun, $kota_terbit,
                $penerbit, $tanggal_masuk, $isbn, $no_klas, $kategori_id, $stok, $stok,
                $tgl_cek, $deskripsi, $bidang,
            ]);
            setFlash('success', 'Buku berhasil ditambahkan.');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare(
                "UPDATE buku SET
                    no_inventaris=?, judul=?, jilid_edisi=?, penulis=?, tahun_terbit=?, kota_terbit=?,
                    penerbit=?, tanggal_masuk=?, isbn=?, no_klas=?, kategori_id=?, stok=?, tgl_cek=?, deskripsi=?, bidang=?
                 WHERE id=?"
            );
            $stmt->execute([
                $no_inventaris, $judul, $jilid_edisi, $penulis, $tahun, $kota_terbit,
                $penerbit, $tanggal_masuk, $isbn, $no_klas, $kategori_id, $stok,
                $tgl_cek, $deskripsi, $bidang, $id,
            ]);
            setFlash('success', 'Data buku berhasil diperbarui.');
        }
    } elseif ($aksi === 'hapus') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM buku WHERE id=?")->execute([$id]);
        setFlash('success', 'Buku berhasil dihapus.');
    } elseif ($aksi === 'import') {
        try {
            $hasil = prosesImportBuku($pdo, $_FILES['file_import'] ?? null);
            if ($hasil['gagal'] > 0) {
                setFlash('error', "Import selesai: {$hasil['berhasil']} buku berhasil ditambahkan, {$hasil['gagal']} baris gagal/dilewati. " . implode(' ', $hasil['pesan']));
            } else {
                setFlash('success', "Import berhasil! {$hasil['berhasil']} buku baru ditambahkan ke perpustakaan.");
            }
        } catch (Exception $e) {
            setFlash('error', 'Import gagal: ' . $e->getMessage());
        }
    }
    header('Location: buku.php'); exit;
}

// ============================================================
//  Fungsi: ubah angka serial tanggal Excel ke format YYYY-MM-DD
//  Excel menyimpan tanggal sebagai angka (jumlah hari sejak
//  30 Desember 1899). Kalau nilainya bukan angka serial yang
//  wajar, kembalikan apa adanya (mungkin sudah berupa teks
//  tanggal biasa, misal dari file CSV).
// ============================================================
function konversiTanggalExcel(string $nilai): ?string
{
    $nilai = trim($nilai);
    if ($nilai === '') return null;

    // Kalau sudah berformat tanggal umum (YYYY-MM-DD atau DD/MM/YYYY dst), pakai langsung
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $nilai)) {
        return substr($nilai, 0, 10);
    }

    // Angka serial Excel murni (umumnya 1 - 100000 untuk tanggal antara 1900-2173)
    if (is_numeric($nilai) && (int)$nilai == $nilai && (int)$nilai > 0 && (int)$nilai < 200000) {
        $unixTimestamp = ((int)$nilai - 25569) * 86400; // 25569 = selisih hari Excel epoch ke Unix epoch
        return gmdate('Y-m-d', $unixTimestamp);
    }

    // Coba parse format umum lain (DD/MM/YYYY, DD-MM-YYYY)
    $ts = strtotime($nilai);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }

    return null; // tidak bisa dikenali, biarkan kosong daripada salah
}

// ============================================================
//  Fungsi: proses import file Excel (.xlsx) atau CSV
//  Mendukung format "BUKU INDUK" sekolah dengan kolom:
//  NO, INVENTARIS, JUDUL BUKU, JILID/EDISI, PENGARANG, THN,
//  KOTA TERBIT, PENERBIT, TANGGAL MASUK, NO. KLAS, JMLH TOTAL,
//  TGL CEK, DESKRIPSI, BIDANG
// ============================================================
function prosesImportBuku(PDO $pdo, ?array $file): array
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new Exception('Tidak ada file yang dipilih.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload file gagal (kode error: ' . $file['error'] . ').');
    }

    $namaFile = $file['name'];
    $ext      = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
    $tmpPath  = $file['tmp_name'];

    if (!in_array($ext, ['xlsx', 'csv'])) {
        throw new Exception('Format file tidak didukung. Gunakan file .xlsx atau .csv.');
    }

    // Batasi ukuran file (5MB) untuk keamanan
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Ukuran file terlalu besar. Maksimal 5MB.');
    }

    // Baca seluruh baris dari file (array of array kolom)
    if ($ext === 'xlsx') {
        $rows = SimpleXlsxReader::read($tmpPath);
    } else {
        $rows = [];
        $handle = fopen($tmpPath, 'r');
        if ($handle === false) {
            throw new Exception('Tidak bisa membuka file CSV.');
        }
        // Lewati BOM UTF-8 jika ada
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = $data;
        }
        fclose($handle);
    }

    if (empty($rows)) {
        throw new Exception('File kosong atau tidak ada data yang bisa dibaca.');
    }

    // Baris pertama dianggap header -> cocokkan ke nama kolom Buku Induk
    $header = array_map(function ($h) {
        return strtoupper(trim((string)$h));
    }, $rows[0]);

    // Mapping nama kolom header (boleh beberapa alias) -> key internal
    $aliasKolom = [
        'no_inventaris' => ['INVENTARIS', 'NO INVENTARIS', 'NO. INVENTARIS'],
        'judul'         => ['JUDUL BUKU', 'JUDUL'],
        'jilid_edisi'   => ['JILID/EDISI', 'JILID / EDISI', 'JILID', 'EDISI'],
        'penulis'       => ['PENGARANG', 'PENULIS'],
        'tahun_terbit'  => ['THN', 'TAHUN', 'TAHUN TERBIT'],
        'kota_terbit'   => ['KOTA TERBIT', 'KOTA'],
        'penerbit'      => ['PENERBIT'],
        'tanggal_masuk' => ['TANGGAL MASUK', 'TGL MASUK'],
        'no_klas'       => ['NO. KLAS', 'NO KLAS', 'NOMOR KLASIFIKASI'],
        'stok'          => ['JMLH TOTAL', 'JUMLAH TOTAL', 'JUMLAH', 'STOK'],
        'tgl_cek'       => ['TGL CEK', 'TANGGAL CEK'],
        'deskripsi'     => ['DESKRIPSI'],
        'bidang'        => ['BIDANG', 'KATEGORI'],
    ];

    $kolomIndex = [];
    foreach ($aliasKolom as $key => $aliases) {
        $idx = null;
        foreach ($aliases as $alias) {
            $found = array_search($alias, $header);
            if ($found !== false) { $idx = $found; break; }
        }
        $kolomIndex[$key] = $idx;
    }

    if ($kolomIndex['judul'] === null || $kolomIndex['penulis'] === null) {
        throw new Exception('Header file tidak sesuai. Pastikan ada kolom "JUDUL BUKU" dan "PENGARANG" (gunakan tombol "Unduh Template" untuk format yang benar).');
    }

    // Ambil daftar kategori yang sudah ada, untuk mencocokkan nama bidang -> kategori_id
    $kategoriMap = [];
    foreach ($pdo->query("SELECT id, nama FROM kategori_buku")->fetchAll() as $k) {
        $kategoriMap[strtolower(trim($k['nama']))] = $k['id'];
    }

    $stmtInsert = $pdo->prepare(
        "INSERT INTO buku
            (no_inventaris, judul, jilid_edisi, penulis, tahun_terbit, kota_terbit, penerbit, tanggal_masuk, no_klas, kategori_id, stok, stok_tersedia, tgl_cek, deskripsi, bidang)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );

    $berhasil = 0;
    $gagal    = 0;
    $pesan    = [];

    // Mulai dari baris ke-2 (index 1), karena baris ke-1 adalah header
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $baris = $i + 1; // nomor baris asli di file (untuk pesan error)

        $ambil = function ($kolom) use ($row, $kolomIndex) {
            $idx = $kolomIndex[$kolom];
            if ($idx === null || !isset($row[$idx])) return '';
            return trim((string)$row[$idx]);
        };

        $judul    = $ambil('judul');
        $penulis  = $ambil('penulis');

        // Lewati baris yang benar-benar kosong
        if ($judul === '' && $penulis === '') continue;

        if ($judul === '' || $penulis === '') {
            $gagal++;
            if (count($pesan) < 5) $pesan[] = "Baris {$baris}: judul/pengarang kosong, dilewati.";
            continue;
        }

        $no_inventaris = $ambil('no_inventaris') ?: null;
        $jilid_edisi    = $ambil('jilid_edisi') ?: null;
        $tahun          = $ambil('tahun_terbit');
        $tahun          = ($tahun !== '' && is_numeric($tahun)) ? (int)$tahun : null;
        $kota_terbit    = $ambil('kota_terbit') ?: null;
        $penerbit       = $ambil('penerbit') ?: null;
        $tanggal_masuk  = konversiTanggalExcel($ambil('tanggal_masuk'));
        $no_klas        = $ambil('no_klas') ?: null;
        $tgl_cek        = konversiTanggalExcel($ambil('tgl_cek'));
        $deskripsi      = $ambil('deskripsi');
        $bidang         = $ambil('bidang') ?: null;

        $stok = $ambil('stok');
        $stok = ($stok !== '' && is_numeric($stok)) ? max(1, (int)$stok) : 1;

        $kategori_id = $bidang !== null ? ($kategoriMap[strtolower($bidang)] ?? null) : null;

        try {
            $stmtInsert->execute([
                $no_inventaris, $judul, $jilid_edisi, $penulis, $tahun, $kota_terbit,
                $penerbit, $tanggal_masuk, $no_klas, $kategori_id, $stok, $stok,
                $tgl_cek, $deskripsi, $bidang,
            ]);
            $berhasil++;
        } catch (PDOException $e) {
            $gagal++;
            if (count($pesan) < 5) $pesan[] = "Baris {$baris}: gagal disimpan ({$e->getMessage()}).";
        }
    }

    return ['berhasil' => $berhasil, 'gagal' => $gagal, 'pesan' => $pesan];
}

// Data
$search   = trim($_GET['q'] ?? '');
$kategori_filter = (int)($_GET['kategori'] ?? 0);
$where    = ['1=1'];
$params   = [];
if ($search) { $where[] = "(b.judul LIKE ? OR b.penulis LIKE ? OR b.isbn LIKE ? OR b.no_inventaris LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]); }
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
        <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
          <a href="?aksi=template" class="btn btn-outline">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Unduh Template
          </a>
          <button class="btn btn-outline" data-modal-open="modal-import">
            <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import Excel
          </button>
          <button class="btn btn-primary" data-modal-open="modal-tambah">
            <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Buku
          </button>
        </div>
      </div>

      <div class="card">
        <!-- Filter bar -->
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.25rem;">
          <form method="GET" style="display:flex; gap:0.65rem; flex:1; flex-wrap:wrap;">
            <div class="search-bar" style="flex:1; min-width:200px;">
              <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="q" id="table-search" placeholder="Cari judul, pengarang, no. inventaris…" value="<?= e($search) ?>"/>
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
                <th>No. Inventaris</th>
                <th>Judul Buku</th>
                <th>Pengarang</th>
                <th>Kategori/Bidang</th>
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
                <td style="font-size:0.8rem;color:var(--muted);"><?= e($b['no_inventaris'] ?? '—') ?></td>
                <td>
                  <div style="font-weight:500;"><?= e($b['judul']) ?></div>
                  <?php if ($b['isbn']): ?><div style="font-size:0.73rem;color:var(--muted);">ISBN: <?= e($b['isbn']) ?></div><?php endif; ?>
                  <?php if (!empty($b['kota_terbit']) || !empty($b['no_klas'])): ?>
                  <div style="font-size:0.73rem;color:var(--muted);">
                    <?= e($b['kota_terbit'] ?? '') ?><?= (!empty($b['kota_terbit']) && !empty($b['no_klas'])) ? ' · ' : '' ?><?= !empty($b['no_klas']) ? 'Klas: ' . e($b['no_klas']) : '' ?>
                  </div>
                  <?php endif; ?>
                </td>
                <td><?= e($b['penulis']) ?></td>
                <td><span class="badge badge-muted"><?= e($b['nama_kategori'] ?? $b['bidang'] ?? '—') ?></span></td>
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
              <tr><td colspan="9">
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

<!-- Modal Import Excel -->
<div class="modal-overlay" id="modal-import">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Import Data Buku dari Excel</div>
      <button class="modal-close" data-modal-close="modal-import">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="aksi" value="import"/>
      <div class="modal-body">
        <p style="font-size:0.85rem; color:var(--muted); margin-bottom:1rem; line-height:1.5;">
          Unggah file <strong>.xlsx</strong> (format Buku Induk) atau <strong>.csv</strong>. Baris pertama harus berupa header kolom:
          <code>NO, INVENTARIS, JUDUL BUKU, JILID/EDISI, PENGARANG, THN, KOTA TERBIT, PENERBIT, TANGGAL MASUK, NO. KLAS, JMLH TOTAL, TGL CEK, DESKRIPSI, BIDANG</code>.
          Belum punya filenya? Klik <strong>Unduh Template</strong> dulu agar formatnya pas.
        </p>
        <div class="form-group">
          <label>Pilih File (.xlsx atau .csv) *</label>
          <input type="file" name="file_import" accept=".xlsx,.csv" required/>
        </div>
        <div style="background:#FDF6E8; border:1px solid rgba(196,154,60,0.25); border-radius:10px; padding:0.75rem 1rem; font-size:0.8rem; color:var(--brown); margin-top:0.5rem;">
          <strong>Catatan:</strong> Kolom "BIDANG" diisi dengan <em>nama</em> kategori (contoh: Fiksi). Jika nama kategori tidak ditemukan di sistem, buku tetap akan ditambahkan tanpa kategori. Kolom "NO" tidak perlu cocok dengan urutan asli, sistem akan mengabaikannya.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close="modal-import">Batal</button>
        <button type="submit" class="btn btn-primary">Import Sekarang</button>
      </div>
    </form>
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
        <?php include __DIR__ . '/_form_buku.php'; ?>
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
        <?php include __DIR__ . '/_form_buku.php'; ?>
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
  ['no_inventaris','judul','jilid_edisi','penulis','tahun_terbit','kota_terbit','penerbit','tanggal_masuk','isbn','no_klas','stok','tgl_cek','deskripsi'].forEach(k => {
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