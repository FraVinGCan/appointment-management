<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = Service::query()
            ->when(! $request->user()?->is_staff, fn ($query) => $query->where('active', true))
            ->orderBy('name')
            ->get();

        return ServiceResource::collection($services)->response();
    }

    public function show(Service $service): ServiceResource
    {
        return new ServiceResource($service->load('appointments'));
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $service = Service::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => $data['active'] ?? true,
        ]);

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $data = $request->validated();
        $service->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => $data['active'],
        ]);

        return new ServiceResource($service->fresh());
    }

    public function deactivate(Service $service): ServiceResource
    {
        $service->update(['active' => false]);

        return new ServiceResource($service->fresh());
    }
}
