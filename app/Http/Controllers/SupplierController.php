<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPayment; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;



class SupplierController extends Controller
{
 


  public function index()
    {

  



        $allsuppliers = Supplier::with([
   
    'products' => function ($query) {
        $query->select('id', 'supplier_id', 'name', 'cost_price', 'total_quantity');
    }
])
->orderBy('id', 'desc')
->get();



        return Inertia::render('Suppliers/Index', [
            'allsuppliers' => $allsuppliers,
            'totalSuppliers' => $allsuppliers->count()
        ]);
    }


    // public function create()
    // {
    //     $categories = Category::all();

    //     return Inertia::render('Categories/Create', [
    //         'categories' => $categories,
    //     ]);
    // }

    public function store(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:191|regex:/^[a-zA-Z\s]+$/',
           'contact' => 'required|string|regex:/^\d{10}$/',
            'email' => 'required|email|regex:/^[\w\.-]+@[a-zA-Z0-9\.-]+\.[a-zA-Z]{2,6}$/|max:255|unique:suppliers,email',
            'address' => 'required|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1024',
        ]);



        // if ($request->hasFile('image')) {
        //     $fileExtension = $request->file('image')->getClientOriginalExtension();
        //     $fileName = 'supplier' . date("YmdHis") . '.' . $fileExtension;
        //     $destinationPath = "images/uploads/supplier/";
        //     $request->file('image')->move(public_path($destinationPath), $fileName);
        //     $validated['image'] = $destinationPath . $fileName;
        // }

        if ($request->hasFile('image')) {
            $fileExtension = $request->file('image')->getClientOriginalExtension();
            $fileName = 'supplier_' . date("YmdHis") . '.' . $fileExtension;
            $path = $request->file('image')->storeAs('suppliers', $fileName, 'public');
            $validated['image'] = 'storage/' . $path;
        }

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->banner('Supplier created successfully.');
    }


    public function update(Request $request, Supplier $supplier)
    {

        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }
        // Validate incoming data
        $validated = $request->validate([
            'name' => 'nullable|string|max:191',
            'contact' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:suppliers,email,' . $supplier->id,
            'address' => 'nullable|string|max:500',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);


        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($supplier->image && Storage::disk('public')->exists(str_replace('storage/', '', $supplier->image))) {
                Storage::disk('public')->delete(str_replace('storage/', '', $supplier->image));
            }

            // Save the new image
            $fileExtension = $request->file('image')->getClientOriginalExtension();
            $fileName = 'supplier_' . date("YmdHis") . '.' . $fileExtension;
            $path = $request->file('image')->storeAs('suppliers', $fileName, 'public');
            $validated['image'] = 'storage/' . $path;
        } else {
            // Retain the old image if no new image is uploaded
            $validated['image'] = $supplier->image;
        }


        $supplier->update($validated);


        // Redirect back with success message
        return redirect()->route('suppliers.index')->banner('Supplier updated successfully.');
    }





    public function destroy(Supplier $supplier)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        if ($supplier->image && Storage::disk('public')->exists(str_replace('storage/', '', $supplier->image))) {
            Storage::disk('public')->delete(str_replace('storage/', '', $supplier->image));
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->banner('Supplier deleted successfully.');
    }

    public function showProducts($id)
    {
        $supplier = Supplier::findOrFail($id);
        $products = Product::where('supplier_id', $id)->with('category')->get();
        
        // Get stock transfers for all products from this supplier
        $transfers = \DB::table('stock_transactions')
            ->join('products', 'stock_transactions.product_id', '=', 'products.id')
            ->where('products.supplier_id', $id)
            ->select(
                'stock_transactions.*',
                'products.name as product_name'
            )
            ->orderBy('stock_transactions.created_at', 'desc')
            ->get();

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier,
            'products' => $products,
            'transfers' => $transfers,
        ]);
    }



 

