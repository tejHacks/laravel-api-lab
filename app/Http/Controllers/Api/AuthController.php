<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Token authentication for mobile apps with Sanctum (see "Token Authentication with Sanctum").
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::min(8)],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::create($data);

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken($data['device_name'], ['books:read', 'books:write'])->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
            'read_only' => ['boolean'],   // demo of token abilities
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        $abilities = ($data['read_only'] ?? false) ? ['books:read'] : ['books:read', 'books:write'];

        return response()->json([
            'user' => $this->userPayload($user),
            'abilities' => $abilities,
            'token' => $user->createToken($data['device_name'], $abilities, now()->addDays(30))->plainTextToken,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
            'token_abilities' => $request->user()->currentAccessToken()?->abilities,
            'books_added' => $request->user()->books()->count(),
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()?->delete();   // revoke only this device's token

        return response()->noContent();
    }

    private function userPayload(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }
}
