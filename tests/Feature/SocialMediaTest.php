<?php

use App\Enums\TeamRole;
use App\Models\SocialMedia;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// -----------------------------------------------------------------------
// Index
// -----------------------------------------------------------------------

test('guests cannot view the social media index', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('social.media.index', ['current_team' => $team->slug]));

    $response->assertRedirect(route('login'));
});

test('team members can view the social media index', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->get(route('social.media.index', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/media/Index')
        ->has('media'),
    );
});

test('social media index only shows media belonging to the team', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $ownMedia = SocialMedia::factory()->for($team)->image()->create();
    SocialMedia::factory()->image()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('social.media.index', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/media/Index')
        ->has('media', 1)
        ->where('media.0.id', $ownMedia->id),
    );
});

// -----------------------------------------------------------------------
// Create
// -----------------------------------------------------------------------

test('team members can view the upload media page', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->get(route('social.media.create', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/media/Upload'),
    );
});

// -----------------------------------------------------------------------
// Destroy
// -----------------------------------------------------------------------

test('owners can delete media', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $media = SocialMedia::factory()->for($team)->image()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('social.media.destroy', ['current_team' => $team->slug, 'media' => $media->id]));

    $response->assertRedirect(route('social.media.index', ['current_team' => $team->slug]));

    $this->assertDatabaseMissing('social_media', [
        'id' => $media->id,
    ]);
});

test('users outside the team cannot delete media', function () {
    $outsider = User::factory()->create();
    [$owner, $team] = userWithTeam(TeamRole::Owner);
    $media = SocialMedia::factory()->for($team)->image()->create();

    $response = $this
        ->actingAs($outsider)
        ->delete(route('social.media.destroy', ['current_team' => $team->slug, 'media' => $media->id]));

    $response->assertForbidden();

    $this->assertDatabaseHas('social_media', [
        'id' => $media->id,
    ]);
});

// -----------------------------------------------------------------------
// Model
// -----------------------------------------------------------------------

test('social media model returns correct url', function () {
    $media = SocialMedia::factory()->image()->create([
        'file_path' => 'social-media/test-image.jpg',
    ]);

    expect($media->url)->toContain('social-media/test-image.jpg');
});

test('social media model correctly identifies image type', function () {
    $image = SocialMedia::factory()->image()->create();
    $video = SocialMedia::factory()->video()->create();

    expect($image->type)->toBe('image');
    expect($video->type)->toBe('video');
});
