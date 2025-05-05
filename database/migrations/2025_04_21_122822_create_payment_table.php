<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 8, 2);
            $table->decimal('commission_fee', 8, 2);
            $table->decimal('partner_payout', 8, 2);
            $table->dateTime('payment_date');
            $table->enum('status', ['completed', 'refunded']);
            $table->enum('payment_method', ['credit_card', 'paypal']);
            $table->string('transaction_id');
            $table->foreignId('client_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('reservation_id')->constrained('reservation')->onDelete('cascade');
            $table->foreignId('partner_id')->constrained('user')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
