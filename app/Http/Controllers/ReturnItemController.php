<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ReturnItem;
use App\Models\Product;
use App\Models\Employee;
use App\Models\EmployeeCommission;
use App\Models\StockTransaction;
use App\Models\P2PReturnTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReturnItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager'])) {
            abort(403, 'Unauthorized');
        }

        $sales = Sale::with('customer', 'employee')->orderBy('created_at', 'desc')->get();
        $saleItems = SaleItem::with('product')->orderBy('created_at', 'desc')->get();

        return Inertia::render('ReturnItem/Index', [
            'sales' => $sales,
            'saleItems' => $saleItems,
        ]);
    }

    /**
     * Display the dedicated returns page.
     */
    public function returnsPage()
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager', 'Cashier'])) {
            abort(403, 'Unauthorized');
        }

        $sales = Sale::with('customer', 'employee')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Returns/Index', [
            'sales' => $sales,
            'loggedInUser' => Auth::user(),
        ]);
    }

    /**
     * Process a cash return - updates the same original bill.
     */
    public function processCashReturn(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager', 'Cashier'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'return_items' => 'required|array|min:1',
            'return_items.*.sale_id' => 'required|exists:sales,id',
            'return_items.*.sale_item_id' => 'required|exists:sale_items,id',
            'return_items.*.product_id' => 'required|exists:products,id',
            'return_items.*.quantity' => 'required|integer|min:1',
            'return_items.*.reason' => 'required|string',
            'return_items.*.unit_price' => 'required|numeric|min:0',
            'return_items.*.return_date' => 'required|date',
            'custom_discount' => 'nullable|numeric|min:0',
            'custom_discount_type' => 'nullable|in:percent,fixed',
            'cash_return_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $originalSale = null;
            $totalReturnAmount = 0;
            $returnedItems = [];

            // Process each return item
            foreach ($validated['return_items'] as $item) {
                $sale = Sale::find($item['sale_id']);
                $originalSale = $sale;
                $saleItem = SaleItem::find($item['sale_item_id']);

                if (!$saleItem) {
                    throw new \Exception("Sale item not found with ID {$item['sale_item_id']}");
                }

                // Skip if this is a negative quantity item (return item itself)
                if ($saleItem->quantity <= 0) {
                    throw new \Exception("Cannot return from a return record. Sale item ID {$item['sale_item_id']} is not a valid sale.");
                }

                // Calculate remaining quantity available for return
                $alreadyReturned = ReturnItem::where('sale_item_id', $item['sale_item_id'])
                    ->sum('quantity');
                $remainingQty = $saleItem->quantity - $alreadyReturned;

                if ($item['quantity'] > $remainingQty) {
                    throw new \Exception("Cannot return {$item['quantity']} units. Only {$remainingQty} units available for return.");
                }

                // Calculate return amount using the unit price from sale item
                $returnAmount = $item['quantity'] * $saleItem->unit_price;
                $totalReturnAmount += $returnAmount;

                // Get product for stock update
                $returnedProduct = Product::find($item['product_id']);

                // Use the cost_price from the original sale item
                $originalCostPrice = $saleItem->cost_price > 0 ? $saleItem->cost_price : $returnedProduct->cost_price;

                // Calculate proportional discount for returned quantity
                $currentQty = $saleItem->quantity;
                $perUnitDiscount = $currentQty > 0 ? ($saleItem->discount / $currentQty) : 0;
                $proportionalDiscount = $perUnitDiscount * $item['quantity'];

                // Create NEGATIVE quantity sale item to reverse the original sale
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => -$item['quantity'], // NEGATIVE quantity
                    'unit_price' => $saleItem->unit_price,
                    'cost_price' => $originalCostPrice,
                    'total_price' => -$returnAmount, // NEGATIVE amount
                    'discount' => -$proportionalDiscount, // NEGATIVE discount being reversed
                ]);

                // Recalculate sale totals from all sale items (positive and negative)
                $sale->refresh(); // Reload to get the new sale_item we just created
                $sale->recalculateTotals();

                // Increase stock for returned product
                $returnedProduct->update([
                    'stock_quantity' => $returnedProduct->stock_quantity + $item['quantity']
                ]);

                // Create stock transaction
                StockTransaction::create([
                    'product_id' => $item['product_id'],
                    'transaction_type' => 'Returned',
                    'quantity' => $item['quantity'],
                    'transaction_date' => $item['return_date'],
                    'supplier_id' => $returnedProduct->supplier_id ?? null,
                ]);

                // Adjust employee commissions if applicable
                if ($sale->employee_id) {
                    $this->adjustEmployeeCommissions($sale, $saleItem, $item, $returnedProduct);
                }

                // Create return item record
                ReturnItem::create([
                    'sale_id' => $item['sale_id'],
                    'sale_item_id' => $item['sale_item_id'],
                    'customer_id' => $sale->customer_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'],
                    'unit_price' => $saleItem->unit_price,
                    'total_price' => $returnAmount,
                    'discount' => 0,
                    'return_date' => $item['return_date'],
                    'return_type' => 'cash',
                    'employee_id' => $sale->employee_id,
                    'original_quantity' => $saleItem->quantity,
                ]);

                // Track returned items for response
                $returnedItems[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $returnedProduct->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $saleItem->unit_price,
                    'total' => $returnAmount,
                    'reason' => $item['reason'],
                ];

                // NOTE: We do NOT modify the original sale_item anymore.
                // The negative quantity sale_item created above (line 123-131) 
                // already handles the return accounting automatically.
                // Modifying the original would double-count the return.
            }

            // Apply custom discount if provided
            $discountAmount = 0;
            if (isset($validated['custom_discount']) && $validated['custom_discount'] > 0) {
                if ($validated['custom_discount_type'] === 'percent') {
                    $discountAmount = ($totalReturnAmount * $validated['custom_discount']) / 100;
                } else {
                    $discountAmount = $validated['custom_discount'];
                }
            }

            $finalRefundAmount = $totalReturnAmount - $discountAmount;
            $cashReturned = $validated['cash_return_amount'] ?? $finalRefundAmount;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Return processed successfully!',
                'data' => [
                    'original_sale_id' => $originalSale->id,
                    'original_order_id' => $originalSale->order_id,
                    'return_items' => $returnedItems,
                    'return_total' => $totalReturnAmount,
                    'discount_applied' => $discountAmount,
                    'final_refund' => $finalRefundAmount,
                    'cash_returned' => $cashReturned,
                    'updated_sale_total' => $originalSale->total_amount,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the return.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchSaleItems(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager', 'Cashier', 'Operator'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'sale_id' => 'required|exists:sales,id',
        ]);

        $sale = Sale::with('employee')->find($request->input('sale_id'));

        $saleItems = SaleItem::with('product')
            ->where('sale_id', $request->input('sale_id'))
            ->where('quantity', '>', 0) // Only get positive quantity items (original sales, not returns)
            ->get()
            ->map(function ($item) {
                // Calculate already returned quantity using sale_item_id
                $returnedQty = ReturnItem::where('sale_item_id', $item->id)
                    ->sum('quantity');

                // Since we now keep the original sale_item unchanged:
                // - item->quantity is the ORIGINAL sale quantity
                // - returnedQty is how much has been returned
                // - remaining_quantity is what can still be returned
                $originalQuantity = $item->quantity;
                $remainingQuantity = $originalQuantity - $returnedQty;

                $item->returned_quantity = $returnedQty;
                $item->remaining_quantity = $remainingQuantity;
                $item->original_sale_quantity = $originalQuantity;

                return $item;
            });

        return response()->json([
            'saleItems' => $saleItems,
            'employee' => $sale->employee,
        ]);
    }

    /**
     * Store a newly created return item.
     * Creates separate sale/bill for P2P returns
     */
    public function store(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager', 'Cashier', 'Operator'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'return_items' => 'required|array',
            'return_items.*.sale_id' => 'required|exists:sales,id',
            'return_items.*.sale_item_id' => 'required|exists:sale_items,id',
            'return_items.*.product_id' => 'required|exists:products,id',
            'return_items.*.quantity' => 'required|integer|min:1',
            'return_items.*.reason' => 'required|string',
            'return_items.*.return_date' => 'required|date',
            'return_items.*.return_type' => 'required|in:cash,p2p',
            'return_items.*.unit_price' => 'required|numeric|min:0',
            // For P2P returns
            'new_products' => 'nullable|array',
            'new_products.*.product_id' => 'required_with:new_products|exists:products,id',
            'new_products.*.quantity' => 'required_with:new_products|integer|min:1',
            'new_products.*.selling_price' => 'required_with:new_products|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $originalSale = null;
            $returnSale = null;
            $hasP2P = false;

            $returnBillData = [
                'return_items' => [],
                'new_products' => [],
                'totals' => [
                    'return_amount' => 0,
                    'new_product_amount' => 0,
                    'net_amount' => 0,
                ]
            ];

            // Check if any P2P returns
            foreach ($validated['return_items'] as $item) {
                if ($item['return_type'] === 'p2p') {
                    $hasP2P = true;
                    break;
                }
            }

            // Process return items
            foreach ($validated['return_items'] as $item) {
                $sale = Sale::find($item['sale_id']);
                $originalSale = $sale;
                $saleItem = SaleItem::find($item['sale_item_id']);

                if (!$saleItem) {
                    throw new \Exception("Sale item not found with ID {$item['sale_item_id']}");
                }

                // Calculate remaining quantity available for return
                // Note: sale_items.quantity gets reduced on each return, so we need to add back
                // already returned quantity to get the original quantity
                $alreadyReturned = ReturnItem::where('sale_item_id', $item['sale_item_id'])
                    ->sum('quantity');

                // Original quantity = current quantity + already returned quantity
                $originalQuantity = $saleItem->quantity + $alreadyReturned;
                $remainingQty = $originalQuantity - $alreadyReturned;

                if ($item['quantity'] > $remainingQty) {
                    throw new \Exception("Cannot return {$item['quantity']} units. Only {$remainingQty} units available for return (Original: {$originalQuantity}, Already returned: {$alreadyReturned}).");
                }

                // Calculate return amount using the unit price from sale item (already discounted)
                $returnAmount = $item['quantity'] * $saleItem->unit_price;
                $returnBillData['totals']['return_amount'] += $returnAmount;

                // Get product for stock update
                $returnedProduct = Product::find($item['product_id']);

                // Use the cost_price from the original sale item (if available) or product's current cost
                $originalCostPrice = $saleItem->cost_price > 0 ? $saleItem->cost_price : $returnedProduct->cost_price;

                // Calculate proportional discount for returned quantity
                // Use current quantity (before this return) to calculate per-unit discount
                $currentQty = $saleItem->quantity;
                $perUnitDiscount = $currentQty > 0 ? ($saleItem->discount / $currentQty) : 0;
                $proportionalDiscount = $perUnitDiscount * $item['quantity'];

                // Calculate the original selling price (before discount) for this item
                // unit_price is already discounted, so: original_price = unit_price + per_unit_discount
                $originalSellingPrice = $saleItem->unit_price + $perUnitDiscount;

                // The return amount based on original selling price (before discount)
                $grossReturnAmount = $item['quantity'] * $originalSellingPrice;

                // Create NEGATIVE quantity sale item to reverse the original sale
                // Store the discounted unit_price for consistency
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => -$item['quantity'], // NEGATIVE quantity
                    'unit_price' => $saleItem->unit_price, // Discounted unit price
                    'cost_price' => $originalCostPrice, // Original cost price
                    'total_price' => -$returnAmount, // NEGATIVE amount (discounted)
                    'discount' => -$proportionalDiscount, // NEGATIVE discount being reversed
                ]);

                // Update sale totals to reflect the negative line
                // total_amount is already net of discount, so we subtract the discounted return amount
                $sale->total_amount -= $returnAmount;
                $sale->total_cost -= ($item['quantity'] * $originalCostPrice);
                $sale->discount -= $proportionalDiscount; // Reduce the tracked discount
                $sale->save();

                // Increase stock for returned product
                $returnedProduct->update([
                    'stock_quantity' => $returnedProduct->stock_quantity + $item['quantity']
                ]);

                // Create stock transaction
                StockTransaction::create([
                    'product_id' => $item['product_id'],
                    'transaction_type' => 'Returned',
                    'quantity' => $item['quantity'],
                    'transaction_date' => $item['return_date'],
                    'supplier_id' => $returnedProduct->supplier_id ?? null,
                ]);

                // Adjust employee commissions
                if ($sale->employee_id) {
                    $this->adjustEmployeeCommissions($sale, $saleItem, $item, $returnedProduct);
                }

                // Create return item record with NO discount
                ReturnItem::create([
                    'sale_id' => $item['sale_id'],
                    'sale_item_id' => $item['sale_item_id'],
                    'customer_id' => $sale->customer_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'],
                    'unit_price' => $saleItem->unit_price, // Use discounted price from sale item
                    'total_price' => $returnAmount,
                    'discount' => 0, // No discount on returns
                    'return_date' => $item['return_date'],
                    'return_type' => $item['return_type'],
                    'employee_id' => $sale->employee_id,
                    'original_quantity' => $saleItem->quantity,
                ]);

                // Add to return bill data
                $returnBillData['return_items'][] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $returnedProduct->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $saleItem->unit_price,
                    'total' => $returnAmount,
                    'return_type' => $item['return_type'],
                    'reason' => $item['reason'],
                ];
            }

            // For P2P returns, create a separate sale/bill with new products
            if ($hasP2P && !empty($validated['new_products'])) {
                $newProductsTotal = 0;

                // Generate unique order ID for return bill
                $returnOrderId = 'RTN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

                // Calculate total for new products
                foreach ($validated['new_products'] as $newProductData) {
                    $newProductTotal = $newProductData['quantity'] * $newProductData['selling_price'];
                    $newProductsTotal += $newProductTotal;
                }

                // Calculate total cost for new products
                $newProductsCost = 0;
                foreach ($validated['new_products'] as $newProductData) {
                    $newProduct = Product::find($newProductData['product_id']);
                    $newProductsCost += $newProductData['quantity'] * $newProduct->cost_price;
                }

                // Create a new Sale record for the return transaction (P2P bill)
                $returnSale = Sale::create([
                    'customer_id' => $originalSale->customer_id,
                    'employee_id' => $originalSale->employee_id,
                    'user_id' => $originalSale->user_id,
                    'order_id' => $returnOrderId,
                    'total_amount' => $newProductsTotal,
                    'discount' => 0,
                    'payment_method' => $originalSale->payment_method,
                    'sale_date' => now()->toDateString(),
                    'total_cost' => $newProductsCost,
                    'cash' => 0,
                    'custom_discount' => 0,
                ]);

                // Process new products and create sale items
                foreach ($validated['new_products'] as $newProductData) {
                    $newProduct = Product::find($newProductData['product_id']);

                    // Check stock availability
                    if ($newProduct->stock_quantity < $newProductData['quantity']) {
                        throw new \Exception("Insufficient stock for product: {$newProduct->name}. Available: {$newProduct->stock_quantity}");
                    }

                    $newProductTotal = $newProductData['quantity'] * $newProductData['selling_price'];
                    $returnBillData['totals']['new_product_amount'] += $newProductTotal;

                    // Calculate item discount (proportional if sale has discount)
                    $itemDiscount = 0;
                    if ($returnSale->discount > 0 && $newProductsTotal > 0) {
                        $itemDiscount = ($newProductTotal / $newProductsTotal) * $returnSale->discount;
                    }
                    $perUnitDiscount = $newProductData['quantity'] > 0 ? ($itemDiscount / $newProductData['quantity']) : 0;
                    $discountedUnitPrice = $newProductData['selling_price'] - $perUnitDiscount;
                    $itemFinalTotal = $newProductTotal - $itemDiscount;

                    // Create sale item for new product in return bill with cost price
                    $newSaleItem = SaleItem::create([
                        'sale_id' => $returnSale->id,
                        'product_id' => $newProduct->id,
                        'quantity' => $newProductData['quantity'],
                        'unit_price' => $discountedUnitPrice, // Store discounted unit price
                        'cost_price' => $newProduct->cost_price, // Store cost price of new product
                        'total_price' => $itemFinalTotal,
                        'discount' => $itemDiscount,
                    ]);

                    // Reduce stock for new product
                    $newProduct->update([
                        'stock_quantity' => $newProduct->stock_quantity - $newProductData['quantity']
                    ]);

                    // Create stock transaction
                    StockTransaction::create([
                        'product_id' => $newProduct->id,
                        'transaction_type' => 'Sold',
                        'quantity' => $newProductData['quantity'],
                        'transaction_date' => now(),
                        'supplier_id' => $newProduct->supplier_id ?? null,
                    ]);

                    // Add commission for new product
                    if ($returnSale->employee_id && $newProduct->category_id) {
                        $category = \App\Models\Category::find($newProduct->category_id);
                        if ($category && $category->commission > 0) {
                            $commissionAmount = EmployeeCommission::calculateCommission(
                                $newProductTotal,
                                $category->commission
                            );

                            EmployeeCommission::create([
                                'employee_id' => $returnSale->employee_id,
                                'sale_id' => $returnSale->id,
                                'sale_item_id' => $newSaleItem->id,
                                'product_id' => $newProduct->id,
                                'category_id' => $newProduct->category_id,
                                'commission_percentage' => $category->commission,
                                'product_price' => $newProductData['selling_price'],
                                'quantity' => $newProductData['quantity'],
                                'total_product_amount' => $newProductTotal,
                                'commission_amount' => $commissionAmount,
                                'commission_date' => now(),
                            ]);
                        }
                    }

                    // Add to return bill data
                    $returnBillData['new_products'][] = [
                        'product_id' => $newProduct->id,
                        'product_name' => $newProduct->name,
                        'quantity' => $newProductData['quantity'],
                        'unit_price' => $newProductData['selling_price'],
                        'total' => $newProductTotal,
                    ];
                }

                // Create P2P Return Transaction record
                foreach ($validated['return_items'] as $item) {
                    $returnedProduct = Product::find($item['product_id']);
                    $returnAmount = $item['quantity'] * $item['unit_price'];

                    foreach ($validated['new_products'] as $newProductData) {
                        $newProduct = Product::find($newProductData['product_id']);
                        $newProductTotal = $newProductData['quantity'] * $newProductData['selling_price'];

                        \App\Models\P2PReturnTransaction::create([
                            'original_sale_id' => $originalSale->id,
                            'return_sale_id' => $returnSale->id,
                            'customer_id' => $originalSale->customer_id,
                            'employee_id' => $originalSale->employee_id,
                            'transaction_type' => 'p2p',
                            'returned_product_id' => $returnedProduct->id,
                            'returned_quantity' => $item['quantity'],
                            'returned_unit_price' => $item['unit_price'],
                            'returned_total_amount' => $returnAmount,
                            'new_product_id' => $newProduct->id,
                            'new_product_quantity' => $newProductData['quantity'],
                            'new_product_unit_price' => $newProductData['selling_price'],
                            'new_product_total_amount' => $newProductTotal,
                            'net_amount' => $newProductTotal - $returnAmount,
                            'reason' => $item['reason'],
                            'return_date' => $item['return_date'],
                            'status' => 'completed',
                        ]);
                    }
                }
            }

            // Calculate net amount
            $returnBillData['totals']['net_amount'] =
                $returnBillData['totals']['new_product_amount'] - $returnBillData['totals']['return_amount'];

            DB::commit();

            // Prepare return sale data for print bill
            // For P2P: shows new products issued with net amount calculation
            // For Cash: can show return receipt details
            $returnSaleData = null;
            if ($returnSale) {
                $returnSale->load(['items.product.unit', 'customer', 'employee']);

                // For P2P returns, the bill should show the net amount (new products - return amount)
                $netAmount = $hasP2P ?
                    $returnBillData['totals']['new_product_amount'] - $returnBillData['totals']['return_amount'] :
                    $returnSale->total_amount;

                $returnSaleData = [
                    'id' => $returnSale->id,
                    'order_id' => $returnSale->order_id,
                    'total_amount' => $netAmount, // Use net amount for P2P returns
                    'payment_method' => $returnSale->payment_method,
                    'sale_date' => $returnSale->sale_date,
                    'customer' => $returnSale->customer,
                    'employee' => $returnSale->employee,
                    'return_amount' => $hasP2P ? $returnBillData['totals']['return_amount'] : 0, // Include return amount for P2P
                    'new_product_amount' => $hasP2P ? $returnBillData['totals']['new_product_amount'] : 0, // Include new product amount
                    'items' => $returnSale->items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'selling_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                            'unit' => $item->product->unit,
                            'discount' => 0,
                            'apply_discount' => false,
                        ];
                    }),
                ];
            }

            // Prepare cash return receipt data (for cash-only returns)
            $cashReturnData = null;
            if (!$hasP2P && $originalSale && count($returnBillData['return_items']) > 0) {
                $originalSale->load(['customer', 'employee']);

                // Format return items for receipt display
                $formattedReturnItems = collect($returnBillData['return_items'])->map(function($item) {
                    return [
                        'id' => $item['product_id'],
                        'name' => $item['product_name'] . ' (RETURN)',
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'selling_price' => $item['unit_price'],
                        'total_price' => $item['total'],
                        'discount' => 0,
                        'apply_discount' => false,
                    ];
                })->toArray();

                $cashReturnData = [
                    'id' => $originalSale->id,
                    'order_id' => $originalSale->order_id . '-RETURN',
                    'total_amount' => $returnBillData['totals']['return_amount'],
                    'payment_method' => 'Cash Return',
                    'sale_date' => now()->toDateString(),
                    'customer' => $originalSale->customer,
                    'employee' => $originalSale->employee,
                    'return_items' => $formattedReturnItems,
                    'original_order_id' => $originalSale->order_id,
                ];
            }

            return response()->json([
                'message' => 'Return processed successfully!',
                'success' => true,
                'return_bill_data' => $returnBillData,
                'original_sale_id' => $originalSale ? $originalSale->id : null,
                'return_sale_id' => $returnSale ? $returnSale->id : null,
                'return_order_id' => $returnSale ? $returnSale->order_id : null,
                'return_sale_data' => $returnSaleData,
                'cash_return_data' => $cashReturnData,
                'is_p2p' => $hasP2P,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'An error occurred while processing the return.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Adjust employee commissions for returned items
     */
    private function adjustEmployeeCommissions($sale, $saleItem, $returnData, $returnedProduct)
    {
        // Find commission for this specific sale item
        $commission = EmployeeCommission::where('sale_id', $sale->id)
            ->where('sale_item_id', $saleItem->id)
            ->first();

        if (!$commission) {
            return; // No commission to adjust
        }

        // Calculate the new quantity after THIS specific return
        $currentReturnQty = $returnData['quantity'];
        $newQuantity = $commission->quantity - $currentReturnQty;

        if ($newQuantity <= 0) {
            // Fully returned, delete commission
            $commission->delete();
        } else {
            // Partially returned, recalculate commission for remaining quantity
            $newTotalAmount = $commission->product_price * $newQuantity;
            $newCommissionAmount = EmployeeCommission::calculateCommission(
                $newTotalAmount,
                $commission->commission_percentage
            );

            $commission->update([
                'quantity' => $newQuantity,
                'total_product_amount' => $newTotalAmount,
                'commission_amount' => $newCommissionAmount,
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ReturnItem $returnItem)
    {
        return response()->json($returnItem->load(['sale', 'customer', 'product', 'newProduct', 'employee']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReturnItem $returnItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReturnItem $returnItem)
    {
        //
    }
}
