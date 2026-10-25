@extends('layouts.app')
@section('title', 'Kasir')
@section('heading', 'Kasir')
@section('content')
    <div class="page-heading"><div><h1>Kasir</h1><p>Cari produk, atur jumlah, lalu selesaikan pembayaran.</p></div></div>
    <div class="pos-grid">
        <section class="panel product-picker">
            <div class="panel-heading"><div><h2>Pilih produk</h2><p>Stok diperiksa ulang saat transaksi disimpan.</p></div></div>
            <form class="toolbar pos-search" method="GET"><label class="search-field"><span>⌕</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Nama produk atau SKU"></label><button class="button button-secondary" type="submit">Cari</button></form>
            @if($products->isEmpty())<div class="empty-state"><strong>Produk tidak ditemukan</strong><p>Pastikan produk sudah dibuat dan stoknya tersedia.</p></div>
            @else
                <div class="product-pick-list">
                    @foreach($products as $product)
                        <article class="product-pick">
                            <div class="product-pick-info"><strong>{{ $product->name }}</strong><small>{{ $product->sku }} · {{ $product->category->name }}</small><span>Rp {{ number_format((float) $product->selling_price, 2, ',', '.') }} <small>· Sisa {{ $product->stock }} {{ $product->unit }}</small></span></div>
                            <form method="POST" action="{{ route('sales.cart.add') }}" class="add-product-form">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><label class="visually-hidden" for="quantity-{{ $product->id }}">Jumlah {{ $product->name }}</label><input id="quantity-{{ $product->id }}" type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}" aria-label="Jumlah"><button class="button button-primary button-small" type="submit">Tambah</button></form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
        <section class="panel cart-panel">
            <div class="panel-heading"><div><h2>Keranjang</h2><p>{{ $cartItems->sum('quantity') }} barang dipilih</p></div><span class="cart-count">{{ $cartItems->count() }}</span></div>
            @if($cartItems->isEmpty())
                <div class="empty-state cart-empty"><span class="empty-icon">▣</span><strong>Keranjang masih kosong</strong><p>Pilih produk di samping untuk memulai.</p></div>
            @else
                <div class="cart-items">
                    @foreach($cartItems as $id => $item)
                        <article class="cart-item">
                            <div class="cart-item-main"><strong>{{ $item['product']->name }}</strong><small>Rp {{ number_format((float) $item['product']->selling_price, 2, ',', '.') }} / {{ $item['product']->unit }}</small></div>
                            <div class="cart-item-controls"><form method="POST" action="{{ route('sales.cart.update', $id) }}">@csrf @method('PATCH')<label class="visually-hidden" for="cart-qty-{{ $id }}">Jumlah {{ $item['product']->name }}</label><input id="cart-qty-{{ $id }}" type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="{{ $item['product']->stock }}"><button class="button button-secondary button-small" type="submit">Ubah</button></form><form method="POST" action="{{ route('sales.cart.remove', $id) }}">@csrf @method('DELETE')<button class="button button-quiet button-small" type="submit" aria-label="Hapus {{ $item['product']->name }}">Hapus</button></form></div>
                            <strong class="cart-line-total">Rp {{ number_format($item['subtotal'] / 100, 2, ',', '.') }}</strong>
                        </article>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('sales.checkout') }}" class="checkout-form" id="checkout-form">
                    @csrf
                    <div class="total-row"><span>Total pembayaran</span><strong id="sale-total" data-total="{{ $cartItems->sum('subtotal') }}">Rp {{ number_format($cartItems->sum('subtotal') / 100, 2, ',', '.') }}</strong></div>
                    <label>Nominal dibayar (Rp)<input id="amount-paid" type="number" name="amount_paid" min="0" step="0.01" value="{{ old('amount_paid') }}" required>@error('amount_paid')<span class="field-error">{{ $message }}</span>@enderror</label>
                    <label>Metode pembayaran<select name="payment_method" required><option value="tunai" @selected(old('payment_method') === 'tunai')>Tunai</option><option value="transfer" @selected(old('payment_method') === 'transfer')>Transfer</option><option value="qris" @selected(old('payment_method') === 'qris')>QRIS</option></select></label>
                    <div class="change-preview"><span>Perkiraan kembalian</span><strong id="change-preview">Rp 0,00</strong></div>
                    <button class="button button-primary button-full" type="submit">Simpan transaksi</button>
                </form>
            @endif
        </section>
    </div>
@endsection
@push('scripts')
<script>
    const paidInput = document.getElementById('amount-paid');
    const totalElement = document.getElementById('sale-total');
    const changeElement = document.getElementById('change-preview');
    if (paidInput && totalElement && changeElement) {
        const formatRupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value / 100);
        const updateChange = () => {
            const paid = Math.round((Number(paidInput.value) || 0) * 100);
            changeElement.textContent = formatRupiah(Math.max(0, paid - Number(totalElement.dataset.total)));
        };
        paidInput.addEventListener('input', updateChange);
        updateChange();
    }
</script>
@endpush
