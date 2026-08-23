<?php

namespace App\Models;

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['client_id', 'service_id', 'notes', 'status', 'priority', 'appointment_date', 'start_time', 'end_time'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'priority' => AppointmentPriority::class,
            'appointment_date' => 'date:Y-m-d',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->client?->user_id === $user->id;
    }

    public function loadDetails(): static
    {
        return $this->load(['client.user', 'service']);
    }

    /**
     * Restrict the query to appointments that overlap the given slot for a service.
     */
    public function scopeConflicting(
        Builder $query,
        int $serviceId,
        string $appointmentDate,
        string $startTime,
        string $endTime,
        ?int $ignoreAppointmentId = null,
    ): void {
        $query->where('service_id', $serviceId)
            ->whereDate('appointment_date', $appointmentDate)
            ->where(function (Builder $query): void {
                $query->where('status', AppointmentStatus::Confirmed->value)
                    ->orWhere('status', AppointmentStatus::Completed->value);
            })
            ->when($ignoreAppointmentId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreAppointmentId))
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);
    }

    /**
     * Eager load the relations required to render an appointment.
     */
    public function scopeWithDetails(Builder $query): void
    {
        $query->with(['client.user', 'service']);
    }
}
