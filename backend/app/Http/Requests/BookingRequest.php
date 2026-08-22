<?php

namespace App\Http\Requests;

use App\Enums\AppointmentPriority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class BookingRequest extends FormRequest
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
            'serviceId' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
            'notes' => ['nullable', 'string'],
            'priority' => ['required', new Enum(AppointmentPriority::class)],
            'appointmentDate' => ['required', 'date_format:Y-m-d'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i', 'after:startTime'],
            'clientId' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
