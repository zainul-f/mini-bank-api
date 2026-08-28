<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Transfer;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard data successfully loaded!',
            'data' => [
                'total_customers' => Customer::count(),
                'total_balance' => Customer::sum('balance'),
                'total_transactions' => Transaction::count(),
                'total_deposit' => Transaction::where('type', 'deposit')->sum('amount'),
                'total_withdraw' => Transaction::where('type', 'withdraw')->sum('amount'),
                'total_transfer' => Transfer::sum('amount'),
            ],
        ]);
    }
}
