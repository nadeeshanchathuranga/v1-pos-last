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
        Schema::table('return_items', function (Blueprint $table) {
            $table->enum('return_type', ['cash', 'p2p'])->default('cash')->after('total_price');
            $table->foreignId('new_product_id')->nullable()->constrained('products')->onDelete('set null')->after('return_type');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null')->after('new_product_id');
            $table->decimal('new_product_amount', 10, 2)->nullable()->after('employee_id');
            $table->integer('original_quantity')->nullable()->after('new_product_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            $table->dropForeign(['new_product_id']);
            $table->dropForeign(['employee_id']);
            $table->dropColumn(['return_type', 'new_product_id', 'employee_id', 'new_product_amount', 'original_quantity']);
        });
    }
};
