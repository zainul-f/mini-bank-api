<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $admin = User::where('role', 'admin')->firstOrFail();

        $customers->each(function ($customer) use ($admin) {
            Transaction::factory()->count(10)->create([
                'customer_id' => $customer->id,
                'performed_by' => $admin->id,
            ]);
        });

        $customers->take(5)->each(function ($fromCustomer) use ($customers, $admin) {
            $toCustomer = $customers
                ->where('id', '!=', $fromCustomer->id)
                ->random();

            $transfer = Transfer::factory()->create([
                'from_customer_id' => $fromCustomer->id,
                'to_customer_id' => $toCustomer->id,
            ]);

            Transaction::factory()->create([
                'customer_id' => $fromCustomer->id,
                'performed_by' => $admin->id,
                'type' => 'transfer_out',
                'reference_no' => $transfer->reference_no,
                'amount' => $transfer->amount,
            ]);

            Transaction::factory()->create([
                'customer_id' => $toCustomer->id,
                'performed_by' => $admin->id,
                'type' => 'transfer_in',
                'reference_no' => $transfer->reference_no,
                'amount' => $transfer->amount,
            ]);
        });
    }
}
