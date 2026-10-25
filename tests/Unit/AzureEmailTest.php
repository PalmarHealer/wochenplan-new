<?php

use App\Providers\Filament\AdminPanelProvider;
use SocialiteProviders\Manager\OAuth2\User as OAuthUser;

function azureEmail(array $raw): string
{
    $user = (new OAuthUser)->setRaw($raw)->map(['email' => $raw['userPrincipalName']]);
    $method = new ReflectionMethod(AdminPanelProvider::class, 'azureEmail');

    return $method->invoke(null, $user);
}

it('uses the mail attribute when present', function () {
    expect(azureEmail([
        'userPrincipalName' => 'm.muster_eltern-asl.de#EXT#@aksleipzig.onmicrosoft.com',
        'mail' => 'M.Muster@eltern-asl.de',
    ]))->toBe('m.muster@eltern-asl.de');
});

it('decodes a guest UPN when mail is missing', function () {
    expect(azureEmail([
        'userPrincipalName' => 'm.muster_eltern-asl.de#EXT#@aksleipzig.onmicrosoft.com',
        'mail' => null,
    ]))->toBe('m.muster@eltern-asl.de');
});

it('keeps the UPN of tenant members', function () {
    expect(azureEmail([
        'userPrincipalName' => 'max.muster@aksleipzig.onmicrosoft.com',
        'mail' => null,
    ]))->toBe('max.muster@aksleipzig.onmicrosoft.com');
});
