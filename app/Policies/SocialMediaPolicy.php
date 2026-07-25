<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\SocialMedia;
use App\Models\Team;
use App\Models\User;

class SocialMediaPolicy
{
    /**
     * Determine if the user can view any media for the given team.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine if the user can create media for the given team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageSocialMedia);
    }

    /**
     * Determine if the user can delete the given media.
     */
    public function delete(User $user, SocialMedia $media): bool
    {
        $team = $media->team;

        return $user->hasTeamPermission($team, TeamPermission::ManageSocialMedia);
    }
}
