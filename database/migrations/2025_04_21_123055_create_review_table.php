<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('review', function (Blueprint $table) {
           
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservation')->onDelete('cascade');
            $table->integer('rating');
            $table->text('comment');
            $table->boolean('is_visible')->default(true);
            $table->dateTime('created_at');
            $table->enum('type', ['forGuest', 'forClient', 'forPartner']);
            $table->foreignId('reviewer_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('reviewee_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('listing_id')->constrained('listing')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review');
    }
};
