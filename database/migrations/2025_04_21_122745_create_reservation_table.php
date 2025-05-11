<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservation', function (Blueprint $table) {
           
            $table->id();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->decimal('total_cost', 8, 2);
            $table->enum('status', ['pending', 'confirmed', 'ongoing', 'canceled', 'completed']);
            $table->string('contract_url');
            $table->dateTime('created_at');
            $table->boolean('delivery_option')->default(false);
            $table->foreignId('client_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('partner_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('listing_id')->constrained('listing')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation');
    }
};
