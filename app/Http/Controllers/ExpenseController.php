<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::query();
        
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }
        if ($request->has('payment_method') && $request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }
        
        $expenses = $query->orderBy('date', 'desc')->get();
        $total = $query->sum('amount');
        
        return Inertia::render('Expenses/index', [
            'expenses' => $expenses,
            'total' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
        ]);
        
        Expense::create($request->only([
            'date', 'category', 'amount', 'payment_method', 'reference', 'description', 'note'
        ]));
        
        return redirect()->back()->with('success', 'Expense added successfully');
    }

    public function update(Request $request, Expense $expense)
    {
        $request->validate([
            'date' => 'required|date',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string',
            'description' => 'nullable|string',
            'note' => 'nullable|string',
        ]);
        
        $expense->update($request->only([
            'date', 'category', 'amount', 'payment_method', 'reference', 'description', 'note'
        ]));
        
        return redirect()->back()->with('success', 'Expense updated successfully');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->back()->with('success', 'Expense deleted successfully');
    }

    public function dashboardSummary()
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $todayTotal = Expense::whereDate('date', $today)->sum('amount');
        $monthTotal = Expense::whereBetween('date', [$monthStart, $today])->sum('amount');
        
        return response()->json([
            'todayTotal' => $todayTotal,
            'monthTotal' => $monthTotal,
        ]);
    }
}
