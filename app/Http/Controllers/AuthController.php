<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials!',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $at_expiration = 60;
        $accessToken = $user->createToken('access-token', ['access-api'], Carbon::now()->addMinutes($at_expiration))->plainTextToken;

        $rt_expiration = 7 * 24 * 60;
        $refreshToken = $user->createToken('refresh-token', ['issue-access-token'], Carbon::now()->addMinutes($rt_expiration))->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refreshToken' => ['required'],
        ]);

        $token = PersonalAccessToken::findToken($request->refreshToken);

        if (! $token || $token->name !== 'refresh-token' || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json(['message' => 'Invalid refresh token'], 401);
        }

        $user = $token->tokenable;

        $at_expiration = 60;
        $accessToken = $user->createToken('access-token', ['access-api'], Carbon::now()->addMinutes($at_expiration))->plainTextToken;

        return response()->json([
            'success' => true,
            'accessToken' => $accessToken,
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json([], 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
