<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::with(['employee', 'role'])
            ->where('username', $request->username)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(
                ['message' => 'The provided credentials are incorrect.'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $token = $user->createToken('api-token', ['*'], now()->addHour())->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load(['employee', 'role']));
    }

    public function setPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();
        $user->password = $request->password;
        $user->isNew    = false;
        $user->save();

        return response()->json([
            'message' => 'Password set successfully.',
            'user'    => $user->load(['employee', 'role']),
        ]);
    }
}
