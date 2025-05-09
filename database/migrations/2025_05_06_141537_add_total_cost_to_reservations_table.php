<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTotalCostToReservationsTable extends Migration
{
    

<<<<<<< HEAD:database/migrations/2025_05_06_145046_add_total_cost_to_reservations_table.php
    
=======
    public function down()
    {
        Schema::table('reservation', function (Blueprint $table) {
            $table->dropColumn('total_cost');
        });
    }
>>>>>>> sprint1/listing-api:database/migrations/2025_05_06_141537_add_total_cost_to_reservations_table.php
}
