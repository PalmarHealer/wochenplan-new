<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\WeekDaysCollection;
use App\Livewire\PersonalInfo;
use App\Models\User;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Actions\MountableAction;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        Page::formActionsAlignment(Alignment::Right);

        MountableAction::configureUsing(function (MountableAction $action) {
            $action->modalFooterActionsAlignment(Alignment::Right);
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                function (): string {
                    return Blade::render('@laravelPWA');
                }
            )
            ->default()
            ->brandName(env('APP_NAME', 'name konnte nicht geladen werden'))
            ->brandLogo(asset(env('APP_LOGO')))
            ->darkModeBrandLogo(asset(env('APP_LOGO_DARK')))
            ->brandLogoHeight('2rem')
            ->id('admin')
            ->path('')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->plugins([
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('azure')
                            ->label('Aktive Schule Leipzig')
                            ->icon('tabler-brand-teams')
                            ->color(Color::hex('#2f2a6b'))
                            ->outlined(false)
                            ->stateless(false),
                    ])
                    ->resolveUserUsing(function (string $provider, SocialiteUserContract $oauthUser, FilamentSocialitePlugin $plugin) {
                        $email = self::azureEmail($oauthUser);
                        $user = User::where('email', $email)->first();

                        // Guest accounts used to be created with their #EXT# UPN; move them to the real address.
                        if (! $user && $oauthUser->getEmail() !== $email) {
                            $user = User::where('email', $oauthUser->getEmail())->first();
                            $user?->update(['email' => $email]);
                        }

                        return $user;
                    })

                    ->createUserUsing(function (string $provider, SocialiteUserContract $oauthUser, FilamentSocialitePlugin $plugin) {

                        if (! env('ALLOW_REGISTRATION', true)) {
                            return null;
                        }

                        $email = self::azureEmail($oauthUser);
                        $fullName = $oauthUser->getName() ?? $oauthUser->getNickname() ?? $email;
                        $firstName = explode(' ', trim($fullName))[0] ?? 'Unbekannt';

                        return User::create([
                            'display_name' => $firstName,
                            'name' => $fullName,
                            'email' => $email,
                            'password' => Hash::make(Str::random(32)),
                            'email_verified_at' => now(),
                        ]);
                    })
                    ->rememberLogin(true)
                    ->showDivider(false)
                    ->registration(env('ALLOW_REGISTRATION', true))
                    ->userModelClass(User::class),
                FilamentShieldPlugin::make()->gridColumns([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 3,
                ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                BreezyCore::make()
                    ->myProfile(
                        slug: 'profile',
                    )
                    ->myProfileComponents([
                        'personal_info' => PersonalInfo::class,
                    ])
                    ->enableTwoFactorAuthentication()
                    ->enableBrowserSessions(),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                WeekDaysCollection::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * The Azure provider maps email to the userPrincipalName. For B2B guests that is
     * "first_domain.tld#EXT#@tenant.onmicrosoft.com", so prefer the real mail attribute
     * and otherwise decode the guest UPN back to the original address.
     */
    private static function azureEmail(SocialiteUserContract $oauthUser): string
    {
        $raw = $oauthUser->getRaw();
        $upn = $oauthUser->getEmail();

        if (! empty($raw['mail'])) {
            return Str::lower($raw['mail']);
        }

        if (str_contains($upn, '#EXT#')) {
            $local = Str::before($upn, '#EXT#');
            $at = strrpos($local, '_');

            if ($at !== false) {
                return Str::lower(substr_replace($local, '@', $at, 1));
            }
        }

        return $upn;
    }
}
