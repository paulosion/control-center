<?php

/*
 * Every system the control center pings. `health` is called once a minute by
 * `php artisan health:ping`; a 2xx answer within `timeout` seconds is "up".
 * The Laravel apps all have the framework's built-in /up route.
 */
return [

    'my-genone' => [
        'name' => 'my-genone',
        'sub' => 'Employee portal · IOMS',
        'url' => env('GENONE_URL', 'https://genone-ioms.up.railway.app'),
        'health' => '/up',
        'db' => 'genone',
        'repo' => 'https://github.com/paulosion/ioms',
    ],

    '201' => [
        'name' => '201 System',
        'sub' => 'IT admin · devices, accounts',
        'url' => env('HR201_URL', 'https://genone-201.up.railway.app'),
        'health' => '/up',
        'db' => 'genone', // same database as my-genone
        'repo' => null,
    ],

    'paymaya' => [
        'name' => 'PayMaya',
        'sub' => 'Maya profile verification',
        'url' => env('PAYMAYA_URL', 'https://genone-maya.up.railway.app'),
        'health' => '/up',
        'db' => 'paymaya',
        'repo' => 'https://github.com/paulosion/mayainteg',
    ],

    'ticketing' => [
        'name' => 'Ticketing Portal',
        'sub' => 'Employee IT tickets',
        'url' => env('TICKETING_URL', 'https://genone-ticketingportal.up.railway.app'),
        'health' => '/up',
        'db' => null,
        'repo' => null,
    ],

    'face-service' => [
        'name' => 'face-service',
        'sub' => 'Face match for RFID photos',
        'url' => env('FACE_SERVICE_URL'),
        'health' => '/',
        'db' => null,
        'repo' => 'https://github.com/paulosion/face-api',
    ],

    'gen1' => [
        'name' => 'Gen One website',
        'sub' => 'gen-one.ph',
        'url' => env('GEN1_URL', 'https://www.gen-one.ph'),
        'health' => '/',
        'db' => null,
        'repo' => 'https://github.com/paulosion/genone-ffw',
    ],

    // Seconds before a ping counts as down.
    'timeout' => 10,

    // Slower than this (ms) shows as "Slow".
    'slow_ms' => 2000,
];
