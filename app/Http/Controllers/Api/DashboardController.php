<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function userInfo(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            // Add other user info as needed
        ]);
    }
}
