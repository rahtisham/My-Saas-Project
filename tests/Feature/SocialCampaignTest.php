<?php

use App\Enums\TeamRole;
use App\Models\SocialCampaign;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// -----------------------------------------------------------------------
// Index
// -----------------------------------------------------------------------

test('guests cannot view the social campaigns index', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('social.campaigns.index', ['current_team' => $team->slug]));

    $response->assertRedirect(route('login'));
});

test('team members can view the social campaigns index', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->get(route('social.campaigns.index', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/campaigns/Index')
        ->has('campaigns'),
    );
});

test('social campaigns index only shows campaigns belonging to the team', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $ownCampaign = SocialCampaign::factory()->for($team)->create();
    SocialCampaign::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('social.campaigns.index', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/campaigns/Index')
        ->has('campaigns', 1)
        ->where('campaigns.0.id', $ownCampaign->id),
    );
});

// -----------------------------------------------------------------------
// Create
// -----------------------------------------------------------------------

test('owners can view the create campaign page', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $response = $this
        ->actingAs($user)
        ->get(route('social.campaigns.create', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/campaigns/Create')
        ->has('posts'),
    );
});

test('members cannot view the create campaign page', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->get(route('social.campaigns.create', ['current_team' => $team->slug]));

    $response->assertForbidden();
});

// -----------------------------------------------------------------------
// Store
// -----------------------------------------------------------------------

test('owners can create a campaign', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $response = $this
        ->actingAs($user)
        ->post(route('social.campaigns.store', ['current_team' => $team->slug]), [
            'name' => 'Summer Sale Campaign',
            'description' => 'Promote summer products',
            'platform' => 'facebook',
            'budget' => 500.00,
            'objective' => 'Engagement',
            'start_date' => now()->addDay()->format('Y-m-d\TH:i'),
            'end_date' => now()->addDays(30)->format('Y-m-d\TH:i'),
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('social_campaigns', [
        'team_id' => $team->id,
        'name' => 'Summer Sale Campaign',
        'platform' => 'facebook',
        'budget' => 500.00,
    ]);
});

test('creating a campaign requires a name', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $response = $this
        ->actingAs($user)
        ->post(route('social.campaigns.store', ['current_team' => $team->slug]), [
            'platform' => 'facebook',
        ]);

    $response->assertSessionHasErrors('name');
});

test('admins can create a campaign', function () {
    [$user, $team] = userWithTeam(TeamRole::Admin);

    $response = $this
        ->actingAs($user)
        ->post(route('social.campaigns.store', ['current_team' => $team->slug]), [
            'name' => 'Admin Campaign',
            'platform' => 'instagram',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('social_campaigns', [
        'team_id' => $team->id,
        'name' => 'Admin Campaign',
    ]);
});

// -----------------------------------------------------------------------
// Edit
// -----------------------------------------------------------------------

test('owners can view the edit campaign page', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $campaign = SocialCampaign::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('social.campaigns.edit', ['current_team' => $team->slug, 'campaign' => $campaign->id]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('social/campaigns/Edit')
        ->where('campaign.id', $campaign->id),
    );
});

// -----------------------------------------------------------------------
// Update
// -----------------------------------------------------------------------

test('owners can update a campaign', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $campaign = SocialCampaign::factory()->for($team)->create(['name' => 'Old Campaign']);

    $response = $this
        ->actingAs($user)
        ->put(route('social.campaigns.update', ['current_team' => $team->slug, 'campaign' => $campaign->id]), [
            'name' => 'Updated Campaign',
            'platform' => $campaign->platform,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('social_campaigns', [
        'id' => $campaign->id,
        'name' => 'Updated Campaign',
    ]);
});

// -----------------------------------------------------------------------
// Destroy
// -----------------------------------------------------------------------

test('owners can delete a campaign', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $campaign = SocialCampaign::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('social.campaigns.destroy', ['current_team' => $team->slug, 'campaign' => $campaign->id]));

    $response->assertRedirect(route('social.campaigns.index', ['current_team' => $team->slug]));

    $this->assertSoftDeleted('social_campaigns', [
        'id' => $campaign->id,
    ]);
});

test('users outside the team cannot delete a campaign', function () {
    $outsider = User::factory()->create();
    [$owner, $team] = userWithTeam(TeamRole::Owner);
    $campaign = SocialCampaign::factory()->for($team)->create();

    $response = $this
        ->actingAs($outsider)
        ->delete(route('social.campaigns.destroy', ['current_team' => $team->slug, 'campaign' => $campaign->id]));

    $response->assertForbidden();
});

// -----------------------------------------------------------------------
// Model
// -----------------------------------------------------------------------

test('social campaign model correctly identifies active status', function () {
    $active = SocialCampaign::factory()->for(Team::factory()->create())->active()->create();
    $draft = SocialCampaign::factory()->for(Team::factory()->create())->create();

    expect($active->isActive())->toBeTrue();
    expect($draft->isActive())->toBeFalse();
});

test('social campaign model checks budget correctly', function () {
    $campaign = SocialCampaign::factory()->for(Team::factory()->create())->create([
        'budget' => 1000,
        'spent' => 500,
    ]);

    expect($campaign->hasBudgetRemaining())->toBeTrue();

    $exhausted = SocialCampaign::factory()->for(Team::factory()->create())->create([
        'budget' => 1000,
        'spent' => 1000,
    ]);

    expect($exhausted->hasBudgetRemaining())->toBeFalse();
});
