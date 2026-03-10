<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function login(Request $request)
    {
        if (Auth::attempt($request->only('email', 'password'))) {
            /** @var \App\Models\User */
            $user = Auth::user();
            $token = $user->createToken('token-name');

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'token' => $token->plainTextToken,
                'user' => Auth::user(),
            ]);
        }

        return response()->json([
            'message' => 'Email or password incorrect',
        ], 422);
    }

    public function logout(Request $request)
    {
        /** @var \App\Models\User */
        $user = Auth::user();
        $user->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful',
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'profile' => Auth::user(),
        ]);
    }
}
