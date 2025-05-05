<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCityTable extends Migration
{
    public function up(): void
    {
        Schema::create('city', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city');
    }
}

