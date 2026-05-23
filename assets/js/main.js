/* ============================================================
   assets/js/main.js
   Script global untuk Sistem Perpustakaan Digital
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ── Sidebar toggle (mobile) ─────────────────────────── */
  const sidebar    = document.getElementById('sidebar');
  const backdrop   = document.getElementById('sidebar-backdrop');
  const hamburger  = document.getElementById('hamburger');

  function openSidebar() {
    sidebar?.classList.add('open');
    backdrop?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar?.classList.remove('open');
    backdrop?.classList.remove('open');
    document.body.style.overflow = '';
  }

  hamburger?.addEventListener('click', openSidebar);
  backdrop?.addEventListener('click', closeSidebar);

  /* ── Modal ───────────────────────────────────────────── */
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.modalOpen);
      target?.classList.add('open');
    });
  });

  document.querySelectorAll('[data-modal-close], .modal-overlay').forEach(el => {
    el.addEventListener('click', function (e) {
      // Jika klik di overlay (bukan modal), tutup
      if (el.classList.contains('modal-overlay') && e.target !== el) return;
      const overlay = el.closest('.modal-overlay') || document.getElementById(el.dataset.modalClose);
      overlay?.classList.remove('open');
    });
  });

  // Tutup modal dengan Escape
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    }
  });

  /* ── Konfirmasi hapus ────────────────────────────────── */
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      const msg = this.dataset.confirm || 'Yakin ingin menghapus data ini?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  /* ── Flash message auto-hide ────────────────────────── */
  document.querySelectorAll('.flash').forEach(flash => {
    setTimeout(() => {
      flash.style.transition = 'opacity 0.5s';
      flash.style.opacity = '0';
      setTimeout(() => flash.remove(), 500);
    }, 3500);
  });

  /* ── Aktifkan nav item sesuai URL ───────────────────── */
  const currentPath = window.location.pathname;
  document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href') || '';
    if (href && currentPath.includes(href.split('/').pop().replace('.php', ''))) {
      item.classList.add('active');
    }
  });

  /* ── Live search tabel ──────────────────────────────── */
  const searchInput = document.getElementById('table-search');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    });
  }

  /* ── Toggle password visibility ─────────────────────── */
  document.querySelectorAll('[data-toggle-pass]').forEach(btn => {
    btn.addEventListener('click', function () {
      const target = document.getElementById(this.dataset.togglePass);
      if (!target) return;
      const isPass = target.type === 'password';
      target.type = isPass ? 'text' : 'password';
      this.setAttribute('title', isPass ? 'Sembunyikan password' : 'Tampilkan password');
    });
  });

  /* ── Preview gambar upload ──────────────────────────── */
  document.querySelectorAll('[data-preview]').forEach(input => {
    input.addEventListener('change', function () {
      const preview = document.getElementById(this.dataset.preview);
      if (!preview || !this.files[0]) return;
      preview.src = URL.createObjectURL(this.files[0]);
    });
  });

});

/* ── Helper: Format angka ke Rupiah ─────────────────────── */
function rupiah(angka) {
  return 'Rp ' + Number(angka).toLocaleString('id-ID');
}

/* ── Helper: Confirm modal ──────────────────────────────── */
function confirmDelete(formId) {
  if (confirm('Yakin ingin menghapus data ini? Tindakan tidak dapat dibatalkan.')) {
    document.getElementById(formId)?.submit();
  }
}