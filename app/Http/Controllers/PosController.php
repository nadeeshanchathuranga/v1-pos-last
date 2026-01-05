<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Color;
use App\Models\Coupon;
use App\Models\CreditBill;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Size;
use App\Models\ReturnItem;
use App\Models\StockTransaction;
use App\Models\Employee;
use App\Models\EmployeeCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PosController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Cashier', 'Operator'])) {
            abort(403, 'Unauthorized');
        }
        $sales = Sale::with('customer','employee')->get();
        $saleItems  = SaleItem::with('product')->orderBy('created_at', 'desc')->get();

        $allcategories = Category::with('parent')->get()->map(function ($category) {
            $category->hierarchy_string = $category->hierarchy_string; // Access it
            return $category;
        });
        $colors = Color::orderBy('created_at', 'desc')->get();
        $sizes = Size::orderBy('created_at', 'desc')->get();
        $allemployee = Employee::orderBy('created_at', 'desc')->get();
    $products = Product::with('unit')->orderBy('name', 'asc')->get(); // Add products in ascending order
    $suppliers = \App\Models\Supplier::orderBy('created_at', 'desc')->get();


        // Render the page for GET requests
        return Inertia::render('Pos/Index', [
            'product' => null,
            'error' => null,
            'loggedInUser' => Auth::user(),
            'allcategories' => $allcategories,
            'allemployee' => $allemployee,
            'colors' => $colors,
            'sizes' => $sizes,
            'sales'=> $sales,
            'saleItems' => $saleItems,
            'products' => $products, // Add products to props
            'suppliers' => $suppliers,
        ]);
    }

    public function getProduct(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Cashier', 'Operator'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'barcode' => 'required',
        ]);

        // First try exact match on barcode/code
        $product = Product::where('barcode', $request->barcode)
            ->orWhere('code', $request->barcode)
            ->first();

        // If no exact match, try flexible search (for manual product search)
        if (!$product) {
            $product = Product::flexibleSearch($request->barcode)->first();
        }

        return response()->json([
            'product' => $product,
            'error' => $product ? null : 'Product not found',
        ]);
    }

    public function getCoupon(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Cashier', 'Operator'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate(
            ['code' => 'required|string'],
            ['code.required' => 'The coupon code missing.', 'code.string' => 'The coupon code must be a valid string.']
        );

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json(['error' => 'Invalid coupon code.']);
        }

        if (!$coupon->isValid()) {
            return response()->json(['error' => 'Coupon is expired or has been fully used.']);
        }

        return response()->json(['success' => 'Coupon applied successfully.', 'coupon' => $coupon]);
    }

    public function submit(Request $request)
    {

        if (!Gate::allows('hasRole', ['Admin', 'Cashier', 'Operator'])) {
            abort(403, 'Unauthorized');
        }
        // Combine countryCode and contactNumber to create the phone field


        $customer = null;

        $products = $request->input('products');
      
        $returnItems = $request->input('return_items', []);

        $totalAmount = collect($products)->reduce(function ($carry, $product) {
            return $carry + ($product['quantity'] * $product['selling_price']);
        }, 0);

        $totalCost = collect($products)->reduce(function ($carry, $product) {
            return $carry + ($product['quantity'] * $product['cost_price']);
        }, 0);
        // $returnItems = ReturnItem::with('product', 'customer', 'sale')->orderBy('created_at', 'desc')->get();
        $productDiscounts = collect($products)->reduce(function ($carry, $product) {
            if (isset($product['discount']) && $product['discount'] > 0 && isset($product['apply_discount']) && $product['apply_discount'] != false) {
                $discountAmount = ($product['selling_price'] - $product['discounted_price']) * $product['quantity'];
                return $carry + $discountAmount;
            }
            return $carry;
        }, 0);

        $totalReturnAmount = collect($returnItems)->reduce(function ($carry, $item) {
            return $carry + ($item['quantity'] * $item['unit_price']);
        }, 0);

      
        // Get coupon discount if applied
        $couponDiscount = isset($request->input('appliedCoupon')['discount']) ?
            floatval($request->input('appliedCoupon')['discount']) : 0;


        // Calculate total combined discount
        $totalDiscount = $productDiscounts + $couponDiscount ;

        // Calculate custom discount
        $customDiscount = floatval($request->input('custom_discount', 0));
        $customDiscountType = $request->input('custom_discount_type', 'percent');
        
        $customValue = 0;
        if ($customDiscountType === 'percent') {
            $customValue = ($totalAmount * $customDiscount) / 100;
        } else if ($customDiscountType === 'fixed') {
            $customValue = $customDiscount;
        }

        // Calculate base total after discounts
        $baseTotal = $totalAmount - $totalDiscount - $customValue - $totalReturnAmount;

        // Add Koko surcharge if payment method is Koko
        $finalTotal = $baseTotal;
        if ($request->input('paymentMethod') === 'Koko') {
            $kokoSurcharge = $baseTotal * 0.115; // 11.5% surcharge
            $finalTotal = $baseTotal + $kokoSurcharge;
        }

        DB::beginTransaction(); // Start a transaction

        try {
            // Save the customer data to the database
            if ($request->input('customer.contactNumber') || $request->input('customer.name') || $request->input('customer.email')) {

                $phone = $request->input('customer.countryCode') . $request->input('customer.contactNumber');
                $customer = Customer::where('email', $request->input('customer.email'))->first();
                $customer1 = Customer::where('phone', $phone)->first();

                if ($customer) {
                    $email = '';
                } else {
                    $email = $request->input('customer.email');
                }

                if ($customer1) {
                    $phone = '';
                }

                if (!empty($phone) || !empty($email) || !empty($request->input('customer.name'))) {
                    $customer = Customer::create([
                        'name' => $request->input('customer.name'),
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $request->input('customer.address', ''), // Optional address
                        'member_since' => now()->toDateString(), // Current date
                        'loyalty_points' => 0, // Default value
                    ]);
                }
            }

            // Create the sale record
            $sale = Sale::create([
                'customer_id' => $customer ? $customer->id : null, // Nullable customer_id
                'employee_id' => $request->input('employee_id'),
                'user_id' => $request->input('userId'), // Logged-in user ID
                'order_id' => $request->input('orderid'),
                'total_amount' => $finalTotal, // Total amount including Koko surcharge if applicable
                'discount' => $totalDiscount, // Default discount to 0 if not provided
                'total_cost' => $totalCost,
                'payment_method' => $request->input('paymentMethod'), // Payment method from the request
                'sale_date' => now()->toDateString(), // Current date
                'cash' => $request->input('cash'),
                'custom_discount' => $customValue, // Store calculated custom discount value, not raw input
                

            ]);

            // Calculate the discount per item proportionally based on their total price
            $itemDiscountMap = [];
            if ($totalDiscount > 0 && $totalAmount > 0) {
                foreach ($products as $product) {
                    $itemTotal = $product['quantity'] * $product['selling_price'];
                    $itemDiscountShare = ($itemTotal / $totalAmount) * $totalDiscount;
                    $itemDiscountMap[$product['id']] = $itemDiscountShare;
                }
            }

            foreach ($products as $product) {
                // Check stock before saving sale items
                $productModel = Product::find($product['id']);
                if ($productModel) {
                    $newStockQuantity = $productModel->stock_quantity - $product['quantity'];

                    // Prevent stock from going negative
                    if ($newStockQuantity < 0) {
                        // Rollback transaction and return error
                        DB::rollBack();
                        return response()->json([
                            'message' => "Insufficient stock for product: {$productModel->name}
                            ({$productModel->stock_quantity} available)",
                        ], 423);
                    }

                    if ($productModel->expire_date && now()->greaterThan($productModel->expire_date)) {
                        // Rollback transaction and return error
                        DB::rollBack();
                        return response()->json([
                            'message' => "The product '{$productModel->name}' has expired (Expiration Date: {$productModel->expire_date->format('Y-m-d')}).",
                        ], 423); // HTTP 422 Unprocessable Entity
                    }

                    // Calculate item total and discount
                    $itemTotal = $product['quantity'] * $product['selling_price'];
                    $itemDiscount = $itemDiscountMap[$product['id']] ?? 0;
                    $perUnitDiscount = $product['quantity'] > 0 ? ($itemDiscount / $product['quantity']) : 0;
                    $discountedUnitPrice = $product['selling_price'] - $perUnitDiscount;
                    $itemFinalTotal = $itemTotal - $itemDiscount;

                    // Create sale item with discounted unit price and cost price
                    $saleItem = SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product['id'],
                        'quantity' => $product['quantity'],
                        'unit_price' => $discountedUnitPrice, // Store discounted unit price
                        'cost_price' => $productModel->cost_price, // Store cost price at time of sale
                        'total_price' => $itemFinalTotal,
                        'discount' => $itemDiscount, // Total discount for this item
                    ]);

                    StockTransaction::create([
                        'product_id' => $product['id'],
                        'transaction_type' => 'Sold',
                        'quantity' => $product['quantity'],
                        'transaction_date' => now(),
                        'supplier_id' => $productModel->supplier_id ?? null,
                    ]);

                    // Update stock quantity
                    $productModel->update([
                        'stock_quantity' => $newStockQuantity,
                    ]);

                    // Calculate and record commission if employee is selected and category has commission
                    if ($request->input('employee_id') && $productModel->category_id) {
                        $category = \App\Models\Category::find($productModel->category_id);
                        if ($category && $category->commission > 0) {
                            $totalProductAmount = $product['selling_price'] * $product['quantity'];
                            $commissionAmount = EmployeeCommission::calculateCommission(
                                $totalProductAmount,
                                $category->commission
                            );

                            EmployeeCommission::create([
                                'employee_id' => $request->input('employee_id'),
                                'sale_id' => $sale->id,
                                'sale_item_id' => $saleItem->id,
                                'product_id' => $product['id'],
                                'category_id' => $productModel->category_id,
                                'commission_percentage' => $category->commission,
                                'product_price' => $product['selling_price'],
                                'quantity' => $product['quantity'],
                                'total_product_amount' => $totalProductAmount,
                                'commission_amount' => $commissionAmount,
                                'commission_date' => now(),
                            ]);
                        }
                    }
                }
            }
                foreach ($returnItems as $item) {
                    // You may want to validate the sale_item_id and product_id exist
               
                    $unitPrice = $item['unit_price'] ?? 0;
                    $quantity = $item['quantity'] ?? 0;
                    $totalPrice = $quantity * $unitPrice;
                    ReturnItem::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $customer ? $customer->id : null,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'reason' => $item['reason'] ?? '',
                        'unit_price' =>$unitPrice,
                        'return_date' => $item['return_date'] ?? now()->toDateString(),
                        'total_price' => $totalPrice,
                      
                    ]);
                    

                         // For return items, we need to increase stock
                $returnedProduct = Product::find($item['product_id']);
                if ($returnedProduct) {
                    $returnedProduct->update([
                        'stock_quantity' => $returnedProduct->stock_quantity + $item['quantity']
                    ]); 

                    StockTransaction::create([
                        'product_id' => $item['product_id'],
                        'transaction_type' => 'Returned',
                        'quantity' => $item['quantity'],
                        'transaction_date' => now(),
                        'supplier_id' => $returnedProduct->supplier_id ?? null,
                    ]);

       
                }

            }

            // Create credit bill entry if payment method is "credit bill"
            if ($request->input('paymentMethod') === 'credit bill') {
                try {
                    CreditBill::create([
                        'sale_id' => $sale->id,
                        'customer_id' => $customer ? $customer->id : null,
                        'order_id' => $request->input('orderid'),
                        'total_amount' => $finalTotal,
                        'paid_amount' => 0,
                        'remaining_amount' => $finalTotal,
                        'payment_status' => 'pending',
                        'due_date' => now()->addDays(30), // Default 30 days from now
                        'notes' => 'Auto-generated from POS sale',
                    ]);
                    \Log::info('Credit bill created successfully for sale ID: ' . $sale->id);
                } catch (\Exception $e) {
                    \Log::error('Failed to create credit bill for sale ID: ' . $sale->id . '. Error: ' . $e->getMessage());
                    throw $e; // Re-throw to trigger transaction rollback
                }
            }

            DB::commit();

            return response()->json([
                'message'=>'Sale recorded successfully!', 
                'sale_id' => $sale->id,],201);
            
            }catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    'message' => 'An error occurred while processing the sale.',
                    'error' => $e->getMessage(),
                ], 500);
            
            }
        }
    }
            // if (!empty($returnItems)) {
            //     $totalReturnAmount = collect($returnItems)->reduce(function ($carry, $item) {
            //         return $carry + ($item['quantity'] * $item['unit_price']);
            //     }, 0);
            
            //     $sale->total_amount -= $totalReturnAmount;
            //     $sale->save();
            // }

            // Commit the transaction
            

        //     return response()->json(['message' => 'Sale recorded successfully!'], 201);
        // } catch (\Exception $e) {
        //     // Rollback the transaction if any error occurs
        //     DB::rollBack();

        //     return response()->json([
        //         'message' => 'An error occurred while processing the sale.',
        //         'error' => $e->getMessage(),
        //     ], 500);
        // }

        // return response()->json([
        //     'message' => 'Customer details saved successfully!',
        //     'data' => $customer,
        // ], 201);
        

