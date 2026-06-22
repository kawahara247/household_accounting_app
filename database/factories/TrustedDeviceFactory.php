<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrustedDevice>
 */
class TrustedDeviceFactory extends Factory
{
    protected $model = TrustedDevice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'token'        => fake()->unique()->sha1(),
            'device_name'  => 'Test Browser',
            'expires_at'   => now()->addYear(),
            'last_used_at' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function token(string $token): static
    {
        return $this->state(['token' => $token]);
    }

    public function deviceName(string $name): static
    {
        return $this->state(['device_name' => $name]);
    }

    public function valid(): static
    {
        return $this->state(['expires_at' => now()->addYear()]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
