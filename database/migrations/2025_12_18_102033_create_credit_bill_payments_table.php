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
        Schema::create('credit_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_bill_id')->constrained('credit_bills')->onDelete('cascade');
            $table->decimal('payment_amount', 10, 2);
            $table->date('payment_date');
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('credit_bill_id');
            $table->index('payment_date');
            $table->index('user_id');
            $table->index(['credit_bill_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_bill_payments');
    }
};
