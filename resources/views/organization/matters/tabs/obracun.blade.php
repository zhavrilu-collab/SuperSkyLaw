@php
    $eur = fn (int $cents): string => number_format($cents / 100, 2, ',', '.').' EUR';
    $nenaplaceno = $seesMoney
        ? $ledger['unbilledTimeCents'] + $ledger['unbilledExpenseCents'] + $ledger['unbilledTariffCents']
        : 0;
    $billableTime = $ledger['times']->filter(fn ($entry) => $entry->status === \App\Enums\TimeEntryStatus::Approved && $entry->invoice_id === null && ! $entry->isRunning());
    $billableExpenses = $ledger['expenses']->filter(fn ($expense) => $expense->invoice_id === null && $expense->bill_to === \App\Enums\FeeAudience::Client->value);
    $billableTariffs = $ledger['tariffs']->filter(fn ($charge) => $charge->invoice_id === null && $charge->audience === \App\Enums\FeeAudience::Client);
@endphp
<div class="kartica-kontejner">
    @if($seesMoney)
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h6 text-tema mb-1">Obračun predmeta</h2>
                <p class="text-muted mb-0">Sati, troškovi, tarifa, računi i depozit ovog predmeta. Uredski izvještaji ostaju u Financijama.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('organization.invoices.index', $org->slug) }}">Financije</a>
                @planFeature('tariff_hok')
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('organization.cost-bills.pdf', [$org->slug, $matter->id]) }}">Troškovnik PDF</a>
                @endplanFeature
            </div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small fw-bold">Sati</div><div>{{ $eur($ledger['unbilledTimeCents']) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small fw-bold">Troškovi</div><div>{{ $eur($ledger['unbilledExpenseCents']) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small fw-bold">Tarifa</div><div>{{ $eur($ledger['unbilledTariffCents']) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="small fw-bold">Nenaplaćeno</div><div>{{ $eur($nenaplaceno) }}</div></div></div>
        </div>
        <p class="small text-muted">Nenaplaćeno zbraja odobrene sate, troškove klijentu i nagradu klijentu koji još nisu na računu.</p>
        @if($ledger['pendingTimeCents'] > 0)
            <p class="small text-muted">Sati koji čekaju odobrenje: {{ $eur($ledger['pendingTimeCents']) }}.</p>
        @endif
    @else
        <h2 class="h6 text-tema mb-1">Sati predmeta</h2>
        <p class="text-muted">Unos i pregled sati ovog predmeta.</p>
    @endif

    <h2 class="h6 text-tema mt-3">Sati</h2>
    @if($runningEntry && (int) $runningEntry->matter_id === (int) $matter->id)
        <div class="alert alert-success">Štoperica radi od {{ $runningEntry->started_at->timezone(config('app.timezone'))->format('H:i') }} — {{ $runningEntry->description }}
            <form class="d-inline" method="POST" action="{{ route('organization.time.stop', [$org->slug, $runningEntry->id]) }}">
                @csrf
                <input type="hidden" name="return_to" value="matter">
                <button class="btn btn-sm btn-dark" type="submit">Zaustavi</button>
            </form>
        </div>
    @endif
    @if($ledger['times']->isEmpty())
        <p class="text-muted">Nema sati.</p>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Kad</th><th>Tko</th><th>Opis</th><th>Minute</th><th>Stanje</th>
                        @if($seesMoney)<th>Iznos</th><th>Račun</th>@endif
                        @if($canApproveTime && $requiresTimeApproval)<th></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($ledger['times'] as $entry)
                        <tr>
                            <td>{{ ($entry->started_at ?? $entry->created_at)->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}</td>
                            <td>{{ $entry->user?->name ?: '—' }}</td>
                            <td>{{ $entry->description }}</td>
                            <td>{{ $entry->isRunning() ? 'U tijeku' : $entry->minutes }}</td>
                            <td>{{ $entry->status->label() }}</td>
                            @if($seesMoney)
                                <td>{{ $eur($entry->valueCents()) }}</td>
                                <td>{{ $entry->invoice_id ? 'Na računu' : 'Nije na računu' }}</td>
                            @endif
                            @if($canApproveTime && $requiresTimeApproval)
                                <td>
                                    @if(!$entry->isRunning() && !$entry->invoice_id && in_array($entry->status->value, ['draft', 'approved', 'non_billable'], true))
                                        <div class="d-flex gap-1">
                                            @if($entry->status->value !== 'approved')
                                                <form method="POST" action="{{ route('organization.time.approve', [$org->slug, $entry->id]) }}">@csrf<input type="hidden" name="return_to" value="matter"><button class="btn btn-sm btn-outline-success" type="submit">Odobri</button></form>
                                            @endif
                                            @if($entry->status->value !== 'written_off')
                                                <form method="POST" action="{{ route('organization.time.write-off', [$org->slug, $entry->id]) }}">@csrf<input type="hidden" name="return_to" value="matter"><button class="btn btn-sm btn-outline-secondary" type="submit">Otpis</button></form>
                                            @endif
                                            @if($entry->status->value !== 'non_billable')
                                                <form method="POST" action="{{ route('organization.time.non-billable', [$org->slug, $entry->id]) }}">@csrf<input type="hidden" name="return_to" value="matter"><button class="btn btn-sm btn-outline-secondary" type="submit">Nenaplativo</button></form>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($canManageTime)
        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <form method="POST" action="{{ route('organization.time.start', $org->slug) }}">
                    @csrf
                    <input type="hidden" name="return_to" value="matter">
                    <input type="hidden" name="matter_id" value="{{ $matter->id }}">
                    <h2 class="h6 text-tema">Štoperica</h2>
                    <label class="form-label" for="opisStoperice">Opis radnje</label>
                    <input name="description" id="opisStoperice" class="form-control mb-2" required>
                    <button class="btn btn-primary btn-sm" type="submit">Pokreni</button>
                </form>
            </div>
            <div class="col-md-6">
                <form method="POST" action="{{ route('organization.time.store', $org->slug) }}">
                    @csrf
                    <input type="hidden" name="return_to" value="matter">
                    <input type="hidden" name="matter_id" value="{{ $matter->id }}">
                    <h2 class="h6 text-tema">Ručni unos</h2>
                    <label class="form-label" for="opisRucnogUnosa">Opis radnje</label>
                    <input name="description" id="opisRucnogUnosa" class="form-control mb-2" required>
                    <label class="form-label" for="minuteRucnogUnosa">Minute</label>
                    <input name="minutes" id="minuteRucnogUnosa" type="number" min="1" max="1440" class="form-control mb-2" required>
                    <button class="btn btn-primary btn-sm" type="submit">Unesi sate</button>
                </form>
            </div>
        </div>
    @endif

    @if($seesMoney)
        <h2 class="h6 text-tema mt-4">Troškovi</h2>
        @if($ledger['expenses']->isEmpty())
            <p class="text-muted">Nema troškova.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Opis</th><th>Vrsta</th><th>Kome</th><th>Iznos</th><th>Račun</th></tr></thead>
                    <tbody>
                        @foreach($ledger['expenses'] as $expense)
                            <tr>
                                <td>{{ $expense->description }}</td>
                                <td>{{ $expense->category->label() }}</td>
                                <td>{{ $expense->bill_to === \App\Enums\FeeAudience::Client->value ? 'Klijent' : 'Protivna strana' }}</td>
                                <td>{{ $eur($expense->amount_cents) }}</td>
                                <td>{{ $expense->invoice_id ? 'Na računu' : 'Nije na računu' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($canManageFinance)
            <form method="POST" action="{{ route('organization.expenses.store', $org->slug) }}" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="return_to" value="matter">
                <input type="hidden" name="matter_id" value="{{ $matter->id }}">
                <div class="col-md-3">
                    <label class="form-label" for="vrstaTroska">Vrsta</label>
                    <select name="category" id="vrstaTroska" class="form-select">@foreach(\App\Enums\ExpenseCategory::cases() as $category)<option value="{{ $category->value }}">{{ $category->label() }}</option>@endforeach</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="opisTroska">Opis</label>
                    <input name="description" id="opisTroska" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="iznosTroska">Iznos EUR</label>
                    <input name="amount" id="iznosTroska" type="number" step="0.01" min="0.01" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="komeTrosak">Kome</label>
                    <select name="bill_to" id="komeTrosak" class="form-select">
                        <option value="client">Na teret klijenta (račun)</option>
                        <option value="opposing">Na teret protivne strane (troškovnik)</option>
                    </select>
                </div>
                <div class="col-md-1"><button class="btn btn-primary btn-sm" type="submit">Unesi</button></div>
            </form>
        @endif

        <h2 class="h6 text-tema mt-4">Tarifa</h2>
        @if($ledger['tariffs']->isEmpty())
            <p class="text-muted">Nema tarife.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Opis</th><th>Kome</th><th>Bodovi</th><th>Iznos</th><th>Račun</th></tr></thead>
                    <tbody>
                        @foreach($ledger['tariffs'] as $charge)
                            <tr>
                                <td>{{ $charge->description }}</td>
                                <td>{{ $charge->audience->label() }}</td>
                                <td>{{ $charge->points }}</td>
                                <td>{{ $eur($charge->amount_cents) }}</td>
                                <td>{{ $charge->invoice_id ? 'Na računu' : 'Nije na računu' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($canManageFinance && $tariffActions->isNotEmpty())
            <form method="POST" action="{{ route('organization.tariff.store', $org->slug) }}" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="return_to" value="matter">
                <input type="hidden" name="matter_id" value="{{ $matter->id }}">
                <div class="col-md-6">
                    <label class="form-label" for="radnjaTarife">Radnja</label>
                    <select name="tariff_action_id" id="radnjaTarife" class="form-select" required>@foreach($tariffActions as $action)<option value="{{ $action->id }}">{{ $action->label }}</option>@endforeach</select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="namjenaTarife">Kome</label>
                    <select name="audience" id="namjenaTarife" class="form-select">@foreach(\App\Enums\FeeAudience::cases() as $audience)<option value="{{ $audience->value }}">{{ $audience->label() }}</option>@endforeach</select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary btn-sm" type="submit">Obračunaj</button></div>
            </form>
        @endif

        <h2 class="h6 text-tema mt-4">Računi</h2>
        @if($ledger['invoices']->isEmpty())
            <p class="text-muted">Nema računa.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Broj</th><th>Datum</th><th>Ukupno</th><th>Plaćeno</th><th>Stanje</th></tr></thead>
                    <tbody>
                        @foreach($ledger['invoices'] as $invoice)
                            <tr>
                                <td><a href="{{ route('organization.invoices.show', [$org->slug, $invoice->id]) }}">{{ $invoice->number }}</a></td>
                                <td>{{ $invoice->issue_date->timezone(config('app.timezone'))->format('d.m.Y.') }}</td>
                                <td>{{ $eur($invoice->total_cents) }}</td>
                                <td>{{ $eur($invoice->paid_cents) }}</td>
                                <td>{{ $invoice->status->label() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($canManageFinance)
            @if($matter->parties->isEmpty())
                <p class="text-muted">Povežite stranku na Podacima da biste izdali račun.</p>
            @else
                <form method="POST" action="{{ route('organization.invoices.store', $org->slug) }}">
                    @csrf
                    <input type="hidden" name="return_to" value="matter">
                    <input type="hidden" name="matter_id" value="{{ $matter->id }}">
                    <label class="form-label" for="kupacRacuna">Kupac</label>
                    <select name="party_id" id="kupacRacuna" class="form-select mb-2" required>
                        @foreach($matter->parties as $link)
                            @if($link->party)
                                <option value="{{ $link->party_id }}">{{ $link->party->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <div class="mb-2 small fw-bold">Odobreni sati</div>
                    @forelse($billableTime as $entry)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="time_entry_ids[]" value="{{ $entry->id }}" id="sati{{ $entry->id }}"><label class="form-check-label" for="sati{{ $entry->id }}">{{ $entry->description }} ({{ $entry->minutes }} min)</label></div>
                    @empty
                        <p class="text-muted mb-2">Nema odobrenih sati za račun.</p>
                    @endforelse
                    <div class="mb-2 small fw-bold mt-2">Nagrada klijentu</div>
                    @forelse($billableTariffs as $charge)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="tariff_charge_ids[]" value="{{ $charge->id }}" id="tarifa{{ $charge->id }}"><label class="form-check-label" for="tarifa{{ $charge->id }}">{{ $charge->description }} ({{ $eur($charge->amount_cents) }})</label></div>
                    @empty
                        <p class="text-muted mb-2">Nema nagrade za račun.</p>
                    @endforelse
                    <div class="mb-2 small fw-bold mt-2">Troškovi na teret klijenta</div>
                    @forelse($billableExpenses as $expense)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="expense_ids[]" value="{{ $expense->id }}" id="trosak{{ $expense->id }}"><label class="form-check-label" for="trosak{{ $expense->id }}">{{ $expense->description }} ({{ $eur($expense->amount_cents) }})</label></div>
                    @empty
                        <p class="text-muted mb-2">Nema troškova za račun.</p>
                    @endforelse
                    <button class="btn btn-primary btn-sm mt-2" type="submit">Izdaj račun</button>
                </form>
            @endif
        @endif

        <h2 class="h6 text-tema mt-4">Depozit</h2>
        <p>Stanje: <strong>{{ $eur($ledger['trustBalanceCents']) }}</strong></p>
        @if($ledger['trustMovements']->isEmpty())
            <p class="text-muted">Nema uplata ni isplata.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Kad</th><th>Smjer</th><th>Svrha</th><th>Iznos</th></tr></thead>
                    <tbody>
                        @foreach($ledger['trustMovements'] as $movement)
                            <tr>
                                <td>{{ $movement->occurred_on->timezone(config('app.timezone'))->format('d.m.Y.') }}</td>
                                <td>{{ $movement->direction->label() }}</td>
                                <td>{{ $movement->purpose }}@if($movement->counterparty) · {{ $movement->counterparty }}@endif</td>
                                <td>{{ $eur($movement->amount_cents) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($canManageFinance)
            <form method="POST" action="{{ route('organization.trust.store', $org->slug) }}" class="row g-2 align-items-end mb-0">
                @csrf
                <input type="hidden" name="return_to" value="matter">
                <input type="hidden" name="matter_id" value="{{ $matter->id }}">
                <div class="col-md-2">
                    <label class="form-label" for="smjerDepozita">Smjer</label>
                    <select name="direction" id="smjerDepozita" class="form-select">@foreach(\App\Enums\TrustDirection::cases() as $direction)<option value="{{ $direction->value }}">{{ $direction->label() }}</option>@endforeach</select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="iznosDepozita">Iznos EUR</label>
                    <input name="amount" id="iznosDepozita" type="number" step="0.01" min="0.01" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="stranaDepozita">Od koga / kome</label>
                    <input name="counterparty" id="stranaDepozita" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="svrhaDepozita">Svrha</label>
                    <input name="purpose" id="svrhaDepozita" class="form-control" required>
                </div>
                <div class="col-md-2"><button class="btn btn-primary btn-sm" type="submit">Upiši</button></div>
            </form>
        @endif
    @endif
</div>
