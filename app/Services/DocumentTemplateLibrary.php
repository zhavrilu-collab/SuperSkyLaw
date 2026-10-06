<?php

namespace App\Services;

use App\Models\DocumentTemplate;

class DocumentTemplateLibrary
{
    public function install(): void
    {
        foreach (config('document_templates') as $template) {
            DocumentTemplate::query()->updateOrCreate(
                ['code' => $template['code']],
                $template,
            );
        }
    }
}
