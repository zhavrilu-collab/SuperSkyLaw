<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrganizationStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationSyncController extends Controller
{
    /** @var list<string> */
    private const PLAN_SLUGS = ['basic', 'standard', 'premium'];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'updated_since' => ['nullable', 'date'],
        ]);

        $query = Organization::query()->orderBy('id');

        if (! empty($validated['updated_since'])) {
            $query->where('updated_at', '>=', $validated['updated_since']);
        }

        $organizations = $query->get()->map(fn (Organization $organization) => $this->resource($organization));

        return response()->json([
            'data' => $organizations,
            'meta' => [
                'total' => $organizations->count(),
            ],
        ]);
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'required', Rule::enum(OrganizationStatus::class)],
            'plan' => ['sometimes', 'required', 'string', Rule::in(self::PLAN_SLUGS)],
            'stripe_customer_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'stripe_subscription_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if ($validated === []) {
            return response()->json([
                'message' => 'Potrebno je poslati status, plan ili Stripe podatke.',
            ], 422);
        }

        if (array_key_exists('status', $validated)) {
            $organization->status = $validated['status'];
            $organization->status_changed_at = now();
        }

        if (array_key_exists('plan', $validated)) {
            $organization->plan = $validated['plan'];
        }

        if (array_key_exists('stripe_customer_id', $validated)) {
            $organization->stripe_customer_id = $validated['stripe_customer_id'];
        }

        if (array_key_exists('stripe_subscription_id', $validated)) {
            $organization->stripe_subscription_id = $validated['stripe_subscription_id'];
        }

        $organization->save();

        return response()->json([
            'data' => $this->resource($organization->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'status' => $organization->status->value,
            'plan' => $organization->plan,
            'email' => $organization->email,
            'oib' => $organization->oib,
            'stripe_customer_id' => $organization->stripe_customer_id,
            'stripe_subscription_id' => $organization->stripe_subscription_id,
            'created_at' => $organization->created_at?->toIso8601String(),
            'updated_at' => $organization->updated_at?->toIso8601String(),
        ];
    }
}
