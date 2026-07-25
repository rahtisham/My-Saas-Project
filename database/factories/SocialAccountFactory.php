<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
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
            'platform' => fake()->randomElement(['facebook', 'instagram']),
            'platform_user_id' => (string) fake()->unique()->numerify('########'),
            'name' => fake()->company(),
            'page_id' => (string) fake()->numerify('##########'),
            'access_token' => fake()->sha256(),
            'token_expires_at' => now()->addDays(60),
            'profile_picture_url' => fake()->imageUrl(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the account is a Facebook account.
     */
    public function facebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'facebook',
        ]);
    }

    /**
     * Indicate that the account is an Instagram account.
     */
    public function instagram(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'instagram',
        ]);
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the token is expired.
     */
    public function expiredToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'token_expires_at' => now()->subDay(),
        ]);
    }
}
