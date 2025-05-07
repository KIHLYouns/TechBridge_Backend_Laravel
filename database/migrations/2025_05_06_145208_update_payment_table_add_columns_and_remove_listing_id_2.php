<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePaymentTableAddColumnsAndRemoveListingId2 extends Migration
{
    public function up()
    {
        Schema::table('payment', function (Blueprint $table) {
            // Supprimer la clé étrangère de listing_id si elle existe
            if (Schema::hasColumn('payment', 'listing_id')) {
                try {
                    $table->dropForeign(['listing_id']);
                } catch (\Throwable $e) {
                    // La contrainte peut être déjà supprimée
                }
                $table->dropColumn('listing_id');
            }

            // Ajouter les colonnes si elles n'existent pas
            if (!Schema::hasColumn('payment', 'commission_fee')) {
                $table->decimal('commission_fee', 8, 2)->nullable();
            }

            if (!Schema::hasColumn('payment', 'partner_payout')) {
                $table->decimal('partner_payout', 8, 2)->nullable();
            }

            if (!Schema::hasColumn('payment', 'payment_method')) {
                $table->enum('payment_method', ['credit_card', 'paypal'])->default('credit_card');
            }

            if (!Schema::hasColumn('payment', 'transaction_id')) {
                $table->integer('transaction_id')->nullable();
            }

            if (!Schema::hasColumn('payment', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable();
                $table->foreign('client_id')->references('id')->on('user')->onDelete('set null');
            }

            if (!Schema::hasColumn('payment', 'reservation_id')) {
                $table->unsignedBigInteger('reservation_id')->nullable();
                $table->foreign('reservation_id')->references('id')->on('reservation')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('payment', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['reservation_id']);

            $table->dropColumn([
                'commission_fee',
                'partner_payout',
                'payment_method',
                'transaction_id',
                'client_id',
                'reservation_id',
            ]);

            $table->unsignedBigInteger('listing_id')->nullable();
            $table->foreign('listing_id')->references('id')->on('listing')->onDelete('set null');
        });
    }
}
