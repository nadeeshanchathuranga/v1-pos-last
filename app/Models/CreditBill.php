<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'order_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(CreditBillPayment::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    // Overdue scope removed - due dates not used

    // Methods to handle payment updates
    public function updatePaymentAmounts()
    {
        // Calculate total paid from all payments
        $totalPaid = $this->payments()->sum('payment_amount');
        
        // Update amounts
        $this->paid_amount = $totalPaid;
        $this->remaining_amount = max(0, $this->total_amount - $totalPaid);
        
        // Update payment status
        if ($this->remaining_amount <= 0) {
            $this->payment_status = 'paid';
        } elseif ($totalPaid > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'pending';
        }
        
        $this->save();
        
        return $this;
    }

    public function addPayment($amount, $paymentMethod = 'cash', $notes = null, $userId = null)
    {
        // Validate payment amount
        if ($amount <= 0 || $amount > $this->remaining_amount) {
            throw new \Exception('Invalid payment amount');
        }

        // Create payment record
        $payment = $this->payments()->create([
            'payment_amount' => $amount,
            'payment_date' => now(),
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'user_id' => $userId
        ]);

        // Update amounts (this will be handled by the payment model events)
        return $payment;
    }
}
