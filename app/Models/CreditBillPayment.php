<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditBillPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_bill_id',
        'payment_amount',
        'payment_date',
        'payment_method',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Relationships
    public function creditBill()
    {
        return $this->belongsTo(CreditBill::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('payment_date', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Boot method to add event listeners
    protected static function boot()
    {
        parent::boot();

        // When payment is created, update credit bill amounts
        static::created(function ($payment) {
            if ($payment->creditBill) {
                $payment->creditBill->updatePaymentAmounts();
            }
        });

        // When payment is updated, update credit bill amounts
        static::updated(function ($payment) {
            if ($payment->creditBill) {
                $payment->creditBill->updatePaymentAmounts();
            }
        });

        // When payment is deleted, update credit bill amounts
        static::deleted(function ($payment) {
            if ($payment->creditBill) {
                $payment->creditBill->updatePaymentAmounts();
            }
        });
    }
}
