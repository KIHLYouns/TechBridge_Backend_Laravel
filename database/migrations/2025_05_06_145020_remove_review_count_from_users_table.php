<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveReviewCountFromUsersTable extends Migration
{
   

    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->integer('review_count')->default(0); // ou nullable() selon le cas initial
        });
    }
}

