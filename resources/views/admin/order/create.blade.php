@extends('layouts.admin')
@section('title', 'Input Penjualan')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/order.css') }}">
@endpush
@section('content')

<div class="flex-between mb-3">
    <div>
        <div style="font-size:1.25rem;font-weight:800;color:var(--gray-800);">Input <span style="color:var(--primary);">Penjualan</span></div>
        <div style="font-size:0.8rem;color:var(--gray-500);">Pilih obat dari katalog dan tentukan jumlahnya</div>
    </div>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<form action="{{ route('admin.orders.store') }}" method="POST" id="form-penjualan">
@csrf

{{-- Hidden inputs will be populated by JS --}}
<div id="hidden-inputs"></div>

<div class="pos-layout">
    {{-- LEFT: Product Catalog --}}
    <div class="catalog-panel">
        <div class="catalog-header">
            <div style="font-weight:700;font-size:0.9rem;color:var(--gray-800);display:flex;align-items:center;gap:0.4rem;">
                <i class="fas fa-pills" style="color:var(--primary);"></i> Katalog Obat
            </div>
            <div class="catalog-search">
                <i class="fas fa-search"></i>
                <input type="text" id="search-obat" placeholder="Cari nama obat..." oninput="filterObat()">
            </div>
        </div>

        {{-- Category Filter --}}
        <div class="cat-filter" id="cat-filter">
            <button type="button" class="cat-btn active" onclick="filterByKat('', this)">Semua</button>
            @php $kats = $obats->pluck('kategori')->filter()->unique('id'); @endphp
            @foreach($kats as $kat)
                <button type="button" class="cat-btn" onclick="filterByKat('{{ $kat->id }}', this)">{{ $kat->nama_kategori }}</button>
            @endforeach
        </div>

        <div class="obat-grid" id="obat-grid">
            @foreach($obats as $obat)
            <div class="obat-card {{ $obat->stok == 0 ? 'out-of-stock' : '' }}"
                 id="card-{{ $obat->id }}"
                 data-id="{{ $obat->id }}"
                 data-name="{{ $obat->nama_obat }}"
                 data-harga="{{ $obat->harga_umum }}"
                 data-stok="{{ $obat->stok }}"
                 data-satuan="{{ $obat->satuan }}"
                 data-kat="{{ $obat->kategori_id }}"
                 onclick="{{ $obat->stok > 0 ? 'addToCart(this)' : '' }}"
                 title="{{ $obat->stok == 0 ? 'Stok habis' : 'Klik untuk tambah ke pesanan' }}">
                <div class="add-indicator" id="indicator-{{ $obat->id }}">✓</div>
                <div class="obat-card-name">{{ $obat->nama_obat }}</div>
                <div class="obat-card-cat">{{ $obat->kategori->nama_kategori ?? '-' }}</div>
                <div class="obat-card-footer">
                    <div class="obat-card-price">Rp {{ number_format($obat->harga_umum, 0, ',', '.') }}</div>
                    <div class="obat-card-stok {{ $obat->stok == 0 ? 'stok-zero' : ($obat->stok < 10 ? 'stok-low' : 'stok-ok') }}">
                        {{ $obat->stok }} {{ $obat->satuan }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div style="padding:0.75rem 1rem;border-top:1px solid var(--gray-100);font-size:0.75rem;color:var(--gray-400);">
            {{ $obats->count() }} obat tersedia
        </div>
    </div>

    {{-- RIGHT: Cart + Form --}}
    <div class="cart-panel">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-cart" style="color:var(--primary);"></i> Keranjang Penjualan</h3>
        </div>

        <div id="cart-empty" class="cart-empty">
            <i class="fas fa-shopping-basket"></i>
            <div style="font-size:0.85rem;font-weight:500;">Keranjang kosong</div>
            <div style="font-size:0.78rem;margin-top:0.25rem;">Klik obat di katalog untuk menambahkan</div>
        </div>

        <div id="cart-items"></div>

        <div class="cart-footer">
            <div class="total-row">
                <div class="total-label">Total Pembayaran</div>
                <div class="total-amount" id="total-display">Rp 0</div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label" style="font-size:0.8rem;">Metode Pembayaran</label>
                <select name="metode_pembayaran" class="form-control" required>
                    <option value="tunai">💵 Tunai / Cash</option>
                    <option value="transfer_bank">🏦 Transfer Bank</option>
                    <option value="qris">📱 QRIS / E-Wallet</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" id="submit-btn" style="width:100%;justify-content:center;padding:0.75rem;" disabled>
                <i class="fas fa-save"></i> Simpan Transaksi
            </button>
        </div>
    </div>
</div>
</form>

@push('scripts')
<script>
const cart = {}; // { id: { name, harga, qty, stok, satuan } }

function addToCart(card) {
    const id = card.dataset.id;
    const name = card.dataset.name;
    const harga = parseInt(card.dataset.harga);
    const stok = parseInt(card.dataset.stok);
    const satuan = card.dataset.satuan;

    if (cart[id]) {
        if (cart[id].qty >= stok) {
            alert('Stok tidak mencukupi! Maksimal ' + stok + ' ' + satuan);
            return;
        }
        cart[id].qty++;
    } else {
        cart[id] = { name, harga, qty: 1, stok, satuan };
    }

    document.getElementById('indicator-' + id).style.display = 'flex';
    renderCart();
}

function renderCart() {
    const cartItems = document.getElementById('cart-items');
    const cartEmpty = document.getElementById('cart-empty');
    const hiddenInputs = document.getElementById('hidden-inputs');
    const submitBtn = document.getElementById('submit-btn');

    const keys = Object.keys(cart);
    cartEmpty.style.display = keys.length === 0 ? 'block' : 'none';
    submitBtn.disabled = keys.length === 0;

    cartItems.innerHTML = '';
    hiddenInputs.innerHTML = '';
    let total = 0;

    keys.forEach(id => {
        const item = cart[id];
        const subtotal = item.harga * item.qty;
        total += subtotal;

        hiddenInputs.innerHTML += `<input type="hidden" name="obat_id[]" value="${id}">`;
        hiddenInputs.innerHTML += `<input type="hidden" name="jumlah[]" value="${item.qty}">`;

        cartItems.innerHTML += `
        <div class="cart-item" id="cart-item-${id}">
            <div class="cart-item-info">
                <div class="cart-item-name" title="${item.name}">${item.name}</div>
                <div class="cart-item-price">Rp ${item.harga.toLocaleString('id-ID')} × ${item.qty} = <strong>Rp ${subtotal.toLocaleString('id-ID')}</strong></div>
            </div>
            <div class="qty-ctrl">
                <button type="button" class="qty-btn" onclick="changeQty('${id}', -1)"><i class="fas fa-minus"></i></button>
                <input type="text" class="qty-input-small" value="${item.qty}" onchange="setQty('${id}', this.value)" id="qty-${id}">
                <button type="button" class="qty-btn" onclick="changeQty('${id}', 1)"><i class="fas fa-plus"></i></button>
            </div>
            <button type="button" class="remove-btn" onclick="removeFromCart('${id}')"><i class="fas fa-trash"></i></button>
        </div>`;
    });

    document.getElementById('total-display').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

function changeQty(id, delta) {
    if (!cart[id]) return;
    const newQty = cart[id].qty + delta;
    if (newQty <= 0) { removeFromCart(id); return; }
    if (newQty > cart[id].stok) { alert('Stok tidak mencukupi!'); return; }
    cart[id].qty = newQty;
    renderCart();
}

function setQty(id, val) {
    const qty = parseInt(val);
    if (isNaN(qty) || qty <= 0) { removeFromCart(id); return; }
    if (qty > cart[id].stok) { alert('Stok tidak mencukupi! Max: ' + cart[id].stok); return; }
    cart[id].qty = qty;
    renderCart();
}

function removeFromCart(id) {
    delete cart[id];
    const indicator = document.getElementById('indicator-' + id);
    if (indicator) indicator.style.display = 'none';
    renderCart();
}

function filterObat() {
    const q = document.getElementById('search-obat').value.toLowerCase();
    document.querySelectorAll('#obat-grid .obat-card').forEach(card => {
        const name = card.dataset.name.toLowerCase();
        card.style.display = name.includes(q) ? '' : 'none';
    });
}

function filterByKat(katId, btn) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#obat-grid .obat-card').forEach(card => {
        card.style.display = (!katId || card.dataset.kat == katId) ? '' : 'none';
    });
    document.getElementById('search-obat').value = '';
}
</script>
@endpush
@endsection
