<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'status' => $this->faker->randomElement(['online', 'offline', 'busy']),
            'last_seen' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_seen' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
            'last_seen' => $this->faker->dateTimeBetween('-2 hours', '-30 minutes'),
        ]);
    }

    public function busy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'busy',
            'last_seen' => now(),
        ]);
    }
}
