<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentication controller for user login, registration, and logout.
 *
 * Handles token-based authentication using Laravel Sanctum.
 */
class AuthController extends ApiBaseController
{
    private const TOKEN_NAME = 'api-token';

    /**
     * Get the currently authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Revoke all tokens for the user (logout).
     *
     * Invalidates all active sessions for security.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success(['message' => 'Logged out successfully']);
    }

    /**
     * Authenticate user and issue access token.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->attemptLogin($validated['email'], $validated['password']);

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Register a new user and issue access token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->tryAction(
            fn () => $this->createUserWithToken($request->validated()),
            fn (array $result) => $this->success($result, 201)
        );
    }

    /**
     * Attempt to authenticate user with email and password.
     *
     * @throws ValidationException
     */
    private function attemptLogin(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user;
    }

    /**
     * Create new user and generate authentication token.
     *
     * @param array{name: string, email: string, password: string} $validated
     * @return array{token: string, user: User}
     */
    private function createUserWithToken(array $validated): array
    {
        return DB::transaction(function () use ($validated): array {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->assignRole('customer');

            $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

            return [
                'token' => $token,
                'user' => $user->load('roles'),
            ];
        });
    }
}
