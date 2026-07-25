<?php

namespace Database\Factories;

use App\Models\SocialMedia;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialMedia>
 */
class SocialMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['image', 'video']);
        $extension = $type === 'image' ? 'jpg' : 'mp4';

        return [
            'team_id' => Team::factory(),
            'file_path' => 'social-media/'.fake()->uuid().'.'.$extension,
            'file_name' => fake()->uuid().'.'.$extension,
            'mime_type' => $type === 'image' ? 'image/jpeg' : 'video/mp4',
            'file_size' => fake()->numberBetween(10000, 10000000),
            'type' => $type,
            'platform' => null,
        ];
    }

    /**
     * Indicate that the media is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_name' => fake()->uuid().'.jpg',
        ]);
    }

    /**
     * Indicate that the media is a video.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'video',
            'mime_type' => 'video/mp4',
            'file_name' => fake()->uuid().'.mp4',
        ]);
    }
}
