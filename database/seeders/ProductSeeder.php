<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing products
        Product::truncate();

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Get categories
        $electronics = Category::where('name', 'Electronics')->first();
        $mobilePhones = Category::where('name', 'Mobile Phones')->first();
        $laptops = Category::where('name', 'Laptops & Computers')->first();
        $clothing = Category::where('name', 'Clothing')->first();
        $mensClothing = Category::where('name', 'Men\'s Clothing')->first();
        $womensClothing = Category::where('name', 'Women\'s Clothing')->first();
        $food = Category::where('name', 'Food & Beverages')->first();
        $fruits = Category::where('name', 'Fresh Fruits')->first();
        $beverages = Category::where('name', 'Beverages')->first();
        $beauty = Category::where('name', 'Beauty & Personal Care')->first();
        $skincare = Category::where('name', 'Skincare')->first();

        // Get first supplier or create one
        $supplier = Supplier::first();
        if (!$supplier) {
            $supplier = Supplier::create([
                'name' => 'General Supplier',
                'email' => 'supplier@example.com',
                'contact' => '0771234567',
                'address' => 'Colombo, Sri Lanka',
            ]);
        }

        // Get default unit or create one
        $unit = Unit::first();
        if (!$unit) {
            $unit = Unit::create([
                'name' => 'Piece',
            ]);
        }

        // Electronics - Mobile Phones
        if ($mobilePhones) {
            Product::create([
                'category_id' => $mobilePhones->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'iPhone 15 Pro',
                'code' => 'IPH15PRO001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 450000.00,
                'selling_price' => 520000.00,
                'stock_quantity' => 15,
                'discount' => 0,
                'discounted_price' => 520000.00,
                'batch_no' => 'BATCH-001',
                'purchase_date' => now()->subDays(10),
                'expire_date' => null,
            ]);

            Product::create([
                'category_id' => $mobilePhones->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Samsung Galaxy S24',
                'code' => 'SAM-S24-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 380000.00,
                'selling_price' => 450000.00,
                'stock_quantity' => 20,
                'discount' => 5,
                'discounted_price' => 427500.00,
                'batch_no' => 'BATCH-002',
                'purchase_date' => now()->subDays(15),
                'expire_date' => null,
            ]);

            Product::create([
                'category_id' => $mobilePhones->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Google Pixel 8',
                'code' => 'GPX8-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 300000.00,
                'selling_price' => 360000.00,
                'stock_quantity' => 12,
                'discount' => 0,
                'discounted_price' => 360000.00,
                'batch_no' => 'BATCH-003',
                'purchase_date' => now()->subDays(5),
                'expire_date' => null,
            ]);
        }

        // Electronics - Laptops
        if ($laptops) {
            Product::create([
                'category_id' => $laptops->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Dell XPS 15',
                'code' => 'DELL-XPS15-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 550000.00,
                'selling_price' => 650000.00,
                'stock_quantity' => 8,
                'discount' => 0,
                'discounted_price' => 650000.00,
                'batch_no' => 'BATCH-004',
                'purchase_date' => now()->subDays(20),
                'expire_date' => null,
            ]);

            Product::create([
                'category_id' => $laptops->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'MacBook Pro M3',
                'code' => 'MBP-M3-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 750000.00,
                'selling_price' => 880000.00,
                'stock_quantity' => 5,
                'discount' => 0,
                'discounted_price' => 880000.00,
                'batch_no' => 'BATCH-005',
                'purchase_date' => now()->subDays(12),
                'expire_date' => null,
            ]);

            Product::create([
                'category_id' => $laptops->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'HP Pavilion 14',
                'code' => 'HP-PAV14-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 280000.00,
                'selling_price' => 350000.00,
                'stock_quantity' => 18,
                'discount' => 10,
                'discounted_price' => 315000.00,
                'batch_no' => 'BATCH-006',
                'purchase_date' => now()->subDays(8),
                'expire_date' => null,
            ]);
        }

        // Clothing - Men's
        if ($mensClothing) {
            Product::create([
                'category_id' => $mensClothing->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Men\'s Formal Shirt - Blue',
                'code' => 'MFS-BLUE-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 2500.00,
                'selling_price' => 3500.00,
                'stock_quantity' => 50,
                'discount' => 0,
                'discounted_price' => 3500.00,
                'batch_no' => 'BATCH-007',
                'purchase_date' => now()->subDays(30),
                'expire_date' => null,
            ]);

            Product::create([
                'category_id' => $mensClothing->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Men\'s Casual T-Shirt - Black',
                'code' => 'MCT-BLK-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 1200.00,
                'selling_price' => 2000.00,
                'stock_quantity' => 100,
                'discount' => 15,
                'discounted_price' => 1700.00,
                'batch_no' => 'BATCH-008',
                'purchase_date' => now()->subDays(25),
                'expire_date' => null,
            ]);

            Product::create([
                'category_id' => $mensClothing->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Men\'s Jeans - Dark Blue',
                'code' => 'MJ-DKBLUE-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 3500.00,
                'selling_price' => 5500.00,
                'stock_quantity' => 40,
                'discount' => 0,
                'discounted_price' => 5500.00,
                'batch_no' => 'BATCH-009',
                'purchase_date' => now()->subDays(18),
                'expire_date' => null,
            ]);
        }

        // Clothing - Women's
        if ($womensClothing) {
            Product::create([
                'category_id' => $womensClothing->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Women\'s Summer Dress',
                'code' => 'WSD-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 4000.00,
                'selling_price' => 6500.00,
                'stock_quantity' => 35,
                'discount' => 0,
                'discounted_price' => 6500.00,
                'batch_no' => 'BATCH-010',
                'purchase_date' => now()->subDays(22),
                'expire_date' => null,
            ]);

            Product::create([
                'category_id' => $womensClothing->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Women\'s Blouse - White',
                'code' => 'WB-WHT-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 2800.00,
                'selling_price' => 4200.00,
                'stock_quantity' => 45,
                'discount' => 10,
                'discounted_price' => 3780.00,
                'batch_no' => 'BATCH-011',
                'purchase_date' => now()->subDays(14),
                'expire_date' => null,
            ]);
        }

        // Food & Beverages - Fruits
        if ($fruits) {
            Product::create([
                'category_id' => $fruits->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Fresh Apples (per kg)',
                'code' => 'FRUIT-APL-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 400.00,
                'selling_price' => 600.00,
                'stock_quantity' => 150,
                'discount' => 0,
                'discounted_price' => 600.00,
                'batch_no' => 'BATCH-012',
                'purchase_date' => now()->subDays(2),
                'expire_date' => now()->addDays(10),
            ]);

            Product::create([
                'category_id' => $fruits->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Fresh Oranges (per kg)',
                'code' => 'FRUIT-ORG-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 350.00,
                'selling_price' => 550.00,
                'stock_quantity' => 200,
                'discount' => 5,
                'discounted_price' => 522.50,
                'batch_no' => 'BATCH-013',
                'purchase_date' => now()->subDays(1),
                'expire_date' => now()->addDays(8),
            ]);

            Product::create([
                'category_id' => $fruits->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Fresh Bananas (per dozen)',
                'code' => 'FRUIT-BAN-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 180.00,
                'selling_price' => 300.00,
                'stock_quantity' => 180,
                'discount' => 0,
                'discounted_price' => 300.00,
                'batch_no' => 'BATCH-014',
                'purchase_date' => now()->subDays(1),
                'expire_date' => now()->addDays(7),
            ]);
        }

        // Beverages
        if ($beverages) {
            Product::create([
                'category_id' => $beverages->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Coca Cola 1.5L',
                'code' => 'BEV-COKE-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 180.00,
                'selling_price' => 280.00,
                'stock_quantity' => 120,
                'discount' => 0,
                'discounted_price' => 280.00,
                'batch_no' => 'BATCH-015',
                'purchase_date' => now()->subDays(15),
                'expire_date' => now()->addMonths(6),
            ]);

            Product::create([
                'category_id' => $beverages->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Mineral Water 500ml',
                'code' => 'BEV-WATER-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 50.00,
                'selling_price' => 80.00,
                'stock_quantity' => 300,
                'discount' => 0,
                'discounted_price' => 80.00,
                'batch_no' => 'BATCH-016',
                'purchase_date' => now()->subDays(10),
                'expire_date' => now()->addYear(),
            ]);

            Product::create([
                'category_id' => $beverages->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Orange Juice 1L',
                'code' => 'BEV-OJ-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 220.00,
                'selling_price' => 350.00,
                'stock_quantity' => 80,
                'discount' => 10,
                'discounted_price' => 315.00,
                'batch_no' => 'BATCH-017',
                'purchase_date' => now()->subDays(5),
                'expire_date' => now()->addMonths(3),
            ]);
        }

        // Beauty & Personal Care - Skincare
        if ($skincare) {
            Product::create([
                'category_id' => $skincare->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Moisturizer Cream 50ml',
                'code' => 'SKIN-MOIST-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 1500.00,
                'selling_price' => 2500.00,
                'stock_quantity' => 60,
                'discount' => 0,
                'discounted_price' => 2500.00,
                'batch_no' => 'BATCH-018',
                'purchase_date' => now()->subDays(20),
                'expire_date' => now()->addYears(2),
            ]);

            Product::create([
                'category_id' => $skincare->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Sunscreen SPF 50',
                'code' => 'SKIN-SUN-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 1800.00,
                'selling_price' => 3200.00,
                'stock_quantity' => 45,
                'discount' => 15,
                'discounted_price' => 2720.00,
                'batch_no' => 'BATCH-019',
                'purchase_date' => now()->subDays(12),
                'expire_date' => now()->addYears(2),
            ]);

            Product::create([
                'category_id' => $skincare->id,
                'supplier_id' => $supplier->id,
                'unit_id' => $unit->id,
                'name' => 'Face Wash 150ml',
                'code' => 'SKIN-FW-001',
                'barcode' => $this->generateBarcode(),
                'cost_price' => 800.00,
                'selling_price' => 1500.00,
                'stock_quantity' => 90,
                'discount' => 0,
                'discounted_price' => 1500.00,
                'batch_no' => 'BATCH-020',
                'purchase_date' => now()->subDays(8),
                'expire_date' => now()->addYears(3),
            ]);
        }

        $this->command->info('Products seeded successfully!');
    }

    /**
     * Generate a random barcode
     */
    private function generateBarcode(): string
    {
        return 'BAR' . strtoupper(Str::random(9));
    }
}
