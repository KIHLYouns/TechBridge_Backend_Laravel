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
            
            $user = User::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $user
            ], 200); 
            
        } catch (\Exception $e) {
           
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => $e->getMessage()
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
                'username' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:user,email,' . $id,
                'phone_number' => 'sometimes|string|max:255',
                'address' => 'sometimes|string|max:255',
                'city_id' => 'sometimes|exists:city,id',
                'avatar_url' => 'sometimes|string|max:255',

            ]);
            
            $user->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
           
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
           
            $validated = $request->validate([
                'username' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:user,email',
                'password' => 'required|string|min:8',
                'phone_number' => 'sometimes|string|max:255',
                'address' => 'sometimes|string|max:255',
                'role' => 'sometimes|string',
                'avatar_url' => 'sometimes|string',
                'city_id' => 'sometimes|exists:city,id',
                'longitude' => 'sometimes|numeric',
                'latitude' => 'sometimes|numeric',
            ]);
            
        
            $validated['password'] = Hash::make($validated['password']);
            
        
            if (!isset($validated['role'])) {
                $validated['role'] = 'user'; 
            }
            
            $validated['join_date'] = now();
            $validated['avg_rating'] = 0; 
            $validated['reviewi_count'] = 0; 
            
           
            $user = User::create($validated);
            
            
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201); 

        } catch (\Illuminate\Validation\ValidationException $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
           
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500); 
        }
    
    }
}