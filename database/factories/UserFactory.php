<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * Kept deliberately in step with the users migration rather than with the
     * model. The stock Laravel factory this replaced set `email_verified_at`,
     * a column this schema has never had, so every create() died on "no such
     * column" -- and it left `username` unset, which is NOT NULL and unique
     * here. Between them, no test could build a user at all.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                => fake()->name(),
            'username'            => fake()->unique()->userName(),
            'email'               => fake()->unique()->safeEmail(),
            'password'            => static::$password ??= Hash::make('password'),
            'password_changed_at' => now(),
            'user_type'           => 'admin',
            'is_active'           => true,
            'can_login'           => true,
            'remember_token'      => Str::random(10),
        ];
    }

    /**
     * An account that has never chosen its own password, which is what
     * EnsurePasswordChanged holds at the door.
     */
    public function passwordNeverChanged(): static
    {
        return $this->state(fn (array $attributes) => [
            'password_changed_at' => null,
        ]);
    }

    /**
     * A deactivated account: canLogin() is false, so the guard refuses it.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
