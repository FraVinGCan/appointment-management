<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentWorkflowException;
use App\Http\Requests\BookingRequest;
use App\Http\Requests\ClientAppointmentIndexRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientAppointmentController extends Controller
{
    public function index(ClientAppointmentIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $appointments = Appointment::query()
            ->withDetails()
            ->where('client_id', $request->user()->client->id)
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->whereHas('service', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate((int) ($filters['per_page'] ?? 10));

        return AppointmentResource::collection($appointments)->response();
    }

    public function dashboard(Request $request): JsonResponse
    {
        $clientId = $request->user()->client->id;
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i:s');
        $appointments = Appointment::query()->where('client_id', $clientId);

        $upcoming = (clone $appointments)
            ->withDetails()
            ->whereIn('status', [AppointmentStatus::Requested, AppointmentStatus::Confirmed])
            ->where(function ($query) use ($today, $now): void {
                $query->whereDate('appointment_date', '>', $today)
                    ->orWhere(function ($query) use ($today, $now): void {
                        $query->whereDate('appointment_date', $today)
                            ->where('start_time', '>=', $now);
                    });
            })
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(3)
            ->get();

        return response()->json([
            'data' => [
                'pending' => (clone $appointments)->where('status', AppointmentStatus::Requested)->count(),
                'completed' => (clone $appointments)->where('status', AppointmentStatus::Completed)->count(),
                'upcoming' => AppointmentResource::collection($upcoming)->resolve(),
            ],
        ]);
    }

    public function store(BookingRequest $request): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validated();
        $data['clientId'] = $request->user()->client->id;

        if (Appointment::query()
            ->conflicting($data['serviceId'], $data['appointmentDate'], $data['startTime'], $data['endTime'])
            ->exists()) {
            return response()->json([
                'message' => 'The selected service is already booked during the requested time.',
            ], 409);
        }

        $appointment = Appointment::create([
            'client_id' => $data['clientId'],
            'service_id' => $data['serviceId'],
            'notes' => $data['notes'] ?? null,
            'status' => AppointmentStatus::Requested,
            'priority' => AppointmentPriority::Medium,
            'appointment_date' => $data['appointmentDate'],
            'start_time' => $data['startTime'],
            'end_time' => $data['endTime'],
        ]);

        return (new AppointmentResource($appointment->loadDetails()))
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(
        Request $request,
        Appointment $appointment,
        AppointmentWorkflowService $workflow,
    ): JsonResponse|AppointmentResource {
        if (! $request->user()->can('cancel', $appointment)) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        try {
            $appointment = $workflow->cancel($appointment);
        } catch (AppointmentWorkflowException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return new AppointmentResource($appointment);
    }
}
