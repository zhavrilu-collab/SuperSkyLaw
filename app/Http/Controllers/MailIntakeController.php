<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\Organization;
use App\Services\MailIntakeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailIntakeController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly MailIntakeService $intake) {}

    public function index(string $slug): View
    {
        $this->authorizePerm('matters.manage');
        $this->authorizeFeature('email_intake');
        $office = $this->office();

        return view('organization.mail.index', [
            'token' => $office->feedToken('mail_intake_token'),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('matters.manage');
        $this->authorizeFeature('email_intake');
        $data = $request->validate([
            'from' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
        ]);
        $this->intake->file($this->office(), $data['from'], $data['subject'], $data['body']);

        return back()->with('status', 'Poruka je spremljena u predmet.');
    }

    public function receive(Request $request, string $token): JsonResponse
    {
        $organization = Organization::query()->where('mail_intake_token', $token)->firstOrFail();
        app()->instance('currentOrganization', $organization);
        $data = $request->validate([
            'from' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
        ]);
        $entry = $this->intake->file($organization, $data['from'], $data['subject'], $data['body']);

        return response()->json(['id' => $entry->id, 'matter_id' => $entry->matter_id]);
    }
}
