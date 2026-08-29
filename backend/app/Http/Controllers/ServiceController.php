<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceIndexRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(ServiceIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['search'] ?? ''));
        $services = Service::query()
            ->when(! $request->user()?->isAdmin(), fn ($query) => $query->where('active', true))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when(isset($filters['category']), fn ($query) => $query->whereRaw('LOWER(category) = ?', [strtolower($filters['category'])]))
            ->when($request->user()?->isAdmin() && array_key_exists('active', $filters), fn ($query) => $query->where('active', $filters['active']))
            ->orderBy('name')
            ->when(isset($filters['limit']), fn ($query) => $query->limit($filters['limit']))
            ->get();

        return ServiceResource::collection($services)->response();
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = Service::query()
            ->whereNotNull('category')
            ->when($request->boolean('active'), fn ($query) => $query->where('active', true))
            ->orderBy('category')
            ->pluck('category')
            ->map(fn (string $category): string => Str::squish($category))
            ->filter()
            ->unique(fn (string $category): string => strtolower($category))
            ->values();

        return response()->json(['data' => $categories]);
    }

    public function show(Request $request, Service $service): ServiceResource
    {
        if ($request->user()) {
            $this->authorize('view', $service);
        } elseif (! $service->active) {
            abort(404);
        }

        if ($request->user()?->isAdmin()) {
            $service->load('appointments');
        }

        return new ServiceResource($service);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $this->authorize('create', Service::class);

        $data = $request->validated();
        $service = Service::create([
            'name' => $data['name'],
            'short_description' => $data['shortDescription'] ?? null,
            'category' => $this->canonicalCategory($data['category'] ?? null),
            'description' => $data['description'] ?? null,
            'active' => $data['active'] ?? true,
        ]);

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $this->authorize('update', $service);

        $data = $request->validated();
        $service->update([
            'name' => $data['name'],
            'short_description' => $data['shortDescription'] ?? null,
            'category' => $this->canonicalCategory($data['category'] ?? null),
            'description' => $data['description'] ?? null,
            'active' => $data['active'],
        ]);

        return new ServiceResource($service->fresh());
    }

    public function deactivate(Service $service): ServiceResource
    {
        $this->authorize('deactivate', $service);

        $service->update(['active' => false]);

        return new ServiceResource($service->fresh());
    }

    private function canonicalCategory(?string $category): ?string
    {
        $category = Str::squish($category ?? '');

        if ($category === '') {
            return null;
        }

        return Service::query()
            ->whereRaw('LOWER(category) = ?', [strtolower($category)])
            ->orderBy('id')
            ->value('category') ?? $category;
    }
}
