<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialPost>
 */
class SocialPostFactory extends Factory
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
            'social_account_id' => SocialAccount::factory(),
            'caption' => fake()->paragraph(),
            'platform' => fake()->randomElement(['facebook', 'instagram']),
            'status' => 'draft',
            'visibility' => 'public',
            'scheduled_at' => null,
            'published_at' => null,
            'platform_post_id' => null,
            'platform_response' => null,
            'failure_reason' => null,
            'retry_count' => 0,
        ];
    }

    /**
     * Indicate that the post is scheduled.
     */
    public function scheduled(?string $scheduledAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt ? carbon($scheduledAt) : now()->addHour(),
        ]);
    }

    /**
     * Indicate that the post is published.
     */
    public function published(?string $publishedAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $publishedAt ? carbon($publishedAt) : now(),
            'platform_post_id' => null,
        ]);
    }

    /**
     * Indicate that the post failed.
     */
    public function failed(string $reason = 'API error'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }
}
