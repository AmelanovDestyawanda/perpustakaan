<?php // admin/_form_buku.php — partial form, di-include dari buku.php ?>
<div class="form-grid">
  <div class="form-group">
    <label>No. Inventaris</label>
    <input type="text" name="no_inventaris" placeholder="Contoh: 13802/SMKGRISA/SB/2025"/>
  </div>
  <div class="form-group">
    <label>No. Klasifikasi</label>
    <input type="text" name="no_klas" placeholder="Contoh: 793.319"/>
  </div>
  <div class="form-group form-span-2">
    <label>Judul Buku *</label>
    <input type="text" name="judul" placeholder="Judul lengkap buku" required/>
  </div>
  <div class="form-group">
    <label>Jilid/Edisi</label>
    <input type="text" name="jilid_edisi" placeholder="Contoh: Edisi 2 / Jilid 1"/>
  </div>
  <div class="form-group">
    <label>Pengarang *</label>
    <input type="text" name="penulis" placeholder="Nama pengarang" required/>
  </div>
  <div class="form-group">
    <label>Tahun Terbit</label>
    <input type="number" name="tahun_terbit" placeholder="Contoh: 2023" min="1900" max="<?= date('Y') ?>"/>
  </div>
  <div class="form-group">
    <label>Kota Terbit</label>
    <input type="text" name="kota_terbit" placeholder="Contoh: Surakarta"/>
  </div>
  <div class="form-group">
    <label>Penerbit</label>
    <input type="text" name="penerbit" placeholder="Nama penerbit"/>
  </div>
  <div class="form-group">
    <label>Tanggal Masuk</label>
    <input type="date" name="tanggal_masuk"/>
  </div>
  <div class="form-group">
    <label>ISBN</label>
    <input type="text" name="isbn" placeholder="978-xxx-xxx-xxx"/>
  </div>
  <div class="form-group">
    <label>Bidang/Kategori</label>
    <select name="kategori_id">
      <option value="">— Pilih Kategori —</option>
      <?php foreach (($kategoris ?? []) as $k): ?>
      <option value="<?= $k['id'] ?>"><?= e($k['nama']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label>Jumlah Stok *</label>
    <input type="number" name="stok" placeholder="1" min="1" value="1" required/>
  </div>
  <div class="form-group">
    <label>Tanggal Cek Terakhir</label>
    <input type="date" name="tgl_cek"/>
  </div>
  <div class="form-group form-span-2">
    <label>Deskripsi</label>
    <textarea name="deskripsi" placeholder="Ringkasan, jumlah halaman, ukuran, ISBN dalam teks bebas, dll…"></textarea>
  </div>
</div>