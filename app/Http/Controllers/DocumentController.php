<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Http\Controllers\Concerns\ResolvesOffice;
use App\Models\AuditLog;
use App\Models\DocumentVersion;
use App\Models\Matter;
use App\Models\MatterDocument;
use App\Services\PlanFeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use ResolvesOffice;

    public function __construct(private readonly PlanFeatureService $plans) {}

    public function index(string $slug): View
    {
        $this->authorizePerm('documents.view');
        $matterIds = Matter::query()->visibleTo($this->membership())->pluck('id');

        return view('organization.documents.index', [
            'documents' => MatterDocument::query()->with(['matter', 'versions', 'signatureRequest'])->whereIn('matter_id', $matterIds)->orderByDesc('id')->get(),
            'matters' => Matter::query()->visibleTo($this->membership())->orderBy('internal_number')->get(),
            'versioning' => $this->plans->allows($this->office(), 'document_versioning'),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $this->authorizePerm('documents.manage');
        $data = $request->validate([
            'matter_id' => ['required', 'integer'],
            'folder' => ['nullable', 'string', 'max:80'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $matter = $this->findVisibleMatter((int) $data['matter_id']);
        $file = $request->file('file');
        $limitMb = $this->plans->limit($this->office(), 'storage_mb') ?? 1024;
        $used = (int) MatterDocument::query()->sum('size_bytes');

        if ($used + $file->getSize() > $limitMb * 1024 * 1024) {
            return back()->withErrors(['file' => 'Dosegnut je limit pohrane za trenutni plan.']);
        }

        $folder = $data['folder'] ?: 'Podnesci';
        $path = $file->store('predmeti/'.$matter->internal_number.'/'.$folder, 'local');

        $document = MatterDocument::query()->create([
            'matter_id' => $matter->id,
            'folder' => $folder,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'size_bytes' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'uploaded_by_user_id' => auth()->id(),
        ]);

        AuditLog::record(AuditAction::Create, $document, 'Upload '.$document->original_name);

        return back()->with('status', 'Dokument je spremljen u mapu predmeta.');
    }

    public function share(string $slug, int $document): RedirectResponse
    {
        $this->authorizePerm('documents.manage');
        $model = MatterDocument::query()->findOrFail($document);
        $this->findVisibleMatter($model->matter_id);
        $model->forceFill(['shared_with_client' => ! $model->shared_with_client])->save();

        return back()->with('status', $model->shared_with_client ? 'Dokument je vidljiv klijentu.' : 'Dokument više nije vidljiv klijentu.');
    }

    public function download(string $slug, int $document): StreamedResponse
    {
        $this->authorizePerm('documents.view');
        $model = MatterDocument::query()->findOrFail($document);
        $this->findVisibleMatter($model->matter_id);
        AuditLog::record(AuditAction::View, $model, 'Preuzimanje '.$model->original_name);

        return Storage::disk('local')->download($model->path, $model->original_name);
    }

    public function storeVersion(Request $request, string $slug, int $document): RedirectResponse
    {
        $this->authorizePerm('documents.manage');
        $this->authorizeFeature('document_versioning');
        $model = MatterDocument::query()->findOrFail($document);
        $this->findVisibleMatter($model->matter_id);
        $request->validate(['file' => ['required', 'file', 'max:20480']]);
        $file = $request->file('file');
        $this->assertStorage($file->getSize());

        DocumentVersion::query()->create([
            'matter_document_id' => $model->id,
            'version' => $model->version,
            'original_name' => $model->original_name,
            'path' => $model->path,
            'size_bytes' => $model->size_bytes,
            'mime' => $model->mime,
            'uploaded_by_user_id' => $model->uploaded_by_user_id,
        ]);

        $path = $file->store('predmeti/'.$model->matter->internal_number.'/'.$model->folder, 'local');
        $model->forceFill([
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'size_bytes' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'version' => $model->version + 1,
            'uploaded_by_user_id' => auth()->id(),
        ])->save();

        AuditLog::record(AuditAction::Update, $model, 'Nova verzija '.$model->version.' — '.$model->original_name);

        return back()->with('status', 'Spremljena je verzija '.$model->version.'.');
    }

    public function downloadVersion(string $slug, int $document, int $version): StreamedResponse
    {
        $this->authorizePerm('documents.view');
        $this->authorizeFeature('document_versioning');
        $model = MatterDocument::query()->findOrFail($document);
        $this->findVisibleMatter($model->matter_id);
        $archived = DocumentVersion::query()
            ->where('matter_document_id', $model->id)
            ->where('version', $version)
            ->firstOrFail();
        AuditLog::record(AuditAction::View, $model, 'Preuzimanje verzije '.$version);

        return Storage::disk('local')->download($archived->path, $archived->original_name);
    }

    public function destroy(string $slug, int $document): RedirectResponse
    {
        $this->authorizePerm('documents.delete');
        $model = MatterDocument::query()->with('versions')->findOrFail($document);
        $this->findVisibleMatter($model->matter_id);
        Storage::disk('local')->delete($model->path);
        foreach ($model->versions as $version) {
            Storage::disk('local')->delete($version->path);
        }
        AuditLog::record(AuditAction::Delete, $model, 'Brisanje '.$model->original_name);
        $model->delete();

        return back()->with('status', 'Dokument je obrisan.');
    }

    private function assertStorage(int $bytes): void
    {
        $limitMb = $this->plans->limit($this->office(), 'storage_mb') ?? 1024;
        $used = (int) MatterDocument::query()->sum('size_bytes') + (int) DocumentVersion::query()->sum('size_bytes');

        if ($used + $bytes > $limitMb * 1024 * 1024) {
            throw ValidationException::withMessages([
                'file' => 'Dosegnut je limit pohrane za trenutni plan.',
            ]);
        }
    }
}
