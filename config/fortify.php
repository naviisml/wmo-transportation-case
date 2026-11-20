<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/dashboard',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web'],
    'limiters' => ['login' => 'login'],
    'views' => true,
    'paths' => [
        'verification' => ['notice' => '/verify-email'],
        'password' => ['confirm' => '/confirm-password'],
    ],
    'features' => [Features::resetPasswords(), Features::emailVerification()],
];
