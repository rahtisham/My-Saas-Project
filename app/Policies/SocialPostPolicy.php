<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\SocialPost;
use App\Models\Team;
use App\Models\User;

class SocialPostPolicy
{
    /**
     * Determine if the user can view any social posts for the given team.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine if the user can view the given social post.
     */
    public function view(User $user, SocialPost $post): bool
    {
        $team = $post->team;

        return $user->belongsToTeam($team);
    }

    /**
     * Determine if the user can create a social post for the given team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::ManageSocialMedia);
    }

    /**
     * Determine if the user can update the given social post.
     */
    public function update(User $user, SocialPost $post): bool
    {
        $team = $post->team;

        return $user->hasTeamPermission($team, TeamPermission::ManageSocialMedia);
    }

    /**
     * Determine if the user can delete the given social post.
     */
    public function delete(User $user, SocialPost $post): bool
    {
        $team = $post->team;

        return $user->hasTeamPermission($team, TeamPermission::ManageSocialMedia);
    }

    /**
     * Determine if the user can publish the given social post.
     */
    public function publish(User $user, SocialPost $post): bool
    {
        $team = $post->team;

        return $user->hasTeamPermission($team, TeamPermission::PublishSocialPosts);
    }
}
