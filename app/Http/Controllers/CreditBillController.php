<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CreditBill;
use App\Models\CreditBillPayment;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreditBillController extends Controller
{
    public function index()
    {
        if (!Gate::allows('hasRole', ['Admin', 'Operator'])) {
            abort(403, 'Unauthorized');
        }

        $allCreditBills = CreditBill::with(['customer', 'sale', 'payments' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCreditBills = CreditBill::all();

        $stats = [
            'total_pending' => CreditBill::pending()->sum('remaining_amount'),
            'total_partial' => CreditBill::partial()->sum('remaining_amount'),
            'total_bills' => CreditBill::count(),
        ];

        return Inertia::render('CreditBill/Index', [
            'allCreditBills' => $allCreditBills,
            'totalCreditBills' => $totalCreditBills,
            'stats' => $stats,
        ]);
    }

    public function show($id)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Operator'])) {
            abort(403, 'Unauthorized');
        }

        $creditBill = CreditBill::with(['customer', 'sale.saleItems.product'])
            ->findOrFail($id);

        return Inertia::render('CreditBill/Show', [
            'creditBill' => $creditBill,
        ]);
    }

    public function updatePayment(Request $request, $id)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Operator'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,card,bank_transfer,check',
            'notes' => 'nullable|string|max:500',
        ]);

        $creditBill = CreditBill::findOrFail($id);
        $paymentAmount = $request->payment_amount;

        if ($paymentAmount > $creditBill->remaining_amount) {
            return back()->withErrors(['payment_amount' => 'Payment amount cannot exceed remaining amount.']);
        }

        DB::transaction(function () use ($creditBill, $paymentAmount, $request) {
            // Create payment record
            $creditBill->payments()->create([
                'payment_amount' => $paymentAmount,
                'payment_date' => now(),
                'payment_method' => $request->payment_method ?? 'cash',
                'notes' => $request->notes,
                'user_id' => auth()->id(),
            ]);

            // The CreditBillPayment model events will automatically update the credit bill amounts
        });

        return back()->with('success', 'Payment updated successfully!');
    }

    public function markAsPaid($id)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Operator'])) {
            abort(403, 'Unauthorized');
        }

        $creditBill = CreditBill::findOrFail($id);
        
        // Create a payment record for the full remaining amount
        if ($creditBill->remaining_amount > 0) {
            $creditBill->payments()->create([
                'payment_amount' => $creditBill->remaining_amount,
                'payment_date' => now(),
                'payment_method' => 'cash',
                'notes' => 'Marked as fully paid',
                'user_id' => auth()->id(),
            ]);
        }
        // The CreditBillPayment model events will automatically update the amounts

        return back()->with('success', 'Credit bill marked as paid!');
    }

    public function destroy($id)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Operator'])) {
            abort(403, 'Unauthorized');
        }

        $creditBill = CreditBill::findOrFail($id);
        $creditBill->delete();

        return back()->with('success', 'Credit bill deleted successfully!');
    }
}
