<?php

namespace App\Support;

use App\Enums\OrganizationStatus;
use App\Models\OrganizationUser;
use Illuminate\Support\Collection;

class UserOrganizationNavigation
{
    /**
     * @return Collection<int, OrganizationUser>
     */
    public static function organizationUsers(int $userId): Collection
    {
        return OrganizationUser::query()
            ->with('organization')
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * @param  Collection<int, OrganizationUser>  $orgUsers
     */
    public static function hasPending(Collection $orgUsers): bool
    {
        return $orgUsers->contains(
            fn (OrganizationUser $orgUser) => $orgUser->organization->status === OrganizationStatus::Pending,
        );
    }

    /**
     * @param  Collection<int, OrganizationUser>  $orgUsers
     */
    public static function landingPath(Collection $orgUsers): ?string
    {
        $active = $orgUsers->filter(
            fn (OrganizationUser $orgUser) => $orgUser->organization->status === OrganizationStatus::Active,
        );

        if ($active->isEmpty()) {
            return null;
        }

        if ($active->count() === 1) {
            return route('organization.dashboard', [
                'slug' => $active->first()->organization->slug,
            ], false);
        }

        return route('organization.pick', [], false);
    }

    /**
     * @param  Collection<int, OrganizationUser>  $orgUsers
     */
    public static function postAuthRedirectPath(Collection $orgUsers): ?string
    {
        if ($orgUsers->isEmpty()) {
            return route('register.organization', [], false);
        }

        $landing = self::landingPath($orgUsers);

        if ($landing !== null) {
            return $landing;
        }

        if (self::hasPending($orgUsers)) {
            return route('registration.pending', [], false);
        }

        return null;
    }
}
