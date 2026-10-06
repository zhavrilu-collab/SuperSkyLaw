<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\OrganizationUser;

class OrganizationRbacService
{
    /** @var array<string, list<OrganizationRole>> */
    private const PERMISSIONS = [
        'dashboard.view' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee, OrganizationRole::Secretary],
        'team.manage' => [OrganizationRole::Owner],
        'settings.manage' => [OrganizationRole::Owner],
        'parties.view' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee, OrganizationRole::Secretary],
        'parties.manage' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Secretary],
        'matters.view' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee, OrganizationRole::Secretary],
        'matters.manage' => [OrganizationRole::Owner, OrganizationRole::Lawyer],
        'matters.delete' => [OrganizationRole::Owner],
        'walls.manage' => [OrganizationRole::Owner],
        'calendar.view' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee, OrganizationRole::Secretary],
        'calendar.manage' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Secretary],
        'time.view' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee],
        'time.manage' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee],
        'time.approve' => [OrganizationRole::Owner],
        'finance.view' => [OrganizationRole::Owner, OrganizationRole::Secretary],
        'finance.manage' => [OrganizationRole::Owner, OrganizationRole::Secretary],
        'documents.view' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Trainee, OrganizationRole::Secretary],
        'documents.manage' => [OrganizationRole::Owner, OrganizationRole::Lawyer, OrganizationRole::Secretary],
        'documents.delete' => [OrganizationRole::Owner],
    ];

    public function roleForUser(int $organizationId, int $userId): ?OrganizationRole
    {
        $membership = OrganizationUser::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->first();

        return $membership?->role;
    }

    public function can(int $organizationId, int $userId, string $permission): bool
    {
        $role = $this->roleForUser($organizationId, $userId);

        if ($role === null) {
            return false;
        }

        $allowed = self::PERMISSIONS[$permission] ?? [];

        return in_array($role, $allowed, true);
    }

    public function authorize(int $organizationId, int $userId, string $permission): void
    {
        if (! $this->can($organizationId, $userId, $permission)) {
            abort(403, 'Nemate ovlasti za ovu radnju.');
        }
    }

    public function isOwner(int $organizationId, int $userId): bool
    {
        return $this->roleForUser($organizationId, $userId) === OrganizationRole::Owner;
    }

    public function seesAllMatters(OrganizationRole $role): bool
    {
        return in_array($role, [OrganizationRole::Owner, OrganizationRole::Secretary], true);
    }
}
