<?php

namespace App\Services;

use App\Enums\SignatureStatus;
use App\Models\MatterDocument;
use App\Models\SignatureRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ESignService
{
    public function submit(MatterDocument $document): SignatureRequest
    {
        $document->loadMissing('matter');
        $hash = hash('sha256', Storage::disk('local')->get($document->path) ?: '');
        $signature = SignatureRequest::query()->firstOrNew([
            'matter_document_id' => $document->id,
        ]);
        $signature->organization_id = $document->organization_id;
        $signature->requested_by_user_id = auth()->id();

        $url = (string) config('services.esign.url');
        if ($url === '') {
            $signature->forceFill([
                'status' => SignatureStatus::Prepared,
                'error' => null,
                'signed_at' => null,
                'provider_reference' => null,
            ])->save();

            return $signature;
        }

        $response = Http::withBasicAuth(
            (string) config('services.esign.username'),
            (string) config('services.esign.password'),
        )->post($url, [
            'file_name' => $document->original_name,
            'sha256' => $hash,
            'matter' => $document->matter?->internal_number,
        ]);

        $signed = $response->successful() && $response->json('signed') === true;
        $signature->forceFill([
            'status' => $signed
                ? SignatureStatus::Signed
                : ($response->successful() ? SignatureStatus::Sent : SignatureStatus::Failed),
            'provider_reference' => $response->json('reference'),
            'error' => $response->successful() ? null : mb_substr($response->body(), 0, 500),
            'signed_at' => $signed ? now() : null,
        ])->save();

        return $signature;
    }
}
