<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public static function insertTransactions($transactions): void
    {
        foreach ($transactions as $transaction) {
            $newTransaction = new Transaction;

            $newTransaction->user_id = $transaction['user_id'];
            $newTransaction->user_type = $transaction['user_type'];
            $newTransaction->date = $transaction['date'];
            $newTransaction->operation_type = $transaction['operation_type'];
            $newTransaction->operation_amount = $transaction['operation_amount'];
            $newTransaction->operation_currency = $transaction['operation_currency'];

            $newTransaction->save();
        }
    }

    public static function insertCommissions($commissions)
    {
        $transactions = Transaction::whereNull('commission')->get();

        foreach ($commissions as $index => $commission) {

            $transactions[$index]->commission = $commission;

            $transactions[$index]->save();
        }
    }
}
