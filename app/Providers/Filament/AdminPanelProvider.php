<?php

namespace App\Providers\Filament;


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
use Filament\Pages;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
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
            ->login()
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
                        return User::where('email', $oauthUser->getEmail())->first();
                    })


                    ->createUserUsing(function (string $provider, SocialiteUserContract $oauthUser, FilamentSocialitePlugin $plugin) {

                        if (!env('ALLOW_REGISTRATION', true)) return null;

                        $fullName = $oauthUser->getName() ?? $oauthUser->getNickname() ?? $oauthUser->getEmail();
                        $firstName = explode(' ', trim($fullName))[0] ?? 'Unbekannt';

                        return User::create([
                            'display_name' => $firstName,
                            'name' => $fullName,
                            'email' => $oauthUser->getEmail(),
                            'password' => Hash::make(Str::random(32)),
                            'email_verified_at' => now(),
                        ]);
                    })
                    ->rememberLogin(true)
                    ->registration(env('ALLOW_REGISTRATION', true))
                    ->userModelClass(User::class),
                FilamentShieldPlugin::make()->gridColumns([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 3
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
                    ->enableBrowserSessions()
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
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
}
