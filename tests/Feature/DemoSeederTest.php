<?php

namespace Tests\Feature;

use App\Models\{Batch, OrderAdjustment, Shipment, User};
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_is_coherent(): void
    {
        putenv('OWNER_EMAIL=owner.seed@test.local');
        putenv('OWNER_PASSWORD=OwnerSeedPassword123!');
        putenv('OWNER_NAME=Owner Seed');
        putenv('SEED_DEMO_DATA=true');
        putenv('DEMO_PASSWORD=DemoGBUKR2026!');

        $_ENV['OWNER_EMAIL'] = 'owner.seed@test.local';
        $_ENV['OWNER_PASSWORD'] = 'OwnerSeedPassword123!';
        $_ENV['OWNER_NAME'] = 'Owner Seed';
        $_ENV['SEED_DEMO_DATA'] = 'true';
        $_ENV['DEMO_PASSWORD'] = 'DemoGBUKR2026!';

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin.demo@example.com', 'role' => 'customer', 'admin_enabled' => 1]);
        $this->assertDatabaseHas('users', ['email' => 'demo.naya@example.com', 'role' => 'customer']);
        $this->assertDatabaseHas('products', ['name' => 'CORTIS 2nd EP Album [GREENGREEN]', 'type' => 'po']);
        $this->assertDatabaseHas('product_variants', ['source_label' => 'Korean Web', 'name' => 'Photobook Ver', 'dp_amount_idr' => 250000]);
        $this->assertDatabaseHas('shipping_options', ['label' => 'Free Shipping', 'amount_foreign' => 0]);
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'INV-DEMO-B-002', 'status' => 'overdue']);
        $this->assertDatabaseHas('payments', ['payment_number' => 'PAY-DEMO-PENDING-001', 'status' => 'pending']);
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'INV-DEMO-KRG-001', 'type' => 'kekurangan', 'amount' => 50000]);
        $this->assertTrue(OrderAdjustment::where('legacy_key', 'DEMO-KRG-001')->exists());

        foreach (Batch::with('warehouse')->get() as $batch) {
            if ($batch->warehouse) {
                $this->assertSame($batch->country_id, $batch->warehouse->country_id, 'Warehouse demo harus sesuai negara Batch.');
            }

            $this->assertTrue(
                Shipment::where('source_type', 'batch')->where('source_id', $batch->id)->exists(),
                'Setiap Batch demo harus memiliki tracking publik yang tersinkron.'
            );
        }

        $this->assertTrue(User::where('role', 'owner')->where('active', true)->exists());
    }
}
