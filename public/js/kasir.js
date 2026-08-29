// Logika kasir: keranjang disimpan di memori (state JS), hanya dikirim ke
// server sekali saat tombol "Bayar" ditekan (form biasa, tanpa fetch),
// supaya tetap ringan & tetap jalan walau browser HP jadul sekalipun.
(function () {
  const cartEl = document.getElementById('cart-items');
  const cartEmptyEl = document.getElementById('cart-empty');
  const totalEl = document.getElementById('cart-total');
  const kembalianEl = document.getElementById('cart-kembalian');
  const fab = document.getElementById('cart-fab');
  const fabCount = document.getElementById('cart-fab-count');
  const itemsJsonInput = document.getElementById('items_json');
  const metodeInput = document.getElementById('metode_input');
  const bayarInput = document.getElementById('bayar_input');
  const bayarWrap = document.getElementById('bayar-wrap');
  const btnBayar = document.getElementById('btn-bayar');
  const errorBox = document.getElementById('form-error');
  const stokEl = document.getElementById('stok-sisa');

  let cart = [];
  let stokRoti = window.STOK_ROTI || 0;

  function totalQtyCart() {
    return cart.reduce((s, it) => s + it.qty, 0);
  }
  function totalCart() {
    return cart.reduce((s, it) => s + it.harga * it.qty, 0);
  }
  function sisaStok() {
    return stokRoti - totalQtyCart();
  }

  function updateStokBanner() {
    if (!stokEl) return;
    const sisa = sisaStok();
    stokEl.textContent = sisa + ' tersisa (dari ' + stokRoti + ')';
    const banner = document.getElementById('stok-banner');
    banner.classList.remove('warn', 'danger');
    if (sisa <= 0) banner.classList.add('danger');
    else if (sisa <= 5) banner.classList.add('warn');

    document.querySelectorAll('.menu-card').forEach((card) => {
      card.classList.toggle('habis', sisa <= 0);
    });
  }

  function refreshQris() {
    const wrap = document.getElementById('qris-wrap');
    const img = document.getElementById('qris-img');
    if (!wrap || !img) return;
    if (metodeInput.value === 'qris' && totalCart() > 0) {
      wrap.style.display = 'block';
      img.src = '/qris/gambar?nominal=' + totalCart() + '&t=' + Date.now();
    } else {
      wrap.style.display = 'none';
    }
  }

  function syncItemsJson() {
    itemsJsonInput.value = JSON.stringify(
      cart.map((it) => ({
        produkId: it.produkId,
        nama: it.nama,
        harga: it.harga,
        qty: it.qty,
        catatan: it.catatan || '',
      }))
    );
  }

  function esc(s) {
    return String(s || '').replace(/"/g, '&quot;').replace(/</g, '&lt;');
  }

  function render() {
    cartEl.innerHTML = '';
    if (cart.length === 0) {
      cartEmptyEl.style.display = 'block';
    } else {
      cartEmptyEl.style.display = 'none';
      cart.forEach((it) => {
        const row = document.createElement('div');
        row.className = 'cart-item';
        row.innerHTML = `
          <div class="cart-item-top">
            <div class="info">
              <div class="nama">${it.nama}</div>
              <div class="harga">${formatRupiah(it.harga)}</div>
            </div>
            <div class="qty-ctrl">
              <button type="button" data-act="min" data-id="${it.produkId}">-</button>
              <span class="qty">${it.qty}</span>
              <button type="button" data-act="plus" data-id="${it.produkId}">+</button>
              <span class="hapus-link" data-act="hapus" data-id="${it.produkId}">hapus</span>
            </div>
          </div>
          <input
            type="text"
            class="input catatan-input"
            data-catatan-id="${it.produkId}"
            placeholder="Catatan (mis. nama selai/topping yang dipakai)"
            value="${esc(it.catatan)}"
            maxlength="120"
          >`;
        cartEl.appendChild(row);
      });
    }

    totalEl.textContent = formatRupiah(totalCart());
    fabCount.textContent = totalQtyCart();
    fab.style.display = cart.length ? 'flex' : 'none';
    syncItemsJson();
    btnBayar.disabled = cart.length === 0;
    hitungKembalian();
    updateStokBanner();
    refreshQris();
  }

  function hitungKembalian() {
    const metode = metodeInput.value;
    const bayar = metode === 'qris' ? totalCart() : Number(bayarInput.value || 0);
    const kembalian = metode === 'qris' ? 0 : bayar - totalCart();
    kembalianEl.textContent = formatRupiah(kembalian > 0 ? kembalian : 0);
  }

  function bump(el) {
    el.classList.remove('bump');
    void el.offsetWidth;
    el.classList.add('bump');
  }

  document.querySelectorAll('.menu-card').forEach((card) => {
    card.addEventListener('click', () => {
      if (sisaStok() <= 0) return;
      const id = Number(card.dataset.id);
      const nama = card.dataset.nama;
      const harga = Number(card.dataset.harga);
      const existing = cart.find((it) => it.produkId === id);
      if (existing) existing.qty += 1;
      else cart.push({ produkId: id, nama, harga, qty: 1, catatan: '' });

      bump(card);
      fab.classList.remove('bounce');
      void fab.offsetWidth;
      fab.classList.add('bounce');
      errorBox.textContent = '';
      render();
    });
  });

  cartEl.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-act]');
    if (!btn) return;
    const id = Number(btn.dataset.id);
    const item = cart.find((it) => it.produkId === id);
    if (!item) return;

    if (btn.dataset.act === 'plus') {
      if (sisaStok() <= 0) { errorBox.textContent = 'Stok roti tawar tidak cukup.'; return; }
      item.qty += 1;
    } else if (btn.dataset.act === 'min') {
      item.qty -= 1;
      if (item.qty <= 0) cart = cart.filter((it) => it.produkId !== id);
    } else if (btn.dataset.act === 'hapus') {
      cart = cart.filter((it) => it.produkId !== id);
    }
    render();
  });

  // Input catatan per item: cukup update state + hidden JSON, TIDAK render
  // ulang seluruh daftar, supaya fokus/kursor ketik tidak hilang tiap huruf.
  cartEl.addEventListener('input', (e) => {
    const input = e.target.closest('[data-catatan-id]');
    if (!input) return;
    const id = Number(input.dataset.catatanId);
    const item = cart.find((it) => it.produkId === id);
    if (!item) return;
    item.catatan = input.value;
    syncItemsJson();
  });

  // Kategori filter (pill)
  document.querySelectorAll('.pill').forEach((pill) => {
    pill.addEventListener('click', () => {
      document.querySelectorAll('.pill').forEach((p) => p.classList.remove('active'));
      pill.classList.add('active');
      const kat = pill.dataset.kategori;
      document.querySelectorAll('.kategori-block').forEach((block) => {
        block.style.display = kat === 'Semua' || block.dataset.kategori === kat ? '' : 'none';
      });
    });
  });

  // Metode bayar toggle
  document.querySelectorAll('.metode-toggle button').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.metode-toggle button').forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      metodeInput.value = btn.dataset.metode;
      bayarWrap.style.display = btn.dataset.metode === 'tunai' ? 'block' : 'none';
      hitungKembalian();
      refreshQris();
    });
  });

  // Tombol uang cepat
  document.querySelectorAll('.chip[data-nominal]').forEach((chip) => {
    chip.addEventListener('click', () => {
      bayarInput.value = Number(bayarInput.value || 0) + Number(chip.dataset.nominal);
      hitungKembalian();
    });
  });
  document.getElementById('chip-pas')?.addEventListener('click', () => {
    bayarInput.value = totalCart();
    hitungKembalian();
  });
  document.getElementById('chip-reset')?.addEventListener('click', () => {
    bayarInput.value = '';
    hitungKembalian();
  });
  bayarInput?.addEventListener('input', hitungKembalian);

  // Buka/tutup bottom sheet keranjang
  fab.addEventListener('click', () => document.getElementById('sheet-cart').classList.add('open'));

  document.getElementById('kasir-form').addEventListener('submit', (e) => {
    if (cart.length === 0) {
      e.preventDefault();
      errorBox.textContent = 'Keranjang masih kosong.';
      return;
    }
    if (metodeInput.value === 'tunai' && Number(bayarInput.value || 0) < totalCart()) {
      e.preventDefault();
      errorBox.textContent = 'Uang bayar kurang dari total belanja.';
    }
  });

  render();
})();
