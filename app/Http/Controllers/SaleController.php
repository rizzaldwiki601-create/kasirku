<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function pos(Request $request): View
    {
        $cart = $request->session()->get('cart', []);
        $products = Product::with('category')
            ->where('stock', '>', 0)
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($search) => $search
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('sku', 'like', '%'.$request->string('search').'%')))
            ->orderBy('name')
            ->limit(30)
            ->get();

        $cartProducts = Product::whereKey(array_keys($cart))->get()->keyBy('id');
        $cartItems = collect($cart)->map(function ($quantity, $id) use ($cartProducts) {
            $product = $cartProducts->get($id);

            return $product ? [
                'product' => $product,
                'quantity' => (int) $quantity,
                'subtotal' => (int) round((float) $product->selling_price * 100) * (int) $quantity,
            ] : null;
        })->filter();

        return view('sales.pos', compact('products', 'cartItems'));
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ]);
        $product = Product::findOrFail($data['product_id']);
        $cart = $request->session()->get('cart', []);
        $newQuantity = (int) ($cart[$product->id] ?? 0) + (int) $data['quantity'];

        if ($newQuantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah melebihi stok yang tersedia.',
            ]);
        }

        $cart[$product->id] = $newQuantity;
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function updateCart(Request $request, int $product): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ]);
        $item = Product::findOrFail($product);

        if ($data['quantity'] > $item->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah melebihi stok yang tersedia.',
            ]);
        }

        $cart = $request->session()->get('cart', []);
        abort_unless(array_key_exists($product, $cart), 404);
        $cart[$product] = (int) $data['quantity'];
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Jumlah produk diperbarui.');
    }

    public function removeFromCart(Request $request, int $product): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product]);
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'payment_method' => ['required', 'in:tunai,transfer,qris'],
        ], [
            'payment_method.in' => 'Metode pembayaran tidak valid.',
        ]);
        $cart = $request->session()->get('cart', []);

        if ($cart === []) {
            throw ValidationException::withMessages([
                'cart' => 'Keranjang masih kosong.',
            ]);
        }

        $paidCents = (int) round((float) $data['amount_paid'] * 100);
        $sale = DB::transaction(function () use ($cart, $paidCents, $data, $request) {
            $ids = array_map('intval', array_keys($cart));
            $products = Product::whereKey($ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($cart)) {
                throw ValidationException::withMessages([
                    'cart' => 'Salah satu produk di keranjang sudah tidak tersedia.',
                ]);
            }

            $totalCents = 0;
            $maximumTotalCents = 999_999_999_999;
            foreach ($cart as $id => $quantity) {
                $product = $products->get((int) $id);
                $quantity = (int) $quantity;

                if ($quantity < 1 || $quantity > $product->stock) {
                    throw ValidationException::withMessages([
                        'cart' => 'Stok '.$product->name.' tidak mencukupi. Periksa kembali keranjang.',
                    ]);
                }

                $unitPriceCents = (int) round((float) $product->selling_price * 100);
                if ($unitPriceCents > 0 && $quantity > intdiv($maximumTotalCents - $totalCents, $unitPriceCents)) {
                    throw ValidationException::withMessages([
                        'cart' => 'Total transaksi melebihi batas nominal yang dapat disimpan.',
                    ]);
                }

                $totalCents += $unitPriceCents * $quantity;
            }

            if ($paidCents < $totalCents) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Nominal pembayaran kurang dari total transaksi.',
                ]);
            }

            do {
                $invoice = 'INV-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(6));
            } while (Sale::where('invoice_number', $invoice)->exists());

            $newSale = Sale::create([
                'invoice_number' => $invoice,
                'user_id' => $request->user()->id,
                'sold_at' => now(),
                'total' => number_format($totalCents / 100, 2, '.', ''),
                'amount_paid' => number_format($paidCents / 100, 2, '.', ''),
                'change_amount' => number_format(($paidCents - $totalCents) / 100, 2, '.', ''),
                'payment_method' => $data['payment_method'],
            ]);

            foreach ($cart as $id => $quantity) {
                $product = $products->get((int) $id);
                $unitPrice = (int) round((float) $product->selling_price * 100);
                $stockBefore = $product->stock;
                $product->decrement('stock', (int) $quantity);
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'sale_id' => $newSale->id,
                    'product_name' => $product->name,
                    'movement_type' => 'penjualan',
                    'quantity_change' => -(int) $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - (int) $quantity,
                    'note' => 'Penjualan '.$newSale->invoice_number,
                ]);
                $newSale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => (int) $quantity,
                    'unit_price' => number_format($unitPrice / 100, 2, '.', ''),
                    'purchase_price' => $product->purchase_price,
                    'subtotal' => number_format(($unitPrice * (int) $quantity) / 100, 2, '.', ''),
                ]);
            }

            return $newSale;
        });

        $request->session()->forget('cart');

        return redirect()->route('sales.receipt', $sale)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $sales = $this->visibleSales($request->user())
            ->with('user')
            ->withCount('items')
            ->when($request->filled('search'), fn (Builder $query) => $query->where('invoice_number', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('start_date'), fn (Builder $query) => $query->whereDate('sold_at', '>=', $filters['start_date']))
            ->when($request->filled('end_date'), fn (Builder $query) => $query->whereDate('sold_at', '<=', $filters['end_date']))
            ->latest('sold_at')
            ->paginate(15)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorizeSale($request, $sale);

        return view('sales.show', ['sale' => $sale->load(['user', 'items'])]);
    }

    public function receipt(Request $request, Sale $sale): View
    {
        $this->authorizeSale($request, $sale);

        return view('sales.receipt', ['sale' => $sale->load(['user', 'items'])]);
    }

    private function visibleSales($user): Builder
    {
        return Sale::query()->when($user->role !== 'admin', fn (Builder $query) => $query->where('user_id', $user->id));
    }

    private function authorizeSale(Request $request, Sale $sale): void
    {
        abort_unless($request->user()->role === 'admin' || $sale->user_id === $request->user()->id, 403);
    }
}
