<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $appointments = Appointment::query()
            ->with(['client.user', 'service'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('status', $search)
                        ->orWhere('priority', $search)
                        ->orWhereHas('client', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('service', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->paginate((int) $request->query('per_page', 10));

        return AppointmentResource::collection($appointments)->response();
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        return new AppointmentResource($appointment->load(['client.user', 'service']));
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($this->hasConflict($data)) {
            return $this->conflictResponse();
        }

        $appointment = Appointment::create([
            'client_id' => $data['clientId'],
            'service_id' => $data['serviceId'],
            'notes' => $data['notes'] ?? null,
            'status' => AppointmentStatus::Requested,
            'priority' => $data['priority'],
            'appointment_date' => $data['appointmentDate'],
            'start_time' => $data['startTime'],
            'end_time' => $data['endTime'],
        ]);

        return (new AppointmentResource($appointment->load(['client.user', 'service'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $data = $request->validated();

        if ($this->hasConflict($data, $appointment->id)) {
            return $this->conflictResponse();
        }

        $appointment->update([
            'client_id' => $data['clientId'],
            'service_id' => $data['serviceId'],
            'notes' => $data['notes'] ?? null,
            'priority' => $data['priority'],
            'appointment_date' => $data['appointmentDate'],
            'start_time' => $data['startTime'],
            'end_time' => $data['endTime'],
        ]);

        return (new AppointmentResource($appointment->fresh()->load(['client.user', 'service'])))->response();
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json(null, 204);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function hasConflict(array $data, ?int $ignoreAppointmentId = null): bool
    {
        return Appointment::query()
            ->where('service_id', $data['serviceId'])
            ->whereDate('appointment_date', $data['appointmentDate'])
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->when($ignoreAppointmentId !== null, fn ($query) => $query->where('id', '!=', $ignoreAppointmentId))
            ->where('start_time', '<', $data['endTime'])
            ->where('end_time', '>', $data['startTime'])
            ->exists();
    }

    private function conflictResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'The selected service is already booked during the requested time.',
        ], 409);
    }
}
