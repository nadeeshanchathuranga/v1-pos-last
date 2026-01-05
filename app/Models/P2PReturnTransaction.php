<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class P2PReturnTransaction extends Model
{
    use HasFactory;

    protected $table = 'p2p_return_transactions';

    protected $fillable = [
        'original_sale_id',
        'return_sale_id',
        'customer_id',
        'employee_id',
        'transaction_type',
        'returned_product_id',
        'returned_quantity',
        'returned_unit_price',
        'returned_total_amount',
        'new_product_id',
        'new_product_quantity',
        'new_product_unit_price',
        'new_product_total_amount',
        'net_amount',
        'reason',
        'return_date',
        'status',
    ];

    protected $casts = [
        'return_date' => 'date',
        'returned_unit_price' => 'decimal:2',
        'returned_total_amount' => 'decimal:2',
        'new_product_unit_price' => 'decimal:2',
        'new_product_total_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    // Relationships
    public function originalSale()
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }

    public function returnSale()
    {
        return $this->belongsTo(Sale::class, 'return_sale_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function returnedProduct()
    {
        return $this->belongsTo(Product::class, 'returned_product_id');
    }

    public function newProduct()
    {
        return $this->belongsTo(Product::class, 'new_product_id');
    }

    // Helper methods
    public function isP2P()
    {
        return $this->transaction_type === 'p2p';
    }

    public function isCash()
    {
        return $this->transaction_type === 'cash';
    }

    public function calculateNetAmount()
    {
        $returned = $this->returned_total_amount ?? 0;
        $newProduct = $this->new_product_total_amount ?? 0;
        return $newProduct - $returned;
    }
}
