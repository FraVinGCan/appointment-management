<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentWorkflowException;
use App\Http\Requests\AppointmentIndexRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    public function index(AppointmentIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $filters = $request->validated();
        $search = trim((string) ($filters['search'] ?? ''));

        $appointments = Appointment::query()
            ->withDetails()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('status', $search)
                        ->orWhere('priority', $search)
                        ->orWhereHas('client', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('service', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['priority']), fn ($query) => $query->where('priority', $filters['priority']))
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->paginate((int) ($filters['per_page'] ?? 10));

        return AppointmentResource::collection($appointments)->response();
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        $this->authorize('view', $appointment);

        return new AppointmentResource($appointment->loadDetails());
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validated();

        if (Appointment::query()
            ->conflicting($data['serviceId'], $data['appointmentDate'], $data['startTime'], $data['endTime'])
            ->exists()) {
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

        return (new AppointmentResource($appointment->loadDetails()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validated();

        if (Appointment::query()
            ->conflicting($data['serviceId'], $data['appointmentDate'], $data['startTime'], $data['endTime'], $appointment->id)
            ->exists()) {
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

        return (new AppointmentResource($appointment->fresh()->loadDetails()))->response();
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return response()->json(null, 204);
    }

    public function confirm(Appointment $appointment, AppointmentWorkflowService $workflow): JsonResponse
    {
        return $this->transition($appointment, $workflow, 'confirm');
    }

    public function complete(Appointment $appointment, AppointmentWorkflowService $workflow): JsonResponse
    {
        return $this->transition($appointment, $workflow, 'complete');
    }

    public function cancel(Appointment $appointment, AppointmentWorkflowService $workflow): JsonResponse
    {
        return $this->transition($appointment, $workflow, 'cancel');
    }

    private function transition(
        Appointment $appointment,
        AppointmentWorkflowService $workflow,
        string $action,
    ): JsonResponse {
        $this->authorize($action, $appointment);

        try {
            $updatedAppointment = $workflow->{$action}($appointment);
        } catch (AppointmentWorkflowException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return (new AppointmentResource($updatedAppointment))->response();
    }

    private function conflictResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'The selected service is already booked during the requested time.',
        ], 409);
    }
}
