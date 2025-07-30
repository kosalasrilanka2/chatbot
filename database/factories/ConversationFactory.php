<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'user_id' => User::factory(),
            'agent_id' => null,
            'status' => $this->faker->randomElement(['waiting', 'active', 'closed']),
            'preferred_language' => $this->faker->randomElement(['SI', 'TI', 'EN', null]),
            'preferred_domain' => $this->faker->randomElement(['FINANCE', 'HR', 'IT', 'NETWORK', null]),
            'priority' => $this->faker->randomElement(['normal', 'high']),
            'language_match_score' => $this->faker->numberBetween(0, 5),
            'domain_match_score' => $this->faker->numberBetween(0, 5),
            'last_activity' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waiting',
            'agent_id' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'agent_id' => Agent::factory(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'agent_id' => Agent::factory(),
        ]);
    }

    public function withSkills(string $language = null, string $domain = null): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_language' => $language,
            'preferred_domain' => $domain,
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }
}
