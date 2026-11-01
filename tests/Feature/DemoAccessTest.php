<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_can_only_view_the_allowed_pages_and_own_sales(): void
    {
        $demo = User::factory()->create(['role' => 'demo']);
        $cashier = User::factory()->create(['role' => 'kasir']);
        $category = Category::create(['name' => 'Demo Category']);
        $product = $this->createProduct($category);
        $demoSale = $this->createSale($demo, $product, 'INV-DEMO-OWN');
        $privateSale = $this->createSale($cashier, $product, 'INV-PRIVATE');

        $this->actingAs($demo);
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($demoSale->invoice_number)
            ->assertDontSee($privateSale->invoice_number)
            ->assertViewHas('saleCount', 1);
        foreach ([
            route('categories.index'),
            route('products.index'),
            route('products.show', $product),
            route('reports.index'),
            route('sales.index'),
            route('sales.show', $demoSale),
            route('sales.receipt', $demoSale),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('sales.index'))
            ->assertSee($demoSale->invoice_number)
            ->assertDontSee($privateSale->invoice_number);
        $this->get(route('reports.index'))
            ->assertSee($demoSale->invoice_number)
            ->assertDontSee($privateSale->invoice_number)
            ->assertViewHas('transactionCount', 1);
        $this->get(route('sales.show', $privateSale))->assertForbidden();
        $this->get(route('sales.receipt', $privateSale))->assertForbidden();

        foreach ([
            route('sales.pos'),
            route('categories.create'),
            route('categories.edit', $category),
            route('products.create'),
            route('products.edit', $product),
            route('users.index'),
            route('users.create'),
            route('users.edit', $demo),
        ] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_demo_cannot_mutate_business_data_through_http_endpoints(): void
    {
        $demo = User::factory()->create(['role' => 'demo']);
        $category = Category::create(['name' => 'Existing Category']);
        $product = $this->createProduct($category);
        $sale = $this->createSale($demo, $product, 'INV-DEMO-LOCKED');
        $initialProductStock = $product->stock;
        $initialUserCount = User::count();

        $this->actingAs($demo)->withSession(['cart' => [$product->id => 1]]);
        $responses = [
            $this->post(route('categories.store'), ['name' => 'Unauthorized Category']),
            $this->put(route('categories.update', $category), ['name' => 'Changed Category']),
            $this->patch(route('categories.update', $category), ['name' => 'Changed Category']),
            $this->delete(route('categories.destroy', $category)),
            $this->post(route('products.store'), $this->productData($category)),
            $this->put(route('products.update', $product), $this->productData($category, ['stock' => 99])),
            $this->patch(route('products.update', $product), $this->productData($category, ['stock' => 99])),
            $this->delete(route('products.destroy', $product)),
            $this->post(route('users.store'), [
                'name' => 'Unauthorized User',
                'email' => 'unauthorized@example.test',
                'password' => 'test-password',
                'password_confirmation' => 'test-password',
                'role' => 'admin',
            ]),
            $this->put(route('users.update', $demo), [
                'name' => 'Changed Demo',
                'email' => $demo->email,
                'role' => 'admin',
            ]),
            $this->patch(route('users.update', $demo), [
                'name' => 'Changed Demo',
                'email' => $demo->email,
                'role' => 'admin',
            ]),
            $this->delete(route('users.destroy', $demo)),
            $this->post(route('sales.cart.add'), ['product_id' => $product->id, 'quantity' => 1]),
            $this->patch(route('sales.cart.update', $product), ['quantity' => 1]),
            $this->delete(route('sales.cart.remove', $product)),
            $this->post(route('sales.checkout'), [
                'amount_paid' => '10000',
                'payment_method' => 'tunai',
            ]),
            $this->put('/storage/demo-upload.txt', ['content' => 'not-authorized']),
        ];

        foreach ($responses as $response) {
            $response->assertForbidden();
        }

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame($initialProductStock, $product->fresh()->stock);
        $this->assertSame($initialUserCount, User::count());
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'invoice_number' => 'INV-DEMO-LOCKED']);
    }

    public function test_admin_retains_access_to_management_and_register(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Admin Category']);
        $product = $this->createProduct($category);

        $this->actingAs($admin);
        foreach ([
            route('dashboard'),
            route('categories.index'),
            route('categories.create'),
            route('categories.edit', $category),
            route('products.index'),
            route('products.create'),
            route('products.edit', $product),
            route('users.index'),
            route('users.create'),
            route('users.edit', $admin),
            route('reports.index'),
            route('sales.pos'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->post(route('categories.store'), ['name' => 'Admin Created Category'])
            ->assertRedirect(route('categories.index'));
        $this->put(route('products.update', $product), $this->productData($category, ['stock' => 9]))
            ->assertRedirect(route('products.index'));
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'penyesuaian',
            'stock_after' => 9,
        ]);
    }

    public function test_cashier_can_operate_the_register_but_not_admin_pages(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $category = Category::create(['name' => 'Cashier Category']);
        $product = $this->createProduct($category);

        $this->actingAs($cashier)
            ->get(route('sales.pos'))
            ->assertOk();
        $this->post(route('sales.cart.add'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect();
        $this->patch(route('sales.cart.update', $product), ['quantity' => 1])
            ->assertRedirect();
        $this->delete(route('sales.cart.remove', $product))
            ->assertRedirect();
        $this->get(route('users.index'))->assertForbidden();
        $this->post(route('products.store'), $this->productData($category))->assertForbidden();
    }

    public function test_demo_account_command_creates_once_and_never_rotates_existing_password(): void
    {
        $password = 'test-only-demo-password';
        config([
            'demo.login_enabled' => true,
            'demo.login_email' => 'demo@kasirku.test',
            'demo.login_password' => $password,
        ]);

        $this->artisan('kasir:buat-demo')
            ->expectsOutput('Akun demo berhasil dibuat.')
            ->assertExitCode(0);

        $demo = User::where('email', 'demo@kasirku.test')->firstOrFail();
        $storedHash = $demo->password;
        $this->assertSame('demo', $demo->role);
        $this->assertTrue(Hash::check($password, $storedHash));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee($demo->email)
            ->assertSee($password)
            ->assertSee('Salin Email')
            ->assertSee('Salin Password')
            ->assertDontSee('Masuk sebagai Demo')
            ->assertDontSee('demo-login-form');
        $this->post(route('login.store'), [
            'email' => $demo->email,
            'password' => $password,
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($demo);

        $this->artisan('kasir:buat-demo')
            ->expectsOutput('Akun demo sudah tersedia dan kredensialnya sesuai.')
            ->assertExitCode(0);
        $this->assertSame($storedHash, $demo->fresh()->password);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_demo_account_command_refuses_existing_non_demo_accounts_or_password_changes(): void
    {
        config([
            'demo.login_enabled' => true,
            'demo.login_email' => 'demo@kasirku.test',
            'demo.login_password' => 'configured-test-password',
        ]);
        $admin = User::factory()->create([
            'email' => 'demo@kasirku.test',
            'role' => 'admin',
        ]);
        $adminHash = $admin->password;

        $this->artisan('kasir:buat-demo')->assertExitCode(1);
        $this->assertSame($adminHash, $admin->fresh()->password);
        $this->assertSame('admin', $admin->fresh()->role);

        User::query()->delete();
        $demo = User::factory()->create([
            'email' => 'demo@kasirku.test',
            'role' => 'demo',
            'password' => 'previous-test-password',
        ]);
        $demoHash = $demo->password;

        $this->artisan('kasir:buat-demo')->assertExitCode(1);
        $this->assertSame($demoHash, $demo->fresh()->password);
    }

    public function test_demo_login_is_hidden_and_account_creation_is_blocked_when_disabled(): void
    {
        config([
            'demo.login_enabled' => false,
            'demo.login_email' => 'demo@kasirku.test',
            'demo.login_password' => 'configured-test-password',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('AKSES DEMO KASIRKU')
            ->assertDontSee('Masuk sebagai Demo');
        $this->artisan('kasir:buat-demo')->assertExitCode(1);
        $this->assertDatabaseCount('users', 0);
    }

    private function createProduct(Category $category): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Demo Product',
            'purchase_price' => 1500,
            'selling_price' => 3000,
            'stock' => 5,
            'unit' => 'pcs',
            'minimum_stock' => 1,
        ]);
    }

    private function createSale(User $user, Product $product, string $invoice): Sale
    {
        $sale = Sale::create([
            'invoice_number' => $invoice,
            'user_id' => $user->id,
            'sold_at' => now(),
            'total' => 3000,
            'amount_paid' => 3000,
            'change_amount' => 0,
            'payment_method' => 'tunai',
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 3000,
            'purchase_price' => 1500,
            'subtotal' => 3000,
        ]);

        return $sale;
    }

    private function productData(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'sku' => 'NEW-'.uniqid(),
            'name' => 'New Product',
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'stock' => 2,
            'unit' => 'pcs',
            'minimum_stock' => 1,
        ], $overrides);
    }
}
