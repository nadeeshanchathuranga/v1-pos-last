<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'employee_id',
        'user_id',
        'order_id',
        'total_amount',
        'discount',
        'payment_method',
        'sale_date',
        'total_cost',
        'cash',
        'custom_discount',
    ];

    /**
     * Calculate the discount share for a sale item based on the sale's total discount
     * 
     * @param float $itemTotal The total price of the item (quantity * unit_price)
     * @param float $totalAmount The total amount before discount
     * @return float The proportional discount for this item
     */
    public function calculateItemDiscount($itemTotal, $totalAmount)
    {
        if ($this->discount > 0 && $totalAmount > 0) {
            return ($itemTotal / $totalAmount) * $this->discount;
        }
        return 0;
    }

    /**
     * Recalculate sale totals from all sale items (including negative quantity return items)
     * This ensures total_amount and total_cost are accurate after returns.
     */
    public function recalculateTotals()
    {
        $totalAmount = 0;
        $totalCost = 0;
        $totalDiscount = 0;

        foreach ($this->saleItems as $item) {
            // Negative quantities from returns will automatically subtract
            $totalAmount += $item->total_price;
            $totalDiscount += $item->discount;
            
            $itemCost = $item->cost_price > 0 ? $item->cost_price : ($item->product->cost_price ?? 0);
            $totalCost += $item->quantity * $itemCost;
        }

        $this->total_amount = $totalAmount;
        $this->total_cost = $totalCost;
        $this->discount = $totalDiscount;
        $this->save();

        return $this;
    }

    /**
     * Calculate net profit for this sale
     * profit = total_amount - total_cost
     */
    public function getNetProfitAttribute()
    {
        $totalCost = 0;
        foreach ($this->saleItems as $item) {
            $itemCost = $item->cost_price > 0 ? $item->cost_price : ($item->product->cost_price ?? 0);
            $totalCost += $item->quantity * $itemCost;
        }
        return $this->total_amount - $totalCost;
    }




    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id','id');
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class, 'order_id','id');
    }
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id','id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id','id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id','id');
    }
}
