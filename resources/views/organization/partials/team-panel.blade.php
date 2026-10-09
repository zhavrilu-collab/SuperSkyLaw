<div id="tim">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 text-tema mb-1">Tim</h2>
            <p class="text-muted mb-0">Upravljanje članovima i pozivnicama.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Članovi</div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Ime</th>
                                <th>E-mail</th>
                                <th>Uloga</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $member)
                                <tr>
                                    <td>{{ $member->user->name }}</td>
                                    <td>{{ $member->user->email }}</td>
                                    <td>
                                        @if($member->isOwner())
                                            <span class="badge text-bg-secondary">{{ $member->role->label() }}</span>
                                        @else
                                            <form method="POST" action="{{ route('organization.team.update-role', [$organization->slug, $member]) }}" class="d-flex gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    @foreach($roles as $role)
                                                        @continue($role === \App\Enums\OrganizationRole::Owner)
                                                        <option value="{{ $role->value }}" @selected($member->role === $role)>{{ $role->label() }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @unless($member->isOwner())
                                            <form method="POST" action="{{ route('organization.team.destroy', [$organization->slug, $member]) }}" onsubmit="return confirm('Ukloniti člana iz tima?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Ukloni</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Nova pozivnica</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('organization.team.invite', $organization->slug) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="email">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="role">Uloga</label>
                            <select name="role" id="role" class="form-select" required>
                                @foreach($roles as $role)
                                    @continue($role === \App\Enums\OrganizationRole::Owner)
                                    <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Pošalji pozivnicu</button>
                    </form>
                </div>
            </div>

            @if($pendingInvites->isNotEmpty())
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">Aktivne pozivnice</div>
                    <ul class="list-group list-group-flush">
                        @foreach($pendingInvites as $invite)
                            <li class="list-group-item small">
                                <div class="fw-semibold">{{ $invite->email }}</div>
                                <div class="text-muted">{{ $invite->role->label() }} · istječe {{ $invite->expires_at->format('d.m.Y.') }}</div>
                                <a href="{{ route('staff-invite.show', $invite->token) }}" class="text-break">{{ route('staff-invite.show', $invite->token) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
