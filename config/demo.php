<?php

return [
    'login_enabled' => (bool) env('DEMO_LOGIN_ENABLED', false),
    'login_email' => env('DEMO_LOGIN_EMAIL'),
    'login_password' => env('DEMO_LOGIN_PASSWORD'),
];
