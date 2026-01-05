<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing categories
        Category::truncate();

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Main Categories (parent_id = null)
        $electronics = Category::create([
            'name' => 'Electronics',
            'parent_id' => null,
            'commission' => 5.0,
        ]);

        $clothing = Category::create([
            'name' => 'Clothing',
            'parent_id' => null,
            'commission' => 10.0,
        ]);

        $food = Category::create([
            'name' => 'Food & Beverages',
            'parent_id' => null,
            'commission' => 3.0,
        ]);

        $home = Category::create([
            'name' => 'Home & Garden',
            'parent_id' => null,
            'commission' => 7.0,
        ]);

        $sports = Category::create([
            'name' => 'Sports & Outdoors',
            'parent_id' => null,
            'commission' => 8.0,
        ]);

        $beauty = Category::create([
            'name' => 'Beauty & Personal Care',
            'parent_id' => null,
            'commission' => 12.0,
        ]);

        $books = Category::create([
            'name' => 'Books & Stationery',
            'parent_id' => null,
            'commission' => 6.0,
        ]);

        // Sub-categories for Electronics
        Category::create([
            'name' => 'Mobile Phones',
            'parent_id' => $electronics->id,
            'commission' => 5.0,
        ]);

        Category::create([
            'name' => 'Laptops & Computers',
            'parent_id' => $electronics->id,
            'commission' => 4.0,
        ]);

        Category::create([
            'name' => 'TV & Audio',
            'parent_id' => $electronics->id,
            'commission' => 5.0,
        ]);

        Category::create([
            'name' => 'Cameras',
            'parent_id' => $electronics->id,
            'commission' => 6.0,
        ]);

        Category::create([
            'name' => 'Accessories',
            'parent_id' => $electronics->id,
            'commission' => 8.0,
        ]);

        // Sub-categories for Clothing
        Category::create([
            'name' => 'Men\'s Clothing',
            'parent_id' => $clothing->id,
            'commission' => 10.0,
        ]);

        Category::create([
            'name' => 'Women\'s Clothing',
            'parent_id' => $clothing->id,
            'commission' => 10.0,
        ]);

        Category::create([
            'name' => 'Kids Clothing',
            'parent_id' => $clothing->id,
            'commission' => 10.0,
        ]);

        Category::create([
            'name' => 'Shoes',
            'parent_id' => $clothing->id,
            'commission' => 12.0,
        ]);

        Category::create([
            'name' => 'Bags & Accessories',
            'parent_id' => $clothing->id,
            'commission' => 15.0,
        ]);

        // Sub-categories for Food & Beverages
        Category::create([
            'name' => 'Fresh Fruits',
            'parent_id' => $food->id,
            'commission' => 3.0,
        ]);

        Category::create([
            'name' => 'Vegetables',
            'parent_id' => $food->id,
            'commission' => 3.0,
        ]);

        Category::create([
            'name' => 'Beverages',
            'parent_id' => $food->id,
            'commission' => 2.5,
        ]);

        Category::create([
            'name' => 'Snacks',
            'parent_id' => $food->id,
            'commission' => 4.0,
        ]);

        Category::create([
            'name' => 'Dairy Products',
            'parent_id' => $food->id,
            'commission' => 2.0,
        ]);

        // Sub-categories for Home & Garden
        Category::create([
            'name' => 'Furniture',
            'parent_id' => $home->id,
            'commission' => 7.0,
        ]);

        Category::create([
            'name' => 'Kitchen Appliances',
            'parent_id' => $home->id,
            'commission' => 6.0,
        ]);

        Category::create([
            'name' => 'Garden Tools',
            'parent_id' => $home->id,
            'commission' => 8.0,
        ]);

        Category::create([
            'name' => 'Home Decor',
            'parent_id' => $home->id,
            'commission' => 10.0,
        ]);

        // Sub-categories for Sports & Outdoors
        Category::create([
            'name' => 'Fitness Equipment',
            'parent_id' => $sports->id,
            'commission' => 8.0,
        ]);

        Category::create([
            'name' => 'Outdoor Gear',
            'parent_id' => $sports->id,
            'commission' => 9.0,
        ]);

        Category::create([
            'name' => 'Sports Wear',
            'parent_id' => $sports->id,
            'commission' => 10.0,
        ]);

        // Sub-categories for Beauty & Personal Care
        Category::create([
            'name' => 'Skincare',
            'parent_id' => $beauty->id,
            'commission' => 12.0,
        ]);

        Category::create([
            'name' => 'Makeup',
            'parent_id' => $beauty->id,
            'commission' => 15.0,
        ]);

        Category::create([
            'name' => 'Hair Care',
            'parent_id' => $beauty->id,
            'commission' => 10.0,
        ]);

        Category::create([
            'name' => 'Fragrances',
            'parent_id' => $beauty->id,
            'commission' => 18.0,
        ]);

        // Sub-categories for Books & Stationery
        Category::create([
            'name' => 'Fiction Books',
            'parent_id' => $books->id,
            'commission' => 6.0,
        ]);

        Category::create([
            'name' => 'Non-Fiction Books',
            'parent_id' => $books->id,
            'commission' => 6.0,
        ]);

        Category::create([
            'name' => 'Office Supplies',
            'parent_id' => $books->id,
            'commission' => 8.0,
        ]);

        Category::create([
            'name' => 'Art Supplies',
            'parent_id' => $books->id,
            'commission' => 10.0,
        ]);

        $this->command->info('Categories seeded successfully!');
    }
}
