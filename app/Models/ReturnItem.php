<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'sale_item_id',
        'customer_id',
        'product_id',
        'quantity',
        'reason',
        'unit_price',
        'total_price',
        'return_date',
        'return_type',
        'new_product_id',
        'employee_id',
        'new_product_amount',
        'original_quantity',
        'discount',
    ];

    protected $casts = [
        'return_date' => 'date',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'new_product_amount' => 'decimal:2',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id','id');
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id','id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id','id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id','id');
    }

    public function newProduct()
    {
        return $this->belongsTo(Product::class, 'new_product_id','id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id','id');
    }

    /**
     * Get remaining quantity available for returns for a specific sale item
     */
    public static function getRemainingQuantity($saleItemId)
    {
        $saleItem = SaleItem::find($saleItemId);
        if (!$saleItem) {
            return 0;
        }

        $totalReturned = self::where('sale_item_id', $saleItemId)
            ->sum('quantity');

        return $saleItem->quantity - $totalReturned;
    }
}
