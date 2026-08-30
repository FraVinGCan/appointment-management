<?php

return [
    'public' => [
        'per_minute' => (int) env('RATE_LIMIT_PUBLIC_PER_MINUTE', 60),
    ],

    'authenticated' => [
        'per_minute' => (int) env('RATE_LIMIT_AUTHENTICATED_PER_MINUTE', 60),
    ],

    'login' => [
        'ip_per_minute' => (int) env('RATE_LIMIT_LOGIN_PER_MINUTE', 5),
        'email_per_minute' => (int) env('RATE_LIMIT_LOGIN_EMAIL_PER_MINUTE', 5),
    ],

    'register' => [
        'per_minute' => (int) env('RATE_LIMIT_REGISTER_PER_MINUTE', 3),
    ],

    'booking' => [
        'per_minute' => (int) env('RATE_LIMIT_BOOKING_PER_MINUTE', 10),
    ],
];
