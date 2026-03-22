<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects root route to dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect('/dashboard');
});
