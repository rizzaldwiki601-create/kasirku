<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Struk {{ $sale->invoice_number }}</title><link rel="stylesheet" href="{{ asset('css/kasirku.css') }}"></head>
<body class="receipt-page">
    <div class="receipt-actions"><a class="button button-secondary" href="{{ route('sales.show', $sale) }}">← Detail transaksi</a><button class="button button-primary" type="button" onclick="window.print()">Cetak struk</button></div>
    <main class="receipt">
        <header class="receipt-header"><strong>KasirKu</strong><span>Bukti transaksi penjualan</span><span>{{ $sale->invoice_number }}</span><span>{{ $sale->sold_at->format('d/m/Y H:i:s') }}</span><span>Kasir: {{ $sale->user?->name ?? 'Pengguna dihapus' }}</span></header>
        <div class="receipt-divider"></div>
        @foreach($sale->items as $item)<div class="receipt-item"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span>Rp {{ number_format((float) $item->subtotal, 2, ',', '.') }}</span><small>Rp {{ number_format((float) $item->unit_price, 2, ',', '.') }} / item</small></div>@endforeach
        <div class="receipt-divider"></div>
        <div class="receipt-row"><strong>Total</strong><strong>Rp {{ number_format((float) $sale->total, 2, ',', '.') }}</strong></div>
        <div class="receipt-row"><span>Dibayar ({{ strtoupper($sale->payment_method) }})</span><span>Rp {{ number_format((float) $sale->amount_paid, 2, ',', '.') }}</span></div>
        <div class="receipt-row"><span>Kembalian</span><span>Rp {{ number_format((float) $sale->change_amount, 2, ',', '.') }}</span></div>
        <p class="receipt-thanks">Terima kasih telah berbelanja.</p>
    </main>
</body>
</html>
