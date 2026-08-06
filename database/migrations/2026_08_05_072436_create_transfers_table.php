<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('from_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('to_customer_id')->constrained('customers')->cascadeOnDelete();

            $table->string('reference_no')->unique();

            $table->decimal('amount', 18, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
