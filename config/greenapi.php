<?php

return [
    'base_url' => env('GREEN_API_BASE_URL', 'https://api.green-api.com'),
    'instance_id' => env('GREEN_API_INSTANCE_ID'),
    'token' => env('GREEN_API_TOKEN'),
    'timeout' => env('GREEN_API_TIMEOUT', 15),
    'throttle_per_second' => env('GREEN_API_THROTTLE_PER_SECOND', 5),
];
