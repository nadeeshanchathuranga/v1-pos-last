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
        Schema::table('sale_items', function (Blueprint $table) {
            // Per-product discount fields - only add if they don't exist
            if (!Schema::hasColumn('sale_items', 'discount')) {
                $table->decimal('discount', 5, 2)->default(0)->after('total_price')->comment('Discount percentage for this item');
            }
            if (!Schema::hasColumn('sale_items', 'apply_discount')) {
                $table->boolean('apply_discount')->default(false)->after('discount')->comment('Whether discount is applied to this item');
            }
            if (!Schema::hasColumn('sale_items', 'discounted_price')) {
                $table->decimal('discounted_price', 10, 2)->nullable()->after('apply_discount')->comment('Final price after applying discount');
            }
            if (!Schema::hasColumn('sale_items', 'include_custom')) {
                $table->boolean('include_custom')->default(false)->after('discounted_price')->comment('Whether item is included in custom discount');
            }
            if (!Schema::hasColumn('sale_items', 'selling_price')) {
                $table->decimal('selling_price', 10, 2)->nullable()->after('include_custom')->comment('Original selling price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'discount',
                'apply_discount', 
                'discounted_price',
                'include_custom',
                'selling_price'
            ]);
        });
    }
};