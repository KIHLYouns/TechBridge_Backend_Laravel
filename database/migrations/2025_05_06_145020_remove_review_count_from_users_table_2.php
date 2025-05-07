<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveReviewCountFromUsersTable2 extends Migration
{
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('review_count');
        });
    }

    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->integer('review_count')->default(0); // ou nullable() selon le cas initial
        });
    }
}

