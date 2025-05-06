<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('password');
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->string('address');
            $table->enum('role', ['USER', 'ADMIN']);
            $table->boolean('is_partner')->default(false);
            $table->string('avatar_url')->nullable();
            $table->dateTime('join_date');
            $table->decimal('client_rating', 3, 2)->default(0);
            $table->integer('client_reviews')->default(0);
            $table->decimal('partner_rating', 3, 2)->default(0);
            $table->integer('partner_reviews')->default(0);
            $table->double('longitude')->nullable();
            $table->double('latitude')->nullable();
            $table->foreignId('city_id')->constrained('city')->onDelete('cascade');

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};

