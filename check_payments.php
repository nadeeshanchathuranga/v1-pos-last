<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CreditBillPayment;

echo "Credit Bill Payments count: " . CreditBillPayment::count() . "\n";

$payments = CreditBillPayment::with(['creditBill.customer', 'user'])->take(5)->get();

foreach ($payments as $payment) {
    echo "Payment ID: {$payment->id}, Amount: {$payment->payment_amount}, Customer: " . ($payment->creditBill->customer->name ?? 'N/A') . ", Date: {$payment->payment_date}\n";
}

echo "\nDone.\n";