<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->string('firstname')->nullable()->after('username');
            $table->string('lastname')->nullable()->after('firstname');
            $table->boolean('is_partner')->default(false)->after('role');
            $table->decimal('client_rating', 5, 2)->default(0)->after('join_date');
            $table->integer('client_reviews')->default(0)->after('client_rating');
            $table->decimal('partner_rating', 5, 2)->default(0)->after('client_reviews');
            $table->integer('partner_reviews')->default(0)->after('partner_rating');
    
            // Optional: Change ENUM role to match new spec
            DB::statement("ALTER TABLE user MODIFY role ENUM('USER', 'ADMIN') NOT NULL DEFAULT 'USER'");
        });
    }
    
    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn([
                'firstname',
                'lastname',
                'is_partner',
                'client_rating',
                'client_reviews',
                'partner_rating',
                'partner_reviews',
            ]);
    
            // Revert enum change
            DB::statement("ALTER TABLE user MODIFY role ENUM('client', 'partner', 'admin') NOT NULL DEFAULT 'client'");
        });
    }
    
};
