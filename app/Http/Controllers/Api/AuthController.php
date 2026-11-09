<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthResponse',
    required: ['message', 'token', 'token_type', 'user'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil'),
        new OA\Property(property: 'token', type: 'string', example: '1|token-rahasia'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
    type: 'object'
)]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/register',
        operationId: 'register',
        summary: 'Daftar akun baru dan dapatkan token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Akun berhasil dibuat',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Data pendaftaran tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        unset($validated['password_confirmation']);

        $user = User::create($validated);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    #[OA\Post(
        path: '/api/login',
        operationId: 'login',
        summary: 'Login dan dapatkan token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Email atau password salah',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken($request->input('device_name') ?: 'api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    #[OA\Get(
        path: '/api/me',
        operationId: 'currentUser',
        summary: 'Tampilkan profil pengguna yang sedang login',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil pengguna',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Token tidak ada atau tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
        ]
    )]
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    #[OA\Post(
        path: '/api/logout',
        operationId: 'logout',
        summary: 'Hapus token yang sedang digunakan',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout berhasil',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Token tidak ada atau tidak valid',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }
}
