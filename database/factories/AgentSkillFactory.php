<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentSkill>
 */
class AgentSkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skillType = fake()->randomElement(['language', 'domain']);
        
        if ($skillType === 'language') {
            $skillCode = fake()->randomElement(['EN', 'SI', 'TI']);
            $skillName = match($skillCode) {
                'EN' => 'English',
                'SI' => 'Sinhala', 
                'TI' => 'Tamil',
                default => 'English'
            };
        } else {
            $skillCode = fake()->randomElement(['FINANCE', 'HR', 'IT', 'NETWORK']);
            $skillName = match($skillCode) {
                'FINANCE' => 'Finance',
                'HR' => 'Human Resources',
                'IT' => 'Information Technology',
                'NETWORK' => 'Network Support',
                default => 'General'
            };
        }
        
        return [
            'agent_id' => Agent::factory(),
            'skill_type' => $skillType,
            'skill_code' => $skillCode,
            'skill_name' => $skillName,
            'proficiency_level' => fake()->numberBetween(1, 5),
        ];
    }

    /**
     * State for language skills
     */
    public function language(string $code = null): static
    {
        $code = $code ?? fake()->randomElement(['EN', 'SI', 'TI']);
        $name = match($code) {
            'EN' => 'English',
            'SI' => 'Sinhala', 
            'TI' => 'Tamil',
            default => 'English'
        };
        
        return $this->state(fn (array $attributes) => [
            'skill_type' => 'language',
            'skill_code' => $code,
            'skill_name' => $name,
        ]);
    }

    /**
     * State for domain skills
     */
    public function domain(string $code = null): static
    {
        $code = $code ?? fake()->randomElement(['FINANCE', 'HR', 'IT', 'NETWORK']);
        $name = match($code) {
            'FINANCE' => 'Finance',
            'HR' => 'Human Resources',
            'IT' => 'Information Technology',
            'NETWORK' => 'Network Support',
            default => 'General'
        };
        
        return $this->state(fn (array $attributes) => [
            'skill_type' => 'domain',
            'skill_code' => $code,
            'skill_name' => $name,
        ]);
    }

    /**
     * State for expert level skills
     */
    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'proficiency_level' => 5,
        ]);
    }

    /**
     * State for beginner level skills
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'proficiency_level' => 1,
        ]);
    }
}
