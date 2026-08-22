<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'clientId' => $this->client_id,
            'serviceId' => $this->service_id,
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'appointmentDate' => $this->appointment_date?->format('Y-m-d'),
            'startTime' => $this->start_time,
            'endTime' => $this->end_time,
            'client' => new ClientResource($this->whenLoaded('client')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
