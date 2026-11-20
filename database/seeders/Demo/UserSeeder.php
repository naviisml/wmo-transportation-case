<?php

namespace Database\Seeders\Demo;

use App\Enums\Role;
use App\Models\TaxiCompany;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends DemoDataSeeder
{
    private const ACCOUNTS = [
        [
            'name' => 'Gemeente Utrecht',
            'email' => 'gemeente@example.test',
            'role' => Role::Municipality,
        ],
        [
            'name' => 'Callcenter',
            'email' => 'callcenter@example.test',
            'role' => Role::Callcenter,
        ],
        [
            'name' => 'Demo Taxi A',
            'email' => 'taxi-a@example.test',
            'role' => Role::TaxiCompany,
            'company' => 'Demo Taxi A',
        ],
        [
            'name' => 'Demo Taxi B',
            'email' => 'taxi-b@example.test',
            'role' => Role::TaxiCompany,
            'company' => 'Demo Taxi B',
        ],
    ];

    protected function seed(): void
    {
        $password = config('wmo.demo_password');

        if (! $password) {
            throw new RuntimeException(
                'Set DEMO_PASSWORD before seeding.'
            );
        }

        foreach (self::ACCOUNTS as $account) {
            $company = isset($account['company'])
                ? TaxiCompany::where('name', $account['company'])->firstOrFail()
                : null;

            $user = User::firstOrNew([
                'email' => $account['email'],
            ]);

            $user->forceFill([
                'name' => $account['name'],
                'role' => $account['role'],
                'taxi_company_id' => $company?->id,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            if (! $user->exists || ! Hash::check($password, $user->password)) {
                $user->password = Hash::make($password);
            }

            $user->save();
        }
    }
}
