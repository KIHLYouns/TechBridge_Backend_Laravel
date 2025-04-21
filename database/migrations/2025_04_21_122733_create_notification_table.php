<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

 class createNotificationTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification', function (Blueprint $table) {
           
            $table->id();
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->enum('type', ['reservation', 'review', 'reminder', 'system']);
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};
