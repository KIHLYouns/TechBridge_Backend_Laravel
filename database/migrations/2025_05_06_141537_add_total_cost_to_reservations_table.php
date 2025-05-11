<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalCostToReservationsTable extends Migration
{
    

    public function down()
    {
        Schema::table('reservation', function (Blueprint $table) {
            $table->dropColumn('total_cost');
        });
    }
}
