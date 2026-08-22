<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\BookingRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
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

    public function cancel(Request $request, Appointment $appointment): JsonResponse|AppointmentResource
    {
        if ($appointment->client_id !== $request->user()->client->id) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        if (! in_array($appointment->status, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], true)) {
            return response()->json(['message' => 'This appointment cannot be cancelled.'], 409);
        }

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        return new AppointmentResource($appointment->fresh()->load(['client.user', 'service']));
    }
}