public function supplierPayment(Request $request)
{

 
    $validated = $request->validate([
        'supplier_id' => 'required|exists:suppliers,id',
        'pay'         => 'required|numeric|min:0',
        'total'       => 'nullable|numeric|min:0', // total bill for this supplier
    ]);

    $supplier = Supplier::findOrFail($validated['supplier_id']);

    // Get latest known total_cost for this supplier
    $existingTotalCost = SupplierPayment::where('supplier_id', $supplier->id)
        ->max('total_cost');

    // If client sends a new total, use that; else keep existing or 0
    $totalCost = $validated['total'] ?? $existingTotalCost ?? 0;

    // How much has already been paid before this payment?
    $alreadyPaid = SupplierPayment::where('supplier_id', $supplier->id)
        ->sum('pay');

    // New overall paid amount
    $newPaidTotal = $alreadyPaid + $validated['pay'];

    // Compute balance
    $balance = max(0, $totalCost - $newPaidTotal);

    // Decide string status for the overall bill after this payment
    // 'pending' = partial, 'complete' = fully paid
    $status = $balance <= 0 ? 'complete' : 'pending';

    // Persist payment in a transaction
    $payment = DB::transaction(function () use ($supplier, $totalCost, $validated, $status) {
        return SupplierPayment::create([
            'supplier_id' => $supplier->id,
            'total_cost'  => $totalCost,
            'pay'         => $validated['pay'],
            'status'      => $status, // now integer, no truncation
        ]);
    });

    return response()->json([
        'message'       => 'Payment recorded successfully',
        'supplier'      => $supplier,
        'payment'       => $payment,
        'summary' => [
            'total_cost' => $totalCost,
            'paid_total' => $newPaidTotal,
            'balance'    => $balance,
            'status'     => $status, // 'pending'/'complete'
        ],
    ]);
}


    /**
     * Return a quick payment summary for a supplier.
     * GET /suppliers/{id}/summary
     */
    public function paymentSummary($id)
    {
        $supplier = Supplier::with('products')->findOrFail($id);

        // Compute current total from products (same logic as frontend)
        $totalCost = 0;
        if ($supplier->products && $supplier->products->count()) {
            $totalCost = $supplier->products->reduce(function ($carry, $product) {
                $cost = floatval($product->cost_price ?? 0);
                $qty = floatval($product->total_quantity ?? 0);
                return $carry + ($cost * $qty);
            }, 0);
        }

        // Sum of payments already recorded
        $paidTotal = SupplierPayment::where('supplier_id', $supplier->id)->sum('pay');

        $balance = max(0, $totalCost - $paidTotal);
        $status = $balance <= 0 ? 'complete' : 'pending';

        return response()->json([
            'supplier_id' => $supplier->id,
            'total_cost' => $totalCost,
            'paid_total' => $paidTotal,
            'balance' => $balance,
            'status' => $status,
        ]);
    }

    /**
     * Download supplier payment history as an HTML (printable) report.
     * GET /suppliers/{id}/payments/pdf
     */
    public function downloadPaymentsPDF($id)
    {
        try {
            // eager-load payments and products for calculations
            $supplier = Supplier::with(['payments' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }, 'products'])->findOrFail($id);

            $payments = $supplier->payments;

            // Compute totals
            $totalCost = 0;
            if ($supplier->products && $supplier->products->count()) {
                $totalCost = $supplier->products->reduce(function ($carry, $product) {
                    $cost = floatval($product->cost_price ?? 0);
                    $qty = floatval($product->total_quantity ?? 0);
                    return $carry + ($cost * $qty);
                }, 0);
            }

            $paidTotal = $payments->sum('pay');
            $balance = max(0, $totalCost - $paidTotal);

            // Build HTML report
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Supplier Payments</title><style>body{font-family:Arial,Helvetica,sans-serif;margin:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:8px}th{background:#f3f4f6}</style></head><body>';
            $html .= '<h2>Supplier Payment History</h2>';
            $html .= '<div><strong>Supplier:</strong> ' . e($supplier->name) . '</div>';
            $html .= '<div><strong>Generated:</strong> ' . now()->format('F d, Y h:i A') . '</div>';
            $html .= '<div style="margin-top:10px"><strong>Total Bill:</strong> LKR ' . number_format($totalCost,2) . ' &nbsp;&nbsp; <strong>Paid:</strong> LKR ' . number_format($paidTotal,2) . ' &nbsp;&nbsp; <strong>Balance:</strong> LKR ' . number_format($balance,2) . '</div>';

            $html .= '<table style="margin-top:20px"><thead><tr><th>#</th><th>Date</th><th>Pay</th> <th>Status</th> </tr></thead><tbody>';
            if ($payments->count()) {
                $i = 1;
                foreach ($payments as $p) {
                    $html .= '<tr>';
                    $html .= '<td>' . $i++ . '</td>';
                    $html .= '<td>' . $p->created_at->format('M d, Y h:i A') . '</td>';
                    $html .= '<td>LKR ' . number_format($p->pay,2) . '</td>';
                    $html .= '<td>' . e($p->status) . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr><td colspan="4" style="text-align:center;padding:20px">No payments recorded.</td></tr>';
            }
            $html .= '</tbody></table>';

            $html .= '<script>window.onload=function(){setTimeout(function(){window.print()},400)}</script>';
            $html .= '</body></html>';

            // return HTML to render directly in the new tab (no attachment header)
            return response($html)
                ->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            // Log and show a readable error instead of blank page
            \Log::error('downloadPaymentsPDF error: ' . $e->getMessage());
            $errHtml = '<!DOCTYPE html><html><body><h3>Error generating report</h3><div>' . e($e->getMessage()) . '</div></body></html>';
            return response($errHtml, 500)->header('Content-Type', 'text/html');
        }
    }



}
