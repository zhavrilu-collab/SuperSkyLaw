<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\OfficeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    use ResolvesOffice;

    public function index(string $slug): View
    {
        $notifications = OfficeNotification::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->get();

        OfficeNotification::query()
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('organization.notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function read(string $slug, int $notification): RedirectResponse
    {
        $model = OfficeNotification::query()->where('user_id', auth()->id())->findOrFail($notification);
        $model->forceFill(['read_at' => now()])->save();

        return back();
    }
}
