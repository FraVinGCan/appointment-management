<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Client::class);

        $search = trim((string) $request->query('search', ''));
        $clients = Client::query()
            ->with('user')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 10));

        return ClientResource::collection($clients)->response();
    }

    public function show(Client $client): ClientResource
    {
        $this->authorize('view', $client);

        return new ClientResource($client->load('user'));
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $this->authorize('create', Client::class);

        $data = $request->validated();
        $client = DB::transaction(function () use ($data): Client {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_staff' => false,
            ]);

            return Client::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'active' => true,
            ]);
        });

        return (new ClientResource($client->load('user')))->response()->setStatusCode(201);
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        $this->authorize('update', $client);

        $data = $request->validated();
        $client->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'active' => $data['active'],
        ]);
        $client->user->update(['name' => $data['name'], 'email' => $data['email']]);

        return new ClientResource($client->fresh()->load('user'));
    }

    public function deactivate(Client $client): ClientResource
    {
        $this->authorize('deactivate', $client);

        $client->update(['active' => false]);

        return new ClientResource($client->fresh()->load('user'));
    }

    public function activate(Client $client): ClientResource
    {
        $this->authorize('activate', $client);

        $client->update(['active' => true]);

        return new ClientResource($client->fresh()->load('user'));
    }
}
