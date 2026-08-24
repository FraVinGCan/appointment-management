<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRegistrationRequest;
use App\Http\Requests\LoginRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->validated())) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 401);
        }

        $request->session()->regenerate();

        return response()->json(['user' => $this->userData($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['user' => $user ? $this->userData($user) : null]);
    }

    public function registerClient(ClientRegistrationRequest $request): JsonResponse
    {
        $client = DB::transaction(function () use ($request): Client {
            $user = User::create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
                'is_admin' => false,
            ]);

            return Client::create([
                'user_id' => $user->id,
                'name' => $request->string('name')->toString(),
                'phone' => $request->input('phone'),
            ]);
        });

        Auth::login($client->user);
        $request->session()->regenerate();

        return response()->json(['user' => $this->userData($client->user)], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(User $user): array
    {
        $client = $user->client;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'isAdmin' => $user->is_admin,
            'client' => $client ? [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
            ] : null,
        ];
    }
}
