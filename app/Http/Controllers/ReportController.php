<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Report;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ReturnItem;
use App\Models\StockTransaction;
use App\Models\Expense;
use App\Models\CreditBillPayment;
use App\Models\InCash;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */


   public function index(Request $request)
{
    if (!Gate::allows('hasRole', ['Admin'])) {
        abort(403, 'Unauthorized');
    }

    $startDate = $request->input('start_date', '');
    $endDate = $request->input('end_date', '');

    $productsQuery = Product::with('supplier','category')->orderBy('created_at','desc');

    $products = Product::with('supplier','category')->orderBy('created_at', 'desc')->get();
    
    if($startDate && $endDate){
        $products->whereBetween('sale_date',[$startDate, $endDate]);
    }

    // Query for sales with optional date range filtering
    $salesQuery = Sale::whereHas('saleItems.product.category')
    ->with(['saleItems.product.category', 'employee']);

    // Base query for sales quantities
    $salesQuantitiesQuery = SaleItem::query()
        ->whereHas('sale');
    
     // Apply date filtering if provided
     if ($startDate && $endDate) {
             
        $productsQuery->whereHas('saleItems.sale',function($query) use ($startDate,$endDate){
           $query->whereBetween('sale_date',[$startDate,$endDate]);
        });
        $salesQuery->whereBetween('sale_date', [$startDate, $endDate]);

        // Apply the same date filter to sales quantities
        $salesQuantitiesQuery->whereHas('sale', function($query) use ($startDate, $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        });

    }
    $products =  $productsQuery->orderBy('created_at', 'desc')->get();
      
      // Get filtered sales quantities
      $salesQuantities = $salesQuantitiesQuery
      ->select('product_id')
      ->selectRaw('SUM(quantity) as total_sales_qty')
      ->groupBy('product_id')
      ->get()
      ->keyBy('product_id');

      $monthlySales = DB::table('sales')
      ->select(
          DB::raw('YEAR(sale_date) as year'),
          DB::raw('MONTH(sale_date) as month'),
          DB::raw('COUNT(*) as number_of_sales'),
          DB::raw('SUM(total_amount) as total_amount')
      )
      ->groupBy(DB::raw('YEAR(sale_date), MONTH(sale_date)'))
      ->orderBy('year', 'desc')
      ->orderBy('month', 'desc');

  // Apply date filtering if provided
  if ($startDate && $endDate) {
      $monthlySales->whereBetween('sale_date', [$startDate, $endDate]);
  }

  $monthlySalesData = $monthlySales->get()->map(function ($item) {
    return [
        'year' => $item->year,
        'month' => $item->month,
        'month_name'=> DateTime::createFromFormat('!m', $item->month)->format('F'),
        'number_of_sales' => $item->number_of_sales,
        'total_amount' => $item->total_amount,
        'date_range' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT) . '-01' . 
                       ' to ' . 
                       $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT) . '-' . 
                       cal_days_in_month(CAL_GREGORIAN, $item->month, $item->year)
    ];
});

// =========================
// Today's Sales Data
// =========================
$todayDate = now()->format('Y-m-d'); // Get today's date in Y-m-d format

$todaySales = DB::table('sales')
    ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
    ->select(
        'sales.id',
        'sales.sale_date',
        'sales.total_amount',
        'sales.payment_method',
        'customers.name as customer_name',
        'sales.employee_id',
        'sales.created_at',
        'sales.order_id'
    )
    ->whereDate('sales.sale_date', $todayDate)
    ->orderBy('sales.created_at', 'desc')
    ->get()
    ->map(function ($sale) {
        return [
            'id' => $sale->id,
            'sale_date' => $sale->sale_date,
            'total_amount' => (float) $sale->total_amount,
            'payment_method' => $sale->payment_method,
            'customer_name' => $sale->customer_name ?: 'Walk-in Customer',
            'time' => \Carbon\Carbon::parse($sale->created_at)->format('H:i A'),
            'order_id' => $sale->order_id
        ];
    });

