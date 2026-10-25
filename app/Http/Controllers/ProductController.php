<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($search) => $search
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('sku', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => new Product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);
            if ($product->stock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'product_name' => $product->name,
                    'movement_type' => 'awal',
                    'quantity_change' => $product->stock,
                    'stock_before' => 0,
                    'stock_after' => $product->stock,
                    'note' => 'Stok awal produk',
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): View
    {
        return view('products.show', [
            'product' => $product->load('category'),
            'stockMovements' => $product->stockMovements()->with('user')->latest()->limit(20)->get(),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        DB::transaction(function () use ($data, $product, $request) {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $stockBefore = $lockedProduct->stock;
            $lockedProduct->update($data);
            if ($stockBefore !== $lockedProduct->stock) {
                StockMovement::create([
                    'product_id' => $lockedProduct->id,
                    'user_id' => $request->user()->id,
                    'product_name' => $lockedProduct->name,
                    'movement_type' => 'penyesuaian',
                    'quantity_change' => $lockedProduct->stock - $stockBefore,
                    'stock_before' => $stockBefore,
                    'stock_after' => $lockedProduct->stock,
                    'note' => 'Penyesuaian stok melalui edit produk',
                ]);
            }
        });

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->saleItems()->exists()) {
            return back()->with('error', 'Produk memiliki riwayat penjualan dan tidak dapat dihapus.');
        }

        try {
            $product->delete();
        } catch (QueryException) {
            return back()->with('error', 'Produk tidak dapat dihapus karena masih digunakan.');
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'.($product ? ','.$product->id : '')],
            'name' => ['required', 'string', 'max:255'],
            'purchase_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'selling_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'unit' => ['required', 'string', 'max:30'],
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ], [
            'sku.unique' => 'Kode produk sudah digunakan.',
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
        ]);
    }
}
