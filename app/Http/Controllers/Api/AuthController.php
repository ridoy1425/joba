<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Google\Client as GoogleClient;

class AuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find or create user
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(uniqid()), // Random password
                    'email_verified_at' => now(),
                ]);
            } else {
                // Update google_id if not set
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
            }
            info($user);
            // Create token (using Sanctum)
            $token = $user->createToken('google-auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'user' => $user,
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 401);
        }
    }


    public function logout(Request $request)
    {
        $user = User::findOrFail(Auth::id());
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6|confirmed',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'user' => $user,
        ], 200);
    }

    public function googleLogin(Request $request)
    {
        
        $idToken = $request->input('id_token');  // Google ID Token from Flutter
        // Initialize the Google Client
        $googleClient = new GoogleClient();
        $googleClient->setClientId(config('services.google.client_id'));  // Your Google Client ID
        $googleClient->setClientSecret(config('services.google.client_secret'));  // Your Google Client Secret
        $googleClient->addScope('email');
        $googleClient->addScope('profile');

        // Verify the ID token
        $payload = $googleClient->verifyIdToken($idToken);

        if ($payload) {
            // The token is valid
            // Now you can get the user's information
            $googleId = $payload['sub'];  // Google user ID
            $email = $payload['email'];
            $name = $payload['name'];

            // Check if the user exists or create a new one
            $user = User::firstOrCreate(
                ['google_id' => $googleId],
                ['email' => $email, 'name' => $name]
            );

            // Log the user in or generate a JWT token for the session
            // For example, using Laravel Passport for authentication
            $token = $user->createToken('YourAppName')->accessToken;

            return response()->json(['token' => $token]);
        } else {
            return response()->json(['error' => $payload], 401);
        }
    }
}