<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Requested = 'Requested';
    case Confirmed = 'Confirmed';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
