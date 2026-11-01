<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasirkuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_and_invalid_login_is_rejected(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'email' => 'unknown@example.test',
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_https_forwarded_by_render_proxy_is_used_for_asset_urls(): void
    {
        $this->get('/login', ['X-Forwarded-Proto' => 'https'])
            ->assertOk()
            ->assertSee('href="https://', false);
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.test']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_creation_command_refuses_non_production_environment(): void
    {
        $this->artisan('kasir:buat-admin')
            ->expectsOutput('Perintah ini hanya boleh dijalankan di environment production.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_creation_command_refuses_non_mysql_production_connection(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('kasir:buat-admin')
            ->expectsOutput('Perintah ini hanya boleh dijalankan dengan koneksi database MySQL.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_product_sku_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Minuman']);
        Product::create([
            'category_id' => $category->id,
            'sku' => 'MIN-001',
            'name' => 'Air Mineral',
            'purchase_price' => 2000,
            'selling_price' => 3000,
            'stock' => 10,
            'unit' => 'botol',
            'minimum_stock' => 2,
        ]);

        $this->actingAs($admin)->post(route('products.store'), [
            'category_id' => $category->id,
            'sku' => 'MIN-001',
            'name' => 'Teh',
            'purchase_price' => 1000,
            'selling_price' => 2500,
            'stock' => 5,
            'unit' => 'botol',
            'minimum_stock' => 1,
        ])->assertSessionHasErrors('sku');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_sale_saves_snapshots_and_decreases_stock(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct(stock: 5, sellingPrice: 3000);

        $response = $this->actingAs($cashier)
            ->withSession(['cart' => [$product->id => 2]])
            ->post(route('sales.checkout'), [
                'amount_paid' => '10000',
                'payment_method' => 'tunai',
            ])
            ->assertRedirect();

        $sale = Sale::with('items')->firstOrFail();
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame('6000.00', $sale->total);
        $this->assertSame('4000.00', $sale->change_amount);
        $this->assertSame('Produk Contoh', $sale->items->first()->product_name);
        $this->assertSame('3000.00', $sale->items->first()->unit_price);
        $response->assertSessionMissing('cart');
    }

    public function test_insufficient_payment_does_not_create_sale_or_change_stock(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct(stock: 3, sellingPrice: 4000);

        $this->actingAs($cashier)
            ->withSession(['cart' => [$product->id => 2]])
            ->post(route('sales.checkout'), [
                'amount_paid' => '7000',
                'payment_method' => 'tunai',
            ])
            ->assertSessionHasErrors('amount_paid');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_cart_cannot_sell_more_than_available_stock(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = $this->createProduct(stock: 2, sellingPrice: 4000);

        $this->actingAs($cashier)
            ->withSession(['cart' => [$product->id => 3]])
            ->post(route('sales.checkout'), [
                'amount_paid' => '12000',
                'payment_method' => 'tunai',
            ])
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_manual_stock_adjustment_is_recorded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct(stock: 3, sellingPrice: 4000);

        $this->actingAs($admin)->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'sku' => $product->sku,
            'name' => $product->name,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'stock' => 7,
            'unit' => $product->unit,
            'minimum_stock' => $product->minimum_stock,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'movement_type' => 'penyesuaian',
            'quantity_change' => 4,
            'stock_before' => 3,
            'stock_after' => 7,
        ]);
    }

    public function test_cashier_cannot_open_administrator_routes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->get(route('products.index'))
            ->assertForbidden();
    }

    public function test_administrator_pages_render_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Halaman uji']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'PAGE-001',
            'name' => 'Produk Halaman',
            'purchase_price' => 2000,
            'selling_price' => 3000,
            'stock' => 5,
            'unit' => 'pcs',
            'minimum_stock' => 1,
        ]);
        $sale = $this->createSale($admin, 'INV-PAGE', '2026-10-20 12:00:00');
        $sale->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 3000,
            'purchase_price' => 2000,
            'subtotal' => 3000,
        ]);

        $this->actingAs($admin);
        foreach ([
            route('dashboard'),
            route('categories.index'),
            route('categories.create'),
            route('products.index'),
            route('products.create'),
            route('products.show', $product),
            route('users.index'),
            route('users.create'),
            route('users.edit', $admin),
            route('reports.index'),
            route('sales.pos'),
            route('sales.index'),
            route('sales.show', $sale),
            route('sales.receipt', $sale),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_report_only_includes_sales_in_the_selected_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $inside = $this->createSale($admin, 'INV-INSIDE', '2026-10-12 12:00:00');
        $outside = $this->createSale($admin, 'INV-OUTSIDE', '2026-09-12 12:00:00');

        $response = $this->actingAs($admin)->get(route('reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertOk()
            ->assertSee($inside->invoice_number)
            ->assertDontSee($outside->invoice_number)
            ->assertViewHas('transactionCount', 1);
    }

    public function test_cashier_dashboard_only_shows_their_own_sales(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'kasir']);
        $adminSale = $this->createSale($admin, 'INV-ADMIN-PRIVATE', now()->toDateTimeString());
        $cashierSale = $this->createSale($cashier, 'INV-CASHIER-OWN', now()->toDateTimeString());

        $this->actingAs($cashier)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($cashierSale->invoice_number)
            ->assertDontSee($adminSale->invoice_number)
            ->assertViewHas('saleCount', 1)
            ->assertViewHas('todayRevenue', '10000.00');
    }

    public function test_admin_dashboard_lists_real_products_at_their_stock_threshold(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->createProduct(stock: 1, sellingPrice: 3000);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertViewHas('lowStockCount', 1)
            ->assertViewHas('lowStockProducts', fn ($products) => $products->contains('id', $product->id));
    }

    private function createProduct(int $stock, int $sellingPrice): Product
    {
        $category = Category::create(['name' => 'Contoh']);

        return Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Produk Contoh',
            'purchase_price' => 1500,
            'selling_price' => $sellingPrice,
            'stock' => $stock,
            'unit' => 'pcs',
            'minimum_stock' => 1,
        ]);
    }

    private function createSale(User $user, string $invoice, string $soldAt): Sale
    {
        return Sale::create([
            'invoice_number' => $invoice,
            'user_id' => $user->id,
            'sold_at' => $soldAt,
            'total' => 10000,
            'amount_paid' => 10000,
            'change_amount' => 0,
            'payment_method' => 'tunai',
        ]);
    }
}
