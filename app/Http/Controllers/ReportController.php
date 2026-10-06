<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Services\OfficeReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly OfficeReport $reports) {}

    public function index(Request $request, string $slug): View
    {
        $this->authorizePerm('finance.view');
        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month) ?: now();

        return view('organization.finance.report', [
            'report' => $this->reports->forMonth($this->office(), $start),
        ]);
    }
}
