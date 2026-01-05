<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCommission;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class EmployeeCommissionController extends Controller
{
    /**
     * Display the employee commission dashboard
     */
    public function index(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager'])) {
            abort(403, 'Unauthorized');
        }

        // Get filter parameters
        $employeeId = $request->input('employee_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build query
        $query = EmployeeCommission::with(['employee', 'product', 'category', 'sale']);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($startDate) {
            $query->whereDate('commission_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('commission_date', '<=', $endDate);
        }

        // Get commissions
        $commissions = $query->orderBy('commission_date', 'desc')->get();

        // Calculate summary statistics
        $totalCommission = $commissions->sum('commission_amount');
        $totalSales = $commissions->sum('total_product_amount');
        $totalTransactions = $commissions->unique('sale_id')->count();

        // Group by employee
        $employeeSummary = $commissions->groupBy('employee_id')->map(function ($employeeCommissions) {
            $employee = $employeeCommissions->first()->employee;
            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'total_commission' => $employeeCommissions->sum('commission_amount'),
                'total_sales' => $employeeCommissions->sum('total_product_amount'),
                'transaction_count' => $employeeCommissions->unique('sale_id')->count(),
                'average_commission' => $employeeCommissions->avg('commission_amount'),
            ];
        })->values();

        // Get all employees for filter dropdown
        $allEmployees = Employee::orderBy('name', 'asc')->get();

        return Inertia::render('CashierCommission/Index', [
            'commissions' => $commissions,
            'employeeSummary' => $employeeSummary,
            'allEmployees' => $allEmployees,
            'filters' => [
                'employee_id' => $employeeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_commission' => $totalCommission,
                'total_sales' => $totalSales,
                'total_transactions' => $totalTransactions,
            ],
        ]);
    }

    /**
     * Get commission details for a specific employee
     */
    public function show($employeeId, Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager', 'Cashier'])) {
            abort(403, 'Unauthorized');
        }

        $employee = Employee::findOrFail($employeeId);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = EmployeeCommission::with(['product', 'category', 'sale'])
            ->where('employee_id', $employeeId);

        if ($startDate) {
            $query->whereDate('commission_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('commission_date', '<=', $endDate);
        }

        $commissions = $query->orderBy('commission_date', 'desc')->get();

        $totalCommission = $commissions->sum('commission_amount');
        $totalSales = $commissions->sum('total_product_amount');

        // Group by category
        $categoryBreakdown = $commissions->groupBy('category_id')->map(function ($categoryCommissions) {
            $category = $categoryCommissions->first()->category;
            return [
                'category_name' => $category->name,
                'commission_percentage' => $categoryCommissions->first()->commission_percentage,
                'total_commission' => $categoryCommissions->sum('commission_amount'),
                'total_sales' => $categoryCommissions->sum('total_product_amount'),
                'item_count' => $categoryCommissions->sum('quantity'),
            ];
        })->values();

        // If AJAX request, return JSON for modal
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'employee' => $employee,
                'commissions' => $commissions,
                'categoryBreakdown' => $categoryBreakdown,
                'summary' => [
                    'total_commission' => $totalCommission,
                    'total_sales' => $totalSales,
                    'transaction_count' => $commissions->unique('sale_id')->count(),
                ],
            ]);
        }

        return Inertia::render('CashierCommission/Show', [
            'employee' => $employee,
            'commissions' => $commissions,
            'categoryBreakdown' => $categoryBreakdown,
            'summary' => [
                'total_commission' => $totalCommission,
                'total_sales' => $totalSales,
                'transaction_count' => $commissions->unique('sale_id')->count(),
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get commission summary for the logged-in cashier (for their own view)
     */
    public function mySummary(Request $request)
    {
        if (!Gate::allows('hasRole', ['Cashier'])) {
            abort(403, 'Unauthorized');
        }

        $user = $request->user();
        
        // Assuming cashier's employee_id is linked to their user account
        // You may need to adjust this based on your user-employee relationship
        $employee = Employee::where('email', $user->email)->first();

        if (!$employee) {
            return response()->json([
                'message' => 'Employee record not found for current user',
            ], 404);
        }

        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        $commissions = EmployeeCommission::with(['product', 'category', 'sale'])
            ->where('employee_id', $employee->id)
            ->whereDate('commission_date', '>=', $startDate)
            ->whereDate('commission_date', '<=', $endDate)
            ->orderBy('commission_date', 'desc')
            ->get();

        $totalCommission = $commissions->sum('commission_amount');

        return response()->json([
            'total_commission' => $totalCommission,
            'commission_count' => $commissions->count(),
            'commissions' => $commissions,
        ]);
    }
}
