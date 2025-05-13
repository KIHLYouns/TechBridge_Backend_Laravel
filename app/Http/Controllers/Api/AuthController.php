<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username'      => 'required|string|max:255',
            'firstname'     => 'required|string',
            'lastname'      => 'required|string',
            'email'         => 'required|string|email|unique:user,email',
            'password'      => 'required|string|min:6',
            'phone_number'  => 'required|string',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'is_partner'   => 'required|boolean',
        ]);

        $response = Http::withHeaders([
            'User-Agent' => 'YourAppName/1.0 (contact@yourdomain.com)',
        ])->get("https://nominatim.openstreetmap.org/reverse", [
            'lat' => $request->latitude,
            'lon' => $request->longitude,
            'format' => 'json',
        ]); 
        
        Log::info("Réponse Nominatim : " . $response->body());
        Log::info("Statut HTTP : " . $response->status());

        if (!$response->successful()) {
            return response()->json(['error' => 'Échec de récupération de la ville'], 500);
        }

        $data = $response->json();
        $cityName = $data['address']['city'] ?? $data['address']['town'] ?? $data['address']['village'] ?? null;

        if (!$cityName) {
            return response()->json(['error' => 'Ville non trouvée à partir de la position'], 400);
        }

        $cityName = preg_replace('/[^a-zA-Z\s]/u', '', $cityName);
        $cityName = trim(explode(' ', $cityName)[0]);

        Log::info("Nom de ville nettoyé : " . $cityName);

        if (!$cityName) {
            return response()->json(['error' => 'Ville non trouvée à partir de la position'], 400);
        }

        $city = City::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($cityName) . '%'])->first();

        if (!$city) {
            $city = City::create([
                'name' => $cityName,
            ]);
        }

        $user = User::create([
            'username'     => $request->username,
            'firstname'    => $request->firstname,
            'lastname'     => $request->lastname,
            'email'        => $request->email,
            'password'     => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'address'      => $request->address,
            'city_id'      => $city->id,
            'longitude'   => $request->longitude,
            'latitude'    => $request->latitude,
            'role'         => 'USER',
            'join_date'    => now(),
            'is_partner'   => $request->is_partner,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'id'    => $user->id,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'id'    => $user->id,
            'token' => $token,
        ]);
    }
}
