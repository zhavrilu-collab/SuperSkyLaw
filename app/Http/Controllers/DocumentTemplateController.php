<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\DocumentTemplate;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Models\Party;
use App\Services\DocumentMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly DocumentMergeService $merge) {}

    public function index(string $slug): View
    {
        $this->authorizePerm('documents.view');
        $this->authorizeFeature('document_templates');

        return view('organization.documents.templates', [
            'templates' => DocumentTemplate::query()->orderBy('name')->get(),
            'matters' => Matter::query()->visibleTo($this->membership())->orderBy('internal_number')->get(),
            'parties' => Party::query()->orderBy('name')->get(),
        ]);
    }

    public function generate(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('documents.manage');
        $this->authorizeFeature('document_templates');
        $data = $request->validate([
            'template_id' => ['required', 'integer'],
            'matter_id' => ['required', 'integer'],
            'party_id' => ['required', 'integer'],
        ]);

        $template = DocumentTemplate::query()->findOrFail($data['template_id']);
        $matter = $this->findVisibleMatter((int) $data['matter_id']);
        $party = Party::query()->findOrFail($data['party_id']);
        $body = $this->merge->render($template, $this->office(), $matter, $party);
        $name = $template->name.'-'.$matter->internal_number.'.txt';
        $path = 'predmeti/'.$matter->internal_number.'/Predlosci/'.$name;
        Storage::disk('local')->put($path, $body);

        MatterDocument::query()->create([
            'matter_id' => $matter->id,
            'folder' => 'Predlošci',
            'original_name' => $name,
            'path' => $path,
            'size_bytes' => strlen($body),
            'mime' => 'text/plain',
            'version' => 1,
            'uploaded_by_user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('organization.documents.index', $this->office()->slug)
            ->with('status', 'Predložak je spremljen u mapu predmeta.');
    }
}
