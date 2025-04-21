<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilityTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('availability', function (Blueprint $table) {
           
            $table->foreignId('listing_id')->constrained('listing')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability');
    }
};
