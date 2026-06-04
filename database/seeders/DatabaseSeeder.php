<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@localhost'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('changeme'),
                'is_admin' => true,
                'is_active'=> true,
                'role'     => 'admin',
            ]
        );

        $this->command->info('Admin user created: admin@localhost / changeme');
        $this->command->warn('IMPORTANT: Change the admin password immediately after first login!');
    }
}
