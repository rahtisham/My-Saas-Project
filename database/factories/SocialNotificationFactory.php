<?php

namespace Database\Factories;

use App\Models\SocialNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialNotification>
 */
class SocialNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['campaign_started', 'post_published', 'post_failed', 'campaign_failed']),
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'data' => null,
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the notification has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    /**
     * Indicate that the notification is about a published post.
     */
    public function postPublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'post_published',
            'title' => 'Post Published',
            'message' => 'Your post has been published successfully.',
        ]);
    }

    /**
     * Indicate that the notification is about a failed post.
     */
    public function postFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'post_failed',
            'title' => 'Post Failed',
            'message' => 'Your post failed to publish.',
        ]);
    }

    /**
     * Indicate that the notification is about a started campaign.
     */
    public function campaignStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'campaign_started',
            'title' => 'Campaign Started',
            'message' => 'Your campaign has been started successfully.',
        ]);
    }
}
