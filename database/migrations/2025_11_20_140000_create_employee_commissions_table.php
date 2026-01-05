<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('sale_item_id')->constrained('sale_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->decimal('commission_percentage', 8, 2)->default(0); // Commission % from category
            $table->decimal('product_price', 10, 2); // Unit price of the product
            $table->integer('quantity'); // Quantity sold
            $table->decimal('total_product_amount', 10, 2); // product_price * quantity
            $table->decimal('commission_amount', 10, 2); // Calculated commission earned
            $table->timestamp('commission_date');
            $table->timestamps();

            // Indexes for faster queries
            $table->index('employee_id');
            $table->index('sale_id');
            $table->index('commission_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_commissions');
    }
};
