@props([
    'paginator',
    'options' => \App\Support\PerPage::OPTIONS,
])

@php
    $currentPerPage = (int) $paginator->perPage();
    $selectId = 'perPageSelect-'.uniqid();
@endphp

@if($paginator->total() > 0)
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
        <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
            <label class="mb-0 text-nowrap" for="{{ $selectId }}">Prikaz</label>
            <form method="GET" class="d-inline-flex align-items-center gap-2 mb-0">
                @foreach(request()->except(['page', 'per_page', 'slug']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $nested)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $nested }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <select name="per_page" id="{{ $selectId }}"
                        class="form-select form-select-sm"
                        style="width: auto; min-width: 4.75rem;"
                        onchange="this.form.submit()"
                        aria-label="Broj prikaza po stranici">
                    @foreach($options as $option)
                        <option value="{{ $option }}" @selected($currentPerPage === (int) $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
            <span class="text-nowrap">
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
                od {{ number_format($paginator->total(), 0, ',', '.') }}
            </span>
        </div>

        @if($paginator->hasPages())
            <div class="ms-auto">
                {{ $paginator->withQueryString()->onEachSide(1)->links('vendor.pagination.bootstrap-5') }}
            </div>
        @endif
    </div>
@endif
