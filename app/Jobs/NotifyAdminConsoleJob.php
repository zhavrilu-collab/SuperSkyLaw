<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Support\AdminConsoleHttp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyAdminConsoleJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $organizationId,
    ) {}

    public function handle(): void
    {
        $organization = Organization::query()->find($this->organizationId);

        if ($organization === null) {
            return;
        }

        $webhookUrl = config('admin_console.webhook_url');
        $webhookSecret = config('admin_console.webhook_secret');
        $applicationSlug = config('admin_console.application_slug');

        if (! is_string($webhookUrl) || $webhookUrl === ''
            || ! is_string($webhookSecret) || $webhookSecret === ''
            || ! is_string($applicationSlug) || $applicationSlug === '') {
            return;
        }

        AdminConsoleHttp::client($webhookSecret)
            ->post($webhookUrl, [
                'application_slug' => $applicationSlug,
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'slug' => $organization->slug,
                    'status' => $organization->status->value,
                    'plan' => $organization->plan,
                    'email' => $organization->email,
                ],
            ])
            ->throw();
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('Admin konzola webhook nije uspio.', [
            'organization_id' => $this->organizationId,
            'message' => $exception->getMessage(),
        ]);
    }
}
