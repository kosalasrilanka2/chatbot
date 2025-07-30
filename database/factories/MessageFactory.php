<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $senderType = $this->faker->randomElement(['user', 'agent', 'system']);
        $senderId = null;

        if ($senderType === 'user') {
            $senderId = User::factory();
        } elseif ($senderType === 'agent') {
            $senderId = Agent::factory();
        }

        return [
            'conversation_id' => Conversation::factory(),
            'content' => $this->faker->paragraph(),
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'is_read' => $this->faker->boolean(),
            'read_at' => $this->faker->optional()->dateTimeBetween('-1 hour', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function fromUser(User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type' => 'user',
            'sender_id' => $user ? $user->id : User::factory(),
        ]);
    }

    public function fromAgent(Agent $agent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type' => 'agent',
            'sender_id' => $agent ? $agent->id : Agent::factory(),
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type' => 'system',
            'sender_id' => null,
            'content' => $this->faker->randomElement([
                'Conversation assigned to agent',
                'Agent is now online',
                'Conversation ended by user',
                'Agent went offline'
            ]),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}
