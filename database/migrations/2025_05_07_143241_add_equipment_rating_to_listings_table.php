<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEquipmentRatingToListingsTable extends Migration
{
    public function up()
    {
        Schema::table('listing', function (Blueprint $table) {
            $table->decimal('equipment_rating', 3, 2)->nullable()->after('delivery_option');
        });
    }

    public function down()
    {
        Schema::table('listing', function (Blueprint $table) {
            $table->dropColumn('equipment_rating');
        });
    }
}
