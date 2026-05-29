<?php // admin/_form_buku.php — partial form, di-include dari buku.php ?>
<div class="form-grid">
  <div class="form-group form-span-2">
    <label>Judul Buku *</label>
    <input type="text" name="judul" placeholder="Judul lengkap buku" required/>
  </div>
  <div class="form-group">
    <label>Penulis *</label>
    <input type="text" name="penulis" placeholder="Nama penulis" required/>
  </div>
  <div class="form-group">
    <label>Penerbit</label>
    <input type="text" name="penerbit" placeholder="Nama penerbit"/>
  </div>
  <div class="form-group">
    <label>Tahun Terbit</label>
    <input type="number" name="tahun_terbit" placeholder="Contoh: 2023" min="1900" max="<?= date('Y') ?>"/>
  </div>
  <div class="form-group">
    <label>ISBN</label>
    <input type="text" name="isbn" placeholder="978-xxx-xxx-xxx"/>
  </div>
  <div class="form-group">
    <label>Kategori</label>
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
  <div class="form-group form-span-2">
    <label>Deskripsi</label>
    <textarea name="deskripsi" placeholder="Ringkasan atau sinopsis buku…"></textarea>
  </div>
</div>