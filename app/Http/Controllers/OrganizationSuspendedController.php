<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\View\View;

class OrganizationSuspendedController extends Controller
{
    public function show(string $slug): View
    {
        $organization = Organization::query()->where('slug', $slug)->firstOrFail();

        return view('auth.organization-suspended', [
            'organization' => $organization,
        ]);
    }
}
