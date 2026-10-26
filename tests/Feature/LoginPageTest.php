<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('offers the Microsoft login before the password form', function () {
    $html = $this->get('/login')->assertOk()->getContent();

    expect(strpos($html, 'Aktive Schule Leipzig'))->toBeLessThan(strpos($html, 'Mit E-Mail und Passwort anmelden'))
        ->and(strpos($html, 'Mit E-Mail und Passwort anmelden'))->toBeLessThan(strpos($html, 'wire:submit="authenticate"'));
});

it('still logs in with email and password', function () {
    $user = User::factory()->create(['password' => bcrypt('richtig-langes-passwort')]);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'richtig-langes-passwort'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);
});
