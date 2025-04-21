<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Class   CreateListingTable  extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('city_id')->constrained('city')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->decimal('price_per_day', 8, 2);
            $table->enum('status', ['active', 'archived', 'inactive'])->default('active');
            $table->boolean('is_premium')->default(false);
            $table->dateTime('premium_start_date')->nullable();
            $table->dateTime('premium_end_date')->nullable();
            $table->foreignId('category_id')->constrained('category')->onDelete('cascade');
            $table->dateTime('created_at');
            $table->boolean('delivery_option')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing');
    }
};
