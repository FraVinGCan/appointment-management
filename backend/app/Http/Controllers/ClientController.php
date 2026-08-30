<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientIndexRequest;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index(ClientIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Client::class);

        $filters = $request->validated();
        $search = trim((string) ($filters['search'] ?? ''));
        $clients = Client::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('email', 'like', "%{$search}%"))
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('active', $filters), fn ($query) => $query->where('active', $filters['active']))
            ->orderBy('name')
            ->paginate((int) ($filters['per_page'] ?? 10));

        return ClientResource::collection($clients)->response();
    }

    public function show(Client $client): ClientResource
    {
        $this->authorize('view', $client);

        return new ClientResource($client->load([
            'user',
            'appointments' => fn ($query) => $query
                ->with('service')
                ->orderByDesc('appointment_date')
                ->orderByDesc('start_time'),
        ]));
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
                'is_admin' => false,
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
