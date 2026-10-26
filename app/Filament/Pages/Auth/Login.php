<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

/**
 * Offers the school's Microsoft login first; email and password are a collapsed fallback.
 */
class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';
}
