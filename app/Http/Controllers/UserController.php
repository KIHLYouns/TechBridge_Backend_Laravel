<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

class UserController extends Controller
{
    
    public function show($id)
    {
        try {
            $user = User::with('city')->findOrFail($id);
    
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'address' => $user->address,
                    'avatar_url' => $user->avatar_url,
                    'client_rating' => $user->client_rating,
                    'partner_rating' => $user->partner_rating,
                    'join_date' => $user->join_date,
                    'city' => [
                        'id' => $user->city->id ?? null,
                        'name' => $user->city->name ?? null,
                    ],
                    'role' => $user->role,
                    'is_partner' => $user->is_partner,
                    'client_reviews' => $user->client_reviews,
                    'partner_reviews' => $user->partner_reviews,
                    'longitude' => $user->longitude,
                    'latitude' => $user->latitude,
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
    
    

      /**
     * 
     * @param Request 
     * @param int 
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateById(Request $request, $id)
{
    try {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username'         => 'sometimes|string|max:255',
            'firstname'        => 'sometimes|string|max:255',
            'lastname'         => 'sometimes|string|max:255',
            'email'            => 'sometimes|string|email|max:255|unique:user,email,' . $id,
            'phone_number'     => 'sometimes|string|max:255',
            'address'          => 'sometimes|string|max:255',
            'avatar_url'       => 'sometimes|string|max:255',
            'client_rating'    => 'sometimes|numeric|min:0|max:5',
            'partner_rating'   => 'sometimes|numeric|min:0|max:5',
            'city_id'          => 'sometimes|exists:city,id',
            'role'             => 'sometimes|in:USER,ADMIN',
            'is_partner'       => 'sometimes|boolean',
            'client_reviews'   => 'sometimes|integer|min:0',
            'partner_reviews'  => 'sometimes|integer|min:0',
            'longitude'        => 'sometimes|numeric',
            'latitude'         => 'sometimes|numeric',
        ]);
    
        logger($validated);
        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'User not found'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update user',
            'error' => $e->getMessage()
        ], 500);
    }
}

    
}