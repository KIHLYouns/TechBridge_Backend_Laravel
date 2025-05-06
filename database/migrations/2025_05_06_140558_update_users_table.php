<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersTable extends Migration
{
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            // Ajout de nouvelles colonnes
            $table->string('firstname')->nullable()->after('username');
            $table->string('lastname')->nullable()->after('firstname');
            $table->boolean('is_partner')->default(false)->after('role');
            $table->decimal('client_rating', 3, 2)->nullable()->after('join_date');
            $table->integer('client_reviews')->default(0)->after('client_rating');
            $table->decimal('partner_rating', 3, 2)->nullable()->after('client_reviews');
            // Ajout de la colonne partner_reviews
            $table->integer('partner_reviews')->default(0)->after('partner_rating');

            // Modification de l'enum 'role'
            $table->enum('role', ['admin', 'user'])->default('user')->change();

            // Suppression de la colonne avg_rating
            $table->dropColumn('avg_rating');
        });
    }

    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            // Rollback : suppression des colonnes ajoutées
            $table->dropColumn([
                'firstname',
                'lastname',
                'is_partner',
                'client_rating',
                'client_reviews',
                'partner_rating',
                'partner_reviews'
            ]);

            // Remettre l'ancienne enum 'role'
            $table->enum('role', ['admin', 'partner', 'client'])->default('client')->change();

            // Réajouter avg_rating
            $table->decimal('avg_rating', 3, 2)->nullable();
        });
    }
}

