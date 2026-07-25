<?php

use App\Enums\TeamRole;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// -----------------------------------------------------------------------
// Index
// -----------------------------------------------------------------------

test('guests cannot view the social posts index', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('social.posts.index', ['current_team' => $team->slug]));

    $response->assertRedirect(route('login'));
});

test('team members can view the social posts index', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->get(route('social.posts.index', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/posts/Index')
        ->has('posts'),
    );
});

test('social posts index only shows posts belonging to the team', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $account = SocialAccount::factory()->for($team)->facebook()->create();

    $ownPost = SocialPost::factory()->for($team)->for($account)->create();
    SocialPost::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('social.posts.index', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/posts/Index')
        ->has('posts', 1)
        ->where('posts.0.id', $ownPost->id),
    );
});

// -----------------------------------------------------------------------
// Create
// -----------------------------------------------------------------------

test('owners can view the create post page', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $response = $this
        ->actingAs($user)
        ->get(route('social.posts.create', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/posts/Create')
        ->has('accounts')
        ->has('media'),
    );
});

test('members can view the create post page', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->get(route('social.posts.create', ['current_team' => $team->slug]));

    $response->assertOk();
});

// -----------------------------------------------------------------------
// Store
// -----------------------------------------------------------------------

test('owners can create a draft post', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $account = SocialAccount::factory()->for($team)->facebook()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('social.posts.store', ['current_team' => $team->slug]), [
            'social_account_id' => $account->id,
            'caption' => 'Test post caption',
            'platform' => 'facebook',
            'visibility' => 'public',
            'status' => 'draft',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('social_posts', [
        'team_id' => $team->id,
        'caption' => 'Test post caption',
        'platform' => 'facebook',
        'status' => 'draft',
    ]);
});

test('creating a post requires a social account', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $response = $this
        ->actingAs($user)
        ->post(route('social.posts.store', ['current_team' => $team->slug]), [
            'caption' => 'No account post',
            'platform' => 'facebook',
            'visibility' => 'public',
            'status' => 'draft',
        ]);

    $response->assertSessionHasErrors('social_account_id');
});

test('creating a post requires a valid platform', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $account = SocialAccount::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->post(route('social.posts.store', ['current_team' => $team->slug]), [
            'social_account_id' => $account->id,
            'caption' => 'Bad platform post',
            'platform' => 'twitter',
            'visibility' => 'public',
            'status' => 'draft',
        ]);

    $response->assertSessionHasErrors('platform');
});

// -----------------------------------------------------------------------
// Edit
// -----------------------------------------------------------------------

test('owners can view the edit post page', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $account = SocialAccount::factory()->for($team)->create();
    $post = SocialPost::factory()->for($team)->for($account)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('social.posts.edit', ['current_team' => $team->slug, 'post' => $post->id]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/posts/Edit')
        ->where('post.id', $post->id),
    );
});

// -----------------------------------------------------------------------
// Update
// -----------------------------------------------------------------------

test('owners can update a post', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $account = SocialAccount::factory()->for($team)->create();
    $post = SocialPost::factory()->for($team)->for($account)->create(['caption' => 'Old caption']);

    $response = $this
        ->actingAs($user)
        ->put(route('social.posts.update', ['current_team' => $team->slug, 'post' => $post->id]), [
            'social_account_id' => $account->id,
            'caption' => 'New caption',
            'platform' => $post->platform,
            'visibility' => $post->visibility,
            'status' => $post->status,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('social_posts', [
        'id' => $post->id,
        'caption' => 'New caption',
    ]);
});

// -----------------------------------------------------------------------
// Destroy
// -----------------------------------------------------------------------

test('owners can delete a post', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $account = SocialAccount::factory()->for($team)->create();
    $post = SocialPost::factory()->for($team)->for($account)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('social.posts.destroy', ['current_team' => $team->slug, 'post' => $post->id]));

    $response->assertRedirect(route('social.posts.index', ['current_team' => $team->slug]));

    $this->assertSoftDeleted('social_posts', [
        'id' => $post->id,
    ]);
});

test('users outside the team cannot delete a post', function () {
    $outsider = User::factory()->create();
    [$owner, $team] = userWithTeam(TeamRole::Owner);
    $account = SocialAccount::factory()->for($team)->create();
    $post = SocialPost::factory()->for($team)->for($account)->create();

    $response = $this
        ->actingAs($outsider)
        ->delete(route('social.posts.destroy', ['current_team' => $team->slug, 'post' => $post->id]));

    $response->assertForbidden();
});

// -----------------------------------------------------------------------
// Dashboard
// -----------------------------------------------------------------------

test('team members can view the social dashboard', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->get(route('social.dashboard', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/Dashboard')
        ->has('stats')
        ->has('recentPosts'),
    );
});
