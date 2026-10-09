<?php

namespace Database\Seeders;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CourtSeeder::class,
            DisputeCategorySeeder::class,
        ]);

        $partner = User::query()->updateOrCreate(
            ['email' => 'partner@law.test'],
            [
                'name' => 'Ana Partner',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $lawyer = User::query()->updateOrCreate(
            ['email' => 'odvjetnik@law.test'],
            [
                'name' => 'Marko Odvjetnik',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $organizations = [
            ['name' => 'Odvjetnički ured Horvat', 'slug' => 'ured-horvat', 'status' => OrganizationStatus::Active, 'plan' => 'standard', 'email' => 'ured@horvat.hr', 'oib' => '12345678903', 'city' => 'Zagreb'],
            ['name' => 'Odvjetničko društvo Nova', 'slug' => 'od-nova', 'status' => OrganizationStatus::Pending, 'plan' => 'basic', 'email' => 'office@od-nova.hr', 'oib' => '10987654326', 'city' => 'Split'],
        ];

        foreach ($organizations as $organizationData) {
            $organization = Organization::query()->updateOrCreate(
                ['slug' => $organizationData['slug']],
                $organizationData,
            );

            OrganizationUser::query()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $partner->id,
                ],
                ['role' => OrganizationRole::Owner],
            );
        }

        $activeOrg = Organization::query()->where('slug', 'ured-horvat')->first();

        if ($activeOrg !== null) {
            OrganizationUser::query()->updateOrCreate(
                [
                    'organization_id' => $activeOrg->id,
                    'user_id' => $lawyer->id,
                ],
                ['role' => OrganizationRole::Lawyer],
            );
        }
    }
}
