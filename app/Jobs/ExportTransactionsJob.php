<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ExportTransactionsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // max 5 minutes
    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $filePath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fullPath = Storage::path($this->filePath);

        $handle = fopen($fullPath, 'x');

        fputcsv($handle, [
            'Customer', 
            'Performed By', 
            'Type', 
            'Reference No.', 
            'Amount', 
            'Balance Before', 
            'Balance After', 
            'Created At'
        ]);

        Transaction::with(['customer', 'performedBy'])->orderBy('created_at')->each(function ($transaction) use ($handle) {
            fputcsv($handle, [
                $transaction->customer->name,
                $transaction->performed_by,
                $transaction->type,
                $transaction->reference_no,
                $transaction->amount,
                $transaction->balance_before,
                $transaction->balance_after,
                $transaction->created_at,
            ]);
        });

        fclose($handle);
    }
}
