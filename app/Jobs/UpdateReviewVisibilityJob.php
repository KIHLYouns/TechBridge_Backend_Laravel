<?php

namespace App\Jobs;

use App\Models\Review;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class UpdateReviewVisibilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find completed reservations where the end date was at least 7 days ago
        $cutoffDate = Carbon::now()->subWeek();
        
        $completedReservations = Reservation::where('status', 'completed')
            ->where('end_date', '<=', $cutoffDate)
            ->get();
        
        foreach ($completedReservations as $reservation) {
            // Update visibility for reviews related to this reservation
            Review::updateVisibility($reservation->id);
        }
    }
}