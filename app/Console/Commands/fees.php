<?php

namespace App\Console\Commands;

use App\Http\Controllers\FeeController;
use App\Http\Controllers\TransactionController;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use League\Csv\Reader;


class fees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:calculate {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = storage_path('app/' . $this->argument('file'));

        $csv = Reader::createFromPath($file, 'r');

        $csv->setHeaderOffset(0);

        $csvData = $csv->getRecords();

        $commissions = [];

        TransactionController::insertTransactions($csvData);

        $transactions = [];

        $usersTransactionsCountPerWeek = [];

        Transaction::whereNull('commission')->chunk(200, function ($chunks) use (&$transactions) {
            foreach ($chunks as $chunk) {
                $transactions[] = $chunk;
            }
        });

        foreach ($transactions as $index => $transaction) {
            if ($transaction->operation_type === 'deposit') {
                $commissions[] = FeeController::getDepositCommission($transaction->operation_amount);
            } else if ($transaction->operation_type === 'withdraw') {
                if ($transaction->user_type === 'business') {
                    $commissions[] = FeeController::getWithdrawCommissionForBusinessUser($transaction->operation_amount);
                } else if ($transaction->user_type === 'private') {

                    $usersTransactionsCountPerWeek[$transaction->user_id][Carbon::parse($transaction->date)->weekOfYear][] = $transaction->id;

                    $weeklyWithdrawals = Transaction::take(count($usersTransactionsCountPerWeek[$transaction->user_id][Carbon::parse($transaction->date)->weekOfYear]))
                        ->whereNull('commission')
                        ->where('user_id', $transaction->user_id)
                        ->where('user_type', 'private')
                        ->where('operation_type', 'withdraw')
                        ->where('date', ">=", Carbon::parse($transaction->date)->startOfWeek())
                        ->where('date', "<=", Carbon::parse($transaction->date))
                        ->get();

                    $commissions[] = FeeController::getWithdrawCommissionForPrivateUser($transaction, $weeklyWithdrawals);
                }
            }
        }

        TransactionController::insertCommissions($commissions);

        dd($commissions);
    }
}
