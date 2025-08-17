<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'tabler-home';

    public static function getRoutePath(): string
    {
        return "dashboard";
    }
}
