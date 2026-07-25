<?php

namespace Database\Factories;

use App\Models\SocialCampaign;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialCampaign>
 */
class SocialCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'social_post_id' => null,
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'status' => 'draft',
            'platform' => fake()->randomElement(['facebook', 'instagram']),
            'budget' => fake()->randomFloat(2, 50, 5000),
            'spent' => 0,
            'objective' => fake()->randomElement(['Engagement', 'Traffic', 'Conversions', 'Brand Awareness', 'Reach']),
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(30),
            'platform_campaign_id' => null,
            'targeting' => null,
            'insights' => null,
            'failure_reason' => null,
        ];
    }

    /**
     * Indicate that the campaign is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the campaign is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'end_date' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the campaign failed.
     */
    public function failed(string $reason = 'Budget exceeded'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Indicate that the campaign is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }
}
