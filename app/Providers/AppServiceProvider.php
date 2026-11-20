<?php

namespace App\Providers;

use App\Enums\Role;
use App\Policies\WorkspacePolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('view-dashboard', [WorkspacePolicy::class, 'dashboard']);
        Gate::define('access-residents', [WorkspacePolicy::class, 'residents']);
        Gate::define('access-rides', [WorkspacePolicy::class, 'rides']);
        Gate::define('access-municipality', [WorkspacePolicy::class, 'municipality']);
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('wmo-api', fn (Request $r) => Limit::perMinute(120)->by($r->user()?->id ?? $r->ip()));
        RateLimiter::for('bookings', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?? $r->ip()));
        Gate::define('viewApiDocs', fn ($user = null) => app()->environment(['local', 'testing']) || $user?->role === Role::Municipality);
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer'));
        });
    }
}
