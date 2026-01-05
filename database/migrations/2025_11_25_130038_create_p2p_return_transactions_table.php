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
        Schema::create('p2p_return_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('return_sale_id')->nullable()->constrained('sales')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->string('transaction_type')->default('p2p'); // 'cash' or 'p2p'
            
            // Returned product details
            $table->foreignId('returned_product_id')->constrained('products')->onDelete('cascade');
            $table->integer('returned_quantity');
            $table->decimal('returned_unit_price', 10, 2);
            $table->decimal('returned_total_amount', 10, 2);
            
            // New product details (for P2P only)
            $table->foreignId('new_product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->integer('new_product_quantity')->nullable();
            $table->decimal('new_product_unit_price', 10, 2)->nullable();
            $table->decimal('new_product_total_amount', 10, 2)->nullable();
            
            // Financial details
            $table->decimal('net_amount', 10, 2); // returned_total - new_product_total
            $table->text('reason')->nullable();
            $table->date('return_date');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('original_sale_id');
            $table->index('return_sale_id');
            $table->index('return_date');
            $table->index('transaction_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p2p_return_transactions');
    }
};
