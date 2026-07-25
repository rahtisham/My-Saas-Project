<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\SocialCampaign;
use App\Models\Team;
use App\Models\User;

class SocialCampaignPolicy
{
    /**
     * Determine if the user can view any social campaigns for the given team.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine if the user can view the given social campaign.
     */
    public function view(User $user, SocialCampaign $campaign): bool
    {
        $team = $campaign->team;

        return $user->belongsToTeam($team);
    }

    /**
     * Determine if the user can create a social campaign for the given team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageCampaigns);
    }

    /**
     * Determine if the user can update the given social campaign.
     */
    public function update(User $user, SocialCampaign $campaign): bool
    {
        $team = $campaign->team;

        return $user->hasTeamPermission($team, TeamPermission::ManageCampaigns);
    }

    /**
     * Determine if the user can delete the given social campaign.
     */
    public function delete(User $user, SocialCampaign $campaign): bool
    {
        $team = $campaign->team;

        return $user->hasTeamPermission($team, TeamPermission::ManageCampaigns);
    }
}
