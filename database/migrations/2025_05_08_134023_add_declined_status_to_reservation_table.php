<?php 

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class AddDeclinedStatusToReservationTable extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reservation MODIFY status ENUM('pending', 'confirmed', 'ongoing', 'canceled', 'completed', 'declined')");
    }

    public function down(): void
    {
        // Revert to the old ENUM values if needed
        DB::statement("ALTER TABLE reservation MODIFY status ENUM('pending', 'confirmed', 'ongoing', 'canceled', 'completed')");
    }
}
