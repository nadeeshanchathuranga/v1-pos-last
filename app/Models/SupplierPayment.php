<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;

      protected $fillable = [
        'supplier_id',
        'total_cost',
        'pay',
        'status', 
    ];


      public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Accessor for balance if you want dynamic calculation
     */
    public function getBalanceAttribute()
    {
        return $this->total_cost - $this->pay;
    }

    /**
     * Mark payment complete if fully paid
     */
    public function markComplete()
    {
        if ($this->balance <= 0) {
            $this->status = 'complete';
            $this->save();
        }
    }

}
