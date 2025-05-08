<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewCheckController extends Controller
{
    /**
     * Check if a user has already reviewed a reservation
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkReview(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer|exists:user,id',
            'reservation_id' => 'required|integer|exists:reservation,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $userId = $request->input('userId');
        $reservation_id = $request->input('reservation_id');

        // Check if the review exists
        $reviewExists = Review::where('reviewer_id', $userId)
            ->where('reservation_id', $reservation_id)
            ->exists();

        return response()->json($reviewExists);
    }
}