$todaySalesTotal = collect($todaySales)->sum('total_amount');
$todaySalesCount = count($todaySales);



    //   $monthlyProductSales = DB::table('sales')
    //    ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
    //    ->join('products', 'sale_items.product_id', '=', 'products.id')
    //    ->selectRaw('
    //          MONTH(sales.sale_date) as month,
    //          products.name as product,
    //          SUM(sale_items.quantity) as total_quantity,
    //          SUM(sale_items.quantity * sale_items.unit_price) as total_amount
    //    ')
    //    ->groupBy('month','products.name')
    //    ->orderBy('month')
    //    ->get();

        // Add filtered sales quantities to products
        $products->transform(function ($product) use ($salesQuantities) {
            $product->sales_qty = $salesQuantities->get($product->id)?->total_sales_qty ?? 0;
            return $product;
        });

        $sales = $salesQuery->orderBy('sale_date', 'desc')->get();

        // Calculate category sales (including negative quantities from returns)
        $categorySales = [];
        foreach ($sales as $sale) {
            foreach ($sale->saleItems as $item) {
                $categoryName = $item->product->category->name ?? 'No Category';
                // Use item total_price to properly account for negative quantities
                $itemAmount = $item->total_price;
                $categorySales[$categoryName] = ($categorySales[$categoryName] ?? 0) + $itemAmount;
            }
        }

          // Calculate payment method totals
          $paymentMethodTotals = $sales->groupBy('payment_method')
          ->map(function ($group) {
              return $group->sum('total_amount');
          })->toArray();

      // Calculate employee sales summary
      $employeeSalesSummary = [];
      foreach ($sales as $sale) {
          if (!$sale->employee) continue;

          $employeeName = $sale->employee->name;
          if (!isset($employeeSalesSummary[$employeeName])) {
              $employeeSalesSummary[$employeeName] = [
                  'Employee Name' => $employeeName,
                  'Total Sales Amount' => 0,
              ];
          }

          // total_amount already has discounts applied
          $employeeSalesSummary[$employeeName]['Total Sales Amount'] += $sale->total_amount;
      }







    // =========================
    // 1. Get Product IDs that were sold within date range
    // =========================
    if ($startDate && $endDate) {
        $productIds = SaleItem::whereHas('sale', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        })->pluck('product_id')->unique();

        // Only get products involved in sales during the selected date range
        $products = Product::whereIn('id', $productIds)->orderBy('created_at', 'desc')->get();
    } else {
        // If no date filter applied, get all products
        $products = Product::orderBy('created_at', 'desc')->get();
    }

    // =========================
    // 2. Sales Query (with date range if present)
    // =========================
    $salesQuery = Sale::whereHas('saleItems.product.category')
        ->with(['saleItems.product.category', 'employee']);

    $salesQuantitiesQuery = SaleItem::query()->whereHas('sale');

    if ($startDate && $endDate) {
        $salesQuery->whereBetween('sale_date', [$startDate, $endDate]);

        $salesQuantitiesQuery->whereHas('sale', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('sale_date', [$startDate, $endDate]);
        });
    }

    $products =  $productsQuery->orderBy('created_at', 'desc')->get();
       
    // =========================
    // 3. Get Total Sales Qty per Product (Filtered)
    // =========================
    $salesQuantities = $salesQuantitiesQuery
        ->select('product_id')
        ->selectRaw('SUM(quantity) as total_sales_qty')
        ->groupBy('product_id')
        ->get()
        ->keyBy('product_id');

    // =========================
    // 4. Assign sales_qty to each product
    // =========================
    $products->transform(function ($product) use ($salesQuantities) {
        $product->sales_qty = $salesQuantities->get($product->id)?->total_sales_qty ?? 0;
        return $product;
    });

    // =========================
    // 5. Get Sales Data
    // =========================
    $sales = $salesQuery->orderBy('sale_date', 'desc')->get();


    // =========================
    // 6. Category Sales
    // =========================
    $categorySales = [];
    foreach ($sales as $sale) {
        foreach ($sale->saleItems as $item) {
            $categoryName = $item->product->category->name ?? 'No Category';
            $totalAmount = $sale->total_amount;
            $categorySales[$categoryName] = ($categorySales[$categoryName] ?? 0) + $sale->total_amount;
        }
    }

    // =========================
    // 7. Payment Method Totals
    // =========================
    $paymentMethodTotals = $sales->groupBy('payment_method')
        ->map(function ($group) {
            return $group->sum('total_amount');
        })->toArray();

    // =========================
    // 8. Employee Sales Summary
    // =========================
    $employeeSalesSummary = [];
    foreach ($sales as $sale) {
        if (!$sale->employee) continue;

        $employeeName = $sale->employee->name;
        if (!isset($employeeSalesSummary[$employeeName])) {
            $employeeSalesSummary[$employeeName] = [
                'Employee Name' => $employeeName,
                'Total Sales Amount' => 0,
            ];
        }

        // total_amount already has discounts applied
        $employeeSalesSummary[$employeeName]['Total Sales Amount'] += $sale->total_amount;
    }

    $stockTransactionsReturn = StockTransaction::with('product')->where('transaction_type','Returned')->get();
    if ($startDate && $endDate) {
        $stockTransactionsReturn->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // =========================
    // 8. Paint Order Reports
    // =========================
    $paintOrderReportsQuery = Report::where('type', 'Paint Orders');
    
    if ($startDate && $endDate) {
        $paintOrderReportsQuery->whereBetween('generated_at', [$startDate, $endDate]);
    }
    
    $paintOrderReports = $paintOrderReportsQuery->orderBy('generated_at', 'desc')->get();
    
    // Process paint order data
    $paintOrderSummary = [
        'total_orders' => $paintOrderReports->count(),
        'total_amount' => 0,
        'total_profit' => 0,
        'total_cost' => 0,
    ];
    
    $paintOrderDetails = [];
    
    foreach ($paintOrderReports as $report) {
        $details = json_decode($report->details, true);
        if ($details) {
            $paintOrderSummary['total_amount'] += $details['total_amount'] ?? 0;
            $paintOrderSummary['total_profit'] += $details['profit'] ?? 0;
            $paintOrderSummary['total_cost'] += ($details['unit_cost'] * $details['quantity']) ?? 0;
            
            $paintOrderDetails[] = array_merge($details, [
                'report_id' => $report->id,
                'generated_at' => $report->generated_at,
            ]);
        }
    }

    //  $stockTransactionsReturn = $stockTransactionsReturn->orderBy('transaction_date', 'desc')->get();   

    // =========================
    // 9. Return Items Analysis
    // =========================
    $returnItemsQuery = ReturnItem::with(['product', 'sale']);
    
    if ($startDate && $endDate) {
        $returnItemsQuery->whereBetween('return_date', [$startDate, $endDate]);
    }
    
    $returnItems = $returnItemsQuery->get();
    $totalReturnAmount = $returnItems->sum('total_price');
    $totalReturnQuantity = $returnItems->sum('quantity');
    
    // =========================
    // 10. Overall Stats (Using Negative Quantity Accounting with Item-Level Costs)
    // =========================
    
    // With negative quantity accounting, the calculations are now automatic:
    // - Returns create negative quantity sale_items (negative revenue, negative cost)
    // - Exchanges create positive quantity sale_items (positive revenue, positive cost)
    // - Profit = SUM((price * qty) - (cost * qty)) automatically accounts for all
    
    // Calculate sales amount excluding unpaid credit bills
    // Only include: 1) Cash/Card sales, 2) Credit bill payments actually collected
    $cashSalesAmount = $sales->where('payment_method', '!=', 'credit bill')->sum('total_amount');
    
    // Calculate credit bill collections (payments received)
    $creditBillCollected = 0;
    if ($startDate && $endDate) {
        $creditBillCollected = CreditBillPayment::whereBetween('payment_date', [$startDate, $endDate])
            ->sum('payment_amount');
    } else {
        $creditBillCollected = CreditBillPayment::sum('payment_amount');
    }
    
    // Total sales amount = cash sales + credit bill payments actually received
    $totalSaleAmount = $cashSalesAmount + $creditBillCollected;
    
    $totalDiscount = $sales->sum('discount');
    $customeDiscount = $sales->sum('custom_discount');
    
    // Calculate total cost only for cash sales and proportional cost for credit bill collections
    $totalCost = 0;
    
    // Add cost for cash/card sales (exclude credit bill sales)
    foreach ($sales as $sale) {
        if ($sale->payment_method !== 'credit bill') {
            foreach ($sale->saleItems as $item) {
                $itemCost = $item->cost_price > 0 ? $item->cost_price : ($item->product->cost_price ?? 0);
                $totalCost += $item->quantity * $itemCost;
            }
        }
    }
    
    // Add proportional cost for credit bill payments collected
    $totalCreditBillSalesAmount = $sales->where('payment_method', 'credit bill')->sum('total_amount');
    if ($totalCreditBillSalesAmount > 0) {
        $creditBillCostRatio = 0;
        foreach ($sales as $sale) {
            if ($sale->payment_method === 'credit bill') {
                foreach ($sale->saleItems as $item) {
                    $itemCost = $item->cost_price > 0 ? $item->cost_price : ($item->product->cost_price ?? 0);
                    $creditBillCostRatio += ($item->quantity * $itemCost);
                }
            }
        }
        // Add proportional cost based on how much of credit bills have been collected
        if ($totalCreditBillSalesAmount > 0) {
            $collectionRatio = $creditBillCollected / $totalCreditBillSalesAmount;
            $totalCost += $creditBillCostRatio * $collectionRatio;
        }
    }
    
    // Calculate net profit: actual revenue received - corresponding costs
    $netProfit = $totalSaleAmount - $totalCost;
    
    // For transparency, calculate gross sales (before any negative adjustments)
    $grossSalesAmount = $totalSaleAmount + $totalReturnAmount;
    
    $totalTransactions = $sales->count();
    $averageTransactionValue = $totalTransactions > 0 ? $totalSaleAmount / $totalTransactions : 0;
    $totalCustomer = $salesQuery->distinct('customer_id')->count('customer_id');

    // =========================
    // 11. Calculate Expenses
    // =========================
    $expensesQuery = Expense::query();
    
    if ($startDate && $endDate) {
        $expensesQuery->whereBetween('date', [$startDate, $endDate]);
    }
    
    $totalExpenses = $expensesQuery->sum('amount');
    $profitAfterExpenses = $netProfit + (isset($paintOrderSummary['total_profit']) ? $paintOrderSummary['total_profit'] : 0) - $totalExpenses;

    // =========================
    // 12. In Cash Calculations
    // =========================
    $inCashQuery = InCash::query();
    
    if ($startDate && $endDate) {
        $inCashQuery->whereBetween('created_at', [$startDate, $endDate]);
    }
    
    $totalInCash = $inCashQuery->sum('amount');
    $cashPlusSales = $totalSaleAmount + (isset($paintOrderSummary['total_amount']) ? $paintOrderSummary['total_amount'] : 0) + $totalInCash;

    // =========================
    // 13. Credit Bill Calculations
    
    // Calculate credit bill collections (payments received)
    $creditBillCollected = 0;
    if ($startDate && $endDate) {
        $creditBillCollected = CreditBillPayment::whereBetween('payment_date', [$startDate, $endDate])
            ->sum('payment_amount');
    } else {
        $creditBillCollected = CreditBillPayment::sum('payment_amount');
    }
    
    // Calculate remaining unpaid credit bill amount
    $totalRemainingCreditBills = DB::table('credit_bills')->sum('remaining_amount');
    
    // Get credit bill payments data with date filtering
    $creditBillPaymentsQuery = CreditBillPayment::with(['creditBill.customer', 'user'])
        ->orderBy('payment_date', 'desc');
    
    if ($startDate && $endDate) {
        $creditBillPaymentsQuery->whereBetween('payment_date', [$startDate, $endDate]);
    }
    
    $creditBillPayments = $creditBillPaymentsQuery->get();

    // =========================
    // 13. Return to Vue via Inertia
    // =========================
    return Inertia::render('Reports/Index', [
        'products' => $products,
        'sales' => $sales,
        'totalSaleAmount' => $totalSaleAmount,
        'totalCustomer' => $totalCustomer,
        'netProfit' => $netProfit,
        'totalDiscount' => $totalDiscount,
        'customeDiscount' => $customeDiscount,
        'totalTransactions' => $totalTransactions,
        'averageTransactionValue' => round($averageTransactionValue, 2),
        'startDate' => $startDate,
        'endDate' => $endDate,
        'categorySales' => $categorySales,
        'employeeSalesSummary' => $employeeSalesSummary,
        'monthlySalesData' => $monthlySalesData,
        'todaySalesData' => $todaySales,
        'todaySalesTotal' => $todaySalesTotal,
        'todaySalesCount' => $todaySalesCount,
        'paymentMethodTotals'=> $paymentMethodTotals,
        'stockTransactionsReturn'=>$stockTransactionsReturn,
        'paintOrderSummary' => $paintOrderSummary,
        'paintOrderDetails' => $paintOrderDetails,
        'totalExpenses' => $totalExpenses,
        'profitAfterExpenses' => $profitAfterExpenses,
        // In Cash data
        'totalInCash' => $totalInCash,
        'cashPlusSales' => $cashPlusSales,
        // Return items data
        'returnItems' => $returnItems,
        'totalReturnAmount' => $totalReturnAmount,
        'totalReturnQuantity' => $totalReturnQuantity,
        'grossSalesAmount' => $grossSalesAmount,
        // Credit bill data
        'totalCreditBillAmount' => $totalRemainingCreditBills, // Show remaining unpaid amount
        'creditBillCollected' => $creditBillCollected,
        'creditBillPayments' => $creditBillPayments,
        'outstandingCreditBills' => $totalRemainingCreditBills,
        // Credit bill cards for dashboard
        'creditBillCards' => [
            'total_outstanding' => $totalRemainingCreditBills,
            'total_collected' => $creditBillCollected,
            'total_credit_bills' => $totalRemainingCreditBills
        ],
    ]);
}

    public function searchByCode(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json([
                'products' => [],
                'totalQuantity' => 0,
                'remainingQuantity' => 0
            ]);
        }

        $products = Product::where('code', $code)
            ->select([
                'batch_no',
                'total_quantity',
                'stock_quantity',
                'expire_date',
                'purchase_date',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalQuantity = $products->sum('total_quantity');
        $remainingQuantity = $products->sum('stock_quantity');

        return response()->json([
            'products' => $products,
            'totalQuantity' => $totalQuantity,
            'remainingQuantity' => $remainingQuantity
        ]);
    }




















    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Report $report)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Report $report)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        //
    }

    /**
     * Store a new in-cash entry.
     */
    public function addInCash(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:1000',
        ]);

        InCash::create([
            'amount' => $request->amount,
            'note' => $request->note,
        ]);

        return redirect()->back()->with('success', 'Cash added successfully!');
    }
}
