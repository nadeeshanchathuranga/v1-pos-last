<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'sale_id',
        'sale_item_id',
        'product_id',
        'category_id',
        'commission_percentage',
        'product_price',
        'quantity',
        'total_product_amount',
        'commission_amount',
        'commission_date',
    ];

    protected $casts = [
        'commission_date' => 'datetime',
        'commission_percentage' => 'decimal:2',
        'product_price' => 'decimal:2',
        'total_product_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'id');
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * Calculate commission amount based on percentage and total amount
     */
    public static function calculateCommission($totalAmount, $commissionPercentage)
    {
        return round(($totalAmount * $commissionPercentage) / 100, 2);
    }
}
