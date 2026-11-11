<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid', 15, 2)->default(0);
            $table->decimal('change', 15, 2)->default(0);
            $table->string('payment_method', 30)->default('cash');
            $table->string('payment_status', 20)->default('paid');
            $table->string('payment_reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('payment_status');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
