<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function userInfo(Request $request)
    {
        $user = $request->create([
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            // Add other user info as needed
        ]);
    }
}
