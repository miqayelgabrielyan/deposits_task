<?php

namespace App\Http\Controllers;


class FeeController extends Controller
{

    public static function roundUpToDecimal($number): float|int
    {
        $numberStr = strval($number);

        $decimalPlaces = 0;
        if (str_contains($numberStr, '.')) {
            $parts = explode('.', $numberStr);
            foreach (str_split($parts[1]) as $index => $part) {
                if ($part != 0) {
                    $decimalPlaces = $index + 1;
                    break;
                }
            }
        }

        $multiplier = pow(10, $decimalPlaces);
        return ceil($number * $multiplier) / $multiplier;
    }

    public static function convertToEUR($amount, $currency): float
    {
        return match ($currency) {
            'USD' => $amount / 1.1497,
            'JPY' => $amount / 129.53,
            default => $amount,
        };
    }

    public static function getDepositCommission($amount): float|int
    {
        return self::roundUpToDecimal($amount * 0.0003);
    }

    public static function getWithdrawCommissionForBusinessUser($amount): float|int
    {
        return self::roundUpToDecimal($amount * 0.005);
    }

    public static function getWithdrawCommissionForPrivateUser($transaction, $totalWithdraws): float|int
    {
        $freeWeeklyWithdrawalAmount = 1000.00;
        $freeWithdrawalLimitPerWeek = 3;
        $commissionRate = 0.003;
        $totalThisWeek = 0;

        $userWithdraw = self::convertToEUR($transaction->operation_amount, $transaction->operation_currency);
        foreach ($totalWithdraws as $withdraw) {
            $totalThisWeek += self::convertToEUR($withdraw->operation_amount, $withdraw->operation_currency);
        }

        $totalThisWeek -= $userWithdraw;

        if (count($totalWithdraws) <= $freeWithdrawalLimitPerWeek + 1) {
            if ($totalThisWeek + $userWithdraw <= $freeWeeklyWithdrawalAmount) {
                return 0;
            } else if ($totalThisWeek < $freeWeeklyWithdrawalAmount) {
                return self::roundUpToDecimal(($userWithdraw - ($freeWeeklyWithdrawalAmount - $totalThisWeek)) * $commissionRate);
            } else {
                return self::roundUpToDecimal($userWithdraw * $commissionRate);
            }
        } else {
            return self::roundUpToDecimal($userWithdraw * $commissionRate);
        }
    }
}
