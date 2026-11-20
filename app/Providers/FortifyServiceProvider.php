<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ResetsUserPasswords::class, fn () => new class implements ResetsUserPasswords
        {
            public function reset($user, array $input): void
            {
                Validator::make($input, ['password' => ['required', 'confirmed', Password::defaults()]])->validate();
                $user->forceFill(['password' => Hash::make($input['password'])])->save();
                $user->tokens()->delete();
            }
        });
    }

    public function boot(): void
    {
        Fortify::loginView(fn () => Inertia::render('auth/Login', ['canResetPassword' => true, 'status' => session('status')]));
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('auth/ForgotPassword', ['status' => session('status')]));
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', ['email' => $request->email, 'token' => $request->route('token')]));
        Fortify::verifyEmailView(fn () => Inertia::render('auth/VerifyEmail', ['status' => session('status')]));
        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }
}
