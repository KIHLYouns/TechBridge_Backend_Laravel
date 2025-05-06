<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalCostToReservationsTable extends Migration
{
    public function up()
    {
        Schema::table('reservation', function (Blueprint $table) {
            $table->decimal('total_cost', 8, 2)->nullable();
        });
    }

    public function down()
    {
        Schema::table('reservation', function (Blueprint $table) {
            $table->dropColumn('total_cost');
        });
    }
}
