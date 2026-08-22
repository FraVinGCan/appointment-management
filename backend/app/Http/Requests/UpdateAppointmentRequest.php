<?php

namespace App\Http\Requests;

use App\Enums\AppointmentPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clientId' => ['required', 'integer', 'exists:clients,id'],
            'serviceId' => ['required', 'integer', 'exists:services,id'],
            'notes' => ['nullable', 'string'],
            'priority' => ['required', new Enum(AppointmentPriority::class)],
            'appointmentDate' => ['required', 'date_format:Y-m-d'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i', 'after:startTime'],
            'status' => ['prohibited'],
        ];
    }
}
