<?php

declare(strict_types=1);

return [

    'auth' => [
        'url' => env('AUTH_SERVICE_URL', 'http://localhost:8000'),
    ],

    'ip' => [
        'url' => env('IP_SERVICE_URL', 'http://localhost:8001'),
    ],

];
