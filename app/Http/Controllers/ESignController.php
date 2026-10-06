<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\MatterDocument;
use App\Services\ESignService;
use Illuminate\Http\RedirectResponse;

class ESignController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly ESignService $signatures) {}

    public function store(string $slug, int $document): RedirectResponse
    {
        $this->authorizePerm('documents.manage');
        $this->authorizeFeature('esign');
        $model = MatterDocument::query()->findOrFail($document);
        $this->findVisibleMatter($model->matter_id);
        $signature = $this->signatures->submit($model);

        return back()->with('status', $signature->status->label().'.');
    }
}
