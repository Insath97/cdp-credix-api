<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'dev@localhost.com', 'username' => 'devadmin'],
            [
                'name' => 'Development Admin',
                'password' => bcrypt('password'),
                'is_active' => true,
                'can_login' => true,
            ]
        );

        $user->assignRole('Super Admin');

        // The account automated status changes are attributed to. Seeded here
        // so it exists from the start rather than being created lazily by the
        // first nightly job that needs it. See User::systemUserId().
        User::firstOrCreate(
            ['username' => User::SYSTEM_USERNAME],
            [
                'name'      => 'System',
                'email'     => null,
                'password'  => bcrypt(Str::random(40)),
                'user_type' => 'admin',
                'is_active' => false,
                'can_login' => false,
            ]
        );

        $this->command->info('Development admin user created!');
        $this->command->info('Email: dev@localhost.com');
        $this->command->info('Password: password');
        $this->command->info('System user created (cannot log in).');
    }
}
