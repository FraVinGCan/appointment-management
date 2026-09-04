<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentWorkflowException;
use App\Http\Requests\AppointmentIndexRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Services\AppointmentWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(AppointmentIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $filters = $request->validated();
        $search = trim((string) ($filters['search'] ?? ''));
        $sortBy = $filters['sort_by'] ?? 'appointment_date';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

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
            ->when(isset($filters['client_id']), fn ($query) => $query->where('client_id', $filters['client_id']))
            ->when(isset($filters['service_id']), fn ($query) => $query->where('service_id', $filters['service_id']))
            ->when(isset($filters['date_from']), fn ($query) => $query->whereDate('appointment_date', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('appointment_date', '<=', $filters['date_to']));

        if ($sortBy === 'client') {
            $appointments->orderBy(
                Client::query()
                    ->select('name')
                    ->whereColumn('clients.id', 'appointments.client_id'),
                $sortDirection,
            );
        } elseif ($sortBy === 'service') {
            $appointments->orderBy(
                Service::query()
                    ->select('name')
                    ->whereColumn('services.id', 'appointments.service_id'),
                $sortDirection,
            );
        } else {
            $appointments->orderBy($sortBy, $sortDirection);
        }

        if ($sortBy === 'appointment_date') {
            $appointments->orderBy('start_time', $sortDirection);
        }

        $appointments->orderBy('id');
        $appointments = $appointments->paginate((int) ($filters['per_page'] ?? 10));

        return AppointmentResource::collection($appointments)->response();
    }

    public function calendar(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $start = Carbon::parse($request->input('start'));
        $end = Carbon::parse($request->input('end'));

        if ($start->diffInDays($end) > 90) {
            $end = $start->copy()->addDays(90);
        }

        $appointments = Appointment::query()
            ->with(['client', 'service'])
            ->whereDate('appointment_date', '>=', $start->toDateString())
            ->whereDate('appointment_date', '<=', $end->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        $statusColors = [
            AppointmentStatus::Requested->value => '#f59e0b',
            AppointmentStatus::Confirmed->value => '#3b82f6',
            AppointmentStatus::Completed->value => '#22c55e',
            AppointmentStatus::Cancelled->value => '#6b7280',
        ];

        $events = $appointments->map(function (Appointment $appointment) use ($statusColors) {
            $clientName = $appointment->client?->name ?? 'Unknown client';
            $serviceName = $appointment->service?->name ?? 'Unknown service';

            return [
                'id' => $appointment->id,
                'title' => "{$clientName} — {$serviceName}",
                'start' => "{$appointment->appointment_date->format('Y-m-d')}T{$appointment->start_time}",
                'end' => "{$appointment->appointment_date->format('Y-m-d')}T{$appointment->end_time}",
                'color' => $statusColors[$appointment->status?->value] ?? '#6b7280',
                'extendedProps' => [
                    'status' => $appointment->status?->value,
                    'priority' => $appointment->priority?->value,
                    'clientId' => $appointment->client_id,
                    'serviceId' => $appointment->service_id,
                ],
            ];
        });

        return response()->json(['data' => $events]);
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
