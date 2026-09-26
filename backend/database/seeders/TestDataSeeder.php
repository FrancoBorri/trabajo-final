<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
    }

    private function seedUsers(): void
    {
        $users = [
            ['name' => 'admin', 'lastName' => 'admin', 'email' => 'admin@test.com', 'phone' => '2920-123458', 'role' => 'admin'],
            ['name' => 'client', 'lastName' => 'client', 'email' => 'client@test.com', 'phone' => '2920-123459', 'role' => 'client'],
            ['name' => 'profesional', 'lastName' => 'profesional', 'email' => 'profesional@test.com', 'phone' => '2920-123460', 'role' => 'profesional'],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'lastName' => $data['lastName'],
                    'phone' => $data['phone'],
                    'role' => $data['role'],
                    'password' => Hash::make('pass'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command?->info('Usuario administrador creado. Credenciales: admin@test.com / pass');
    }
}
