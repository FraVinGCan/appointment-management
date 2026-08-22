<?php

namespace App\Exceptions;

use App\Enums\AppointmentStatus;
use RuntimeException;

class AppointmentWorkflowException extends RuntimeException
{
    public function __construct(
        public readonly string $action,
        public readonly AppointmentStatus $currentStatus,
    ) {
        parent::__construct(sprintf(
            'Appointment cannot be %s because its current status is %s.',
            $action,
            $currentStatus->value,
        ));
    }
}
