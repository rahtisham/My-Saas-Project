<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\SocialAccount;
use App\Models\Team;
use App\Models\User;

class SocialAccountPolicy
{
    /**
     * Determine if the user can view any social accounts for the given team.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine if the user can create a social account for the given team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageSocialMedia);
    }

    /**
     * Determine if the user can delete the given social account.
     */
    public function delete(User $user, SocialAccount $account): bool
    {
        $team = $account->team;

        return $user->hasTeamPermission($team, TeamPermission::ManageSocialMedia);
    }
}
