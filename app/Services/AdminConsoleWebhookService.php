<?php

namespace App\Services;

use App\Jobs\NotifyAdminConsoleJob;
use App\Models\Organization;

class AdminConsoleWebhookService
{
    public function notifyTenantRegistered(Organization $organization): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        NotifyAdminConsoleJob::dispatch($organization->id);
    }

    public function isConfigured(): bool
    {
        $webhookUrl = config('admin_console.webhook_url');
        $webhookSecret = config('admin_console.webhook_secret');
        $applicationSlug = config('admin_console.application_slug');

        return is_string($webhookUrl) && $webhookUrl !== ''
            && is_string($webhookSecret) && $webhookSecret !== ''
            && is_string($applicationSlug) && $applicationSlug !== '';
    }
}
