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
            $table->foreignId('partner_id')->constrained('user')->onDelete('cascade');
            $table->decimal('amount', 8, 2);
            $table->dateTime('payment_date');
            $table->enum('status', ['pending', 'completed', 'failed']);
            $table->foreignId('listing_id')->constrained('listing')->onDelete('cascade');
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
