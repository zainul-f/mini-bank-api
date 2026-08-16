<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Http\Requests\TransactionRequest;
use App\Http\Requests\TransferRequest;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::all();

        return response()->json([
            'success' => true,
            'message' => 'Data successfully loaded',
            'data' => $customers
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerStoreRequest $request)
    {
        $data = $request->validated();

        $customer = Customer::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Data Successfully created!',
            'data' => $customer
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json([
            'success' => true,
            'message' => 'Data successfully retrieved!',
            'data' => $customer,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerUpdateRequest $request, string $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Data successfully updated!',
            'data' => $customer,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
        return response()->json([
            'success' => true,
            'message' => 'Data has been deleted!',
        ], 200);
    }

    public function deposit(TransactionRequest $transactionRequest, string $id)
    {
        $amount = $transactionRequest->validated('amount');

        DB::beginTransaction();

        try {
            $customer = Customer::whereKey($id)->lockForUpdate()->firstOrFail();

            $before = $customer->balance;

            $customer->balance += $amount;
            $customer->save();

            Transaction::create([
                'customer_id' => $customer->id,
                'performed_by' => Auth::id(),
                'type' => 'deposit',
                'reference_no' => 'DEP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $customer->balance,
            ]);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Deposit successfully processed!',
                'data' => $customer
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function withdraw(TransactionRequest $transactionRequest, string $id)
    {
        $amount = $transactionRequest->validated('amount');

        DB::beginTransaction();

        try {
            $customer = Customer::whereKey($id)->lockForUpdate()->firstOrFail();

            $before = $customer->balance;

            if ($customer->balance < $amount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance'
                ], 422);
            }

            $customer->balance -= $amount;
            $customer->save();

            Transaction::create([
                'customer_id' => $customer->id,
                'performed_by' => Auth::id(),
                'type' => 'withdraw',
                'reference_no' => 'WD-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $customer->balance,
            ]);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Withdraw successfully processed!',
                'data' => $customer
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function transfer(TransferRequest $transferRequest, string $id)
    {
        $amount = $transferRequest->validated('amount');
        $toCustomerId = $transferRequest->validated('to_customer_id');

        if ($id === $toCustomerId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot transfer to the same customer'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $fromCustomer = Customer::whereKey($id)->lockForUpdate()->firstOrFail();
            $toCustomer = Customer::whereKey($toCustomerId)->lockForUpdate()->firstOrFail();

            if ($fromCustomer->balance < $amount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance'
                ], 422);
            }

            $fromBefore = $fromCustomer->balance;
            $toBefore = $toCustomer->balance;

            $fromCustomer->balance -= $amount;
            $fromCustomer->save();

            $toCustomer->balance += $amount;
            $toCustomer->save();

            $referenceNo = 'TF-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));

            Transaction::create([
                'customer_id' => $fromCustomer->id,
                'performed_by' => Auth::id(),
                'type' => 'transfer',
                'reference_no' => $referenceNo,
                'amount' => $amount,
                'balance_before' => $fromBefore,
                'balance_after' => $fromCustomer->balance,
            ]);

            Transaction::create([
                'customer_id' => $toCustomer->id,
                'performed_by' => Auth::id(),
                'type' => 'transfer',
                'reference_no' => $referenceNo,
                'amount' => $amount,
                'balance_before' => $toBefore,
                'balance_after' => $toCustomer->balance,
            ]);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer successfully processed!',
                'data' => [
                    'from' => $fromCustomer,
                    'to' => $toCustomer,
                ]
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function transactions(string $id)
    {
        $customer = Customer::findOrFail($id);
        
        $transactions = $customer->transactions()->latest()->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'Transactions history successfully loaded!',
            'data' => $transactions,
        ], 200);
    }
}
