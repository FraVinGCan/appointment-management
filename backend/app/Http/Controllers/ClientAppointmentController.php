<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentWorkflowException;
use App\Http\Requests\BookingRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientAppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $appointments = Appointment::query()
            ->with(['client.user', 'service'])
            ->where('client_id', $request->user()->client->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate((int) $request->query('per_page', 10));

        return AppointmentResource::collection($appointments)->response();
    }

    public function store(BookingRequest $request, AppointmentController $appointments): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validated();
        $data['clientId'] = $request->user()->client->id;

        if ($appointments->hasConflict($data)) {
            return response()->json([
                'message' => 'The selected service is already booked during the requested time.',
            ], 409);
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
