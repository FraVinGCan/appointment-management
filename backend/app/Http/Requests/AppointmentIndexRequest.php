<?php

namespace App\Http\Requests;

use App\Enums\AppointmentPriority;
use App\Enums\AppointmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AppointmentIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', new Enum(AppointmentStatus::class)],
            'priority' => ['nullable', new Enum(AppointmentPriority::class)],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
