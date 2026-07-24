<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine if the user can view any products for the given team.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine if the user can create a product for the given team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false;
    }

    /**
     * Determine if the user can update the given product.
     */
    public function update(User $user, Product $product): bool
    {
        $team = $product->team()->withTrashed()->first();

        return $user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false;
    }

    /**
     * Determine if the user can delete the given product.
     */
    public function delete(User $user, Product $product): bool
    {
        $team = $product->team()->withTrashed()->first();

        return $user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false;
    }
}
