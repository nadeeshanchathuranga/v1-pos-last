<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration syncs existing credit bill payments by ensuring that:
     * 1. All paid amounts in credit_bills table have corresponding records in credit_bill_payments table
     * 2. Credit bill amounts are recalculated based on actual payment records
     */
    public function up(): void
    {
        // Get all credit bills that have payments recorded but might be missing payment records
        $creditBills = DB::table('credit_bills')
            ->where('paid_amount', '>', 0)
            ->get();

        foreach ($creditBills as $creditBill) {
            // Check if payment records exist for this credit bill
            $existingPayments = DB::table('credit_bill_payments')
                ->where('credit_bill_id', $creditBill->id)
                ->sum('payment_amount');

            // If there's a discrepancy, create a reconciliation payment record
            $discrepancy = $creditBill->paid_amount - $existingPayments;
            
            if ($discrepancy > 0) {
                DB::table('credit_bill_payments')->insert([
                    'credit_bill_id' => $creditBill->id,
                    'payment_amount' => $discrepancy,
                    'payment_date' => $creditBill->updated_at ?? now(),
                    'payment_method' => 'cash',
                    'notes' => 'Data migration: reconciliation payment for existing paid amount',
                    'user_id' => 1, // Assuming admin user ID is 1
                    'created_at' => $creditBill->updated_at ?? now(),
                    'updated_at' => $creditBill->updated_at ?? now(),
                ]);
            }
        }

        // Now recalculate all credit bill amounts based on payment records
        $allCreditBills = DB::table('credit_bills')->get();
        
        foreach ($allCreditBills as $creditBill) {
            $totalPaid = DB::table('credit_bill_payments')
                ->where('credit_bill_id', $creditBill->id)
                ->sum('payment_amount');
            
            $remaining = max(0, $creditBill->total_amount - $totalPaid);
            
            // Determine payment status
            $status = 'pending';
            if ($remaining <= 0) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partial';
            }

            // Update the credit bill record
            DB::table('credit_bills')
                ->where('id', $creditBill->id)
                ->update([
                    'paid_amount' => $totalPaid,
                    'remaining_amount' => $remaining,
                    'payment_status' => $status,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove reconciliation payment records created by this migration
        DB::table('credit_bill_payments')
            ->where('notes', 'LIKE', '%Data migration: reconciliation payment%')
            ->delete();
    }
};
