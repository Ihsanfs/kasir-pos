<?php

namespace Tests\Feature;

use App\Filament\Pages\Pos;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AiService;
use App\Services\PosService;
use Database\Seeders\PosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PosSeeder::class);
    }

    public function test_admin_pages_render_successfully(): void
    {
        $user = User::query()->where('email', 'admin@kasir.test')->firstOrFail();

        $pages = [
            '/admin/pos',
            '/admin/profit-loss-report',
            '/admin/ai-assistant',
            '/admin/settings',
            '/admin/transactions',
            '/admin/shifts',
            '/admin/products',
            '/admin/categories',
            '/admin/customers',
            '/admin/expenses',
            '/admin/branches',
            '/admin/users',
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($user)->get($page);
            $this->assertTrue($response->isSuccessful(), $page.' returned HTTP '.$response->status());
        }
    }

    public function test_pos_service_creates_transaction_and_reduces_stock(): void
    {
        $user = User::query()->where('email', 'admin@kasir.test')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        $shift = Shift::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'opened_at' => now(),
            'opening_cash' => 100000,
            'status' => 'open',
        ]);

        $food = Product::query()->where('sku', 'MKN-001')->firstOrFail();
        $drink = Product::query()->where('sku', 'MNM-004')->firstOrFail();
        $variant = $drink->variants()->firstOrFail();

        $foodStock = $food->stock;
        $variantStock = $variant->stock;

        $transaction = app(PosService::class)->checkout([
            'items' => [
                ['product_id' => $food->id, 'qty' => 2],
                ['product_id' => $drink->id, 'product_variant_id' => $variant->id, 'qty' => 1],
            ],
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'discount' => 1000,
            'paid' => 100000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]);

        $expectedSubtotal = (2 * (float) $food->sell_price) + (float) $variant->sell_price;
        $expectedTotal = $expectedSubtotal - 1000;

        $this->assertMatchesRegularExpression('/^INV-\d{8}-\d{4}$/', $transaction->invoice_number);
        $this->assertSame(2, $transaction->items()->count());
        $this->assertEquals($expectedSubtotal, $transaction->subtotal);
        $this->assertEquals($expectedTotal, $transaction->total);
        $this->assertEquals(100000 - $expectedTotal, $transaction->change);

        $this->assertEquals($foodStock - 2, $food->fresh()->stock);
        $this->assertEquals($variantStock - 1, $variant->fresh()->stock);

        $shift->refresh();
        $this->assertEquals($expectedTotal, $shift->total_sales);
        $this->assertEquals($expectedTotal, $shift->total_cash_sales);
        $this->assertSame(1, $shift->total_transactions);
    }

    public function test_pos_livewire_flow_checkout(): void
    {
        $user = User::query()->where('email', 'admin@kasir.test')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        Shift::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'opened_at' => now(),
            'opening_cash' => 0,
            'status' => 'open',
        ]);

        $product = Product::query()->where('sku', 'MNM-001')->firstOrFail();

        $component = Livewire::actingAs($user)->test(Pos::class);

        $component->call('addToCart', $product->id);
        $this->assertCount(1, $component->get('cart'));

        $component->call('setPaid', 20000);
        $component->call('checkout');

        $this->assertCount(0, $component->get('cart'));
        $this->assertDatabaseCount('transactions', 1);

        $transaction = Transaction::query()->firstOrFail();
        $this->assertEquals(5000, $transaction->total);
        $this->assertEquals('paid', $transaction->payment_status);

        $this->actingAs($user)
            ->get(route('pos.print', $transaction))
            ->assertSuccessful()
            ->assertSee($transaction->invoice_number);
    }

    public function test_pos_open_and_close_shift_actions(): void
    {
        $user = User::query()->where('email', 'admin@kasir.test')->firstOrFail();
        $branch = Branch::query()->firstOrFail();

        $component = Livewire::actingAs($user)->test(Pos::class);

        $component->assertActionVisible('openShift');
        $component->assertActionHidden('closeShift');

        $component->callAction('openShift', ['opening_cash' => 100000]);

        $this->assertDatabaseHas('shifts', [
            'user_id' => $user->id,
            'status' => 'open',
        ]);

        $shift = Shift::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->firstOrFail();

        $this->assertEquals(100000, $shift->opening_cash);

        // Header actions harus langsung berubah setelah computed property di-invalidate.
        $component->assertSee('Tutup Kasir');
        $component->assertDontSee('Buka Shift');
        $component->assertActionVisible('closeShift');

        $product = Product::query()->where('sku', 'MNM-001')->firstOrFail();

        $transaction = app(PosService::class)->checkout([
            'items' => [['product_id' => $product->id, 'qty' => 1]],
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'paid' => 10000,
            'payment_method' => 'cash',
        ]);

        $closingCash = 100000 + (float) $transaction->total;

        $component->callAction('closeShift', [
            'closing_cash' => $closingCash,
            'notes' => 'Tutup shift uji otomatis',
        ]);

        $shift->refresh();

        $this->assertSame('closed', $shift->status);
        $this->assertNotNull($shift->closed_at);
        $this->assertEquals($closingCash, $shift->closing_cash);
        $this->assertEquals($closingCash, $shift->expected_cash);
        $this->assertEquals(0, $shift->difference);
        $this->assertEquals($transaction->total, $shift->total_sales);
        $this->assertSame(1, $shift->total_transactions);

        $component->assertSee('Buka Shift');
        $component->assertDontSee('Tutup Kasir');
        $component->assertActionVisible('openShift');
    }

    public function test_ai_local_analysis_returns_insight(): void
    {
        $answer = app(AiService::class)->answer('Bagaimana ringkasan penjualan hari ini?');

        $this->assertIsString($answer);
        $this->assertNotSame('', trim($answer));
        $this->assertStringContainsString('Penjualan', $answer);
    }
}
