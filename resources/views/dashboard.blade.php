<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nadzorna ploča — SMB SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">SMB SaaS</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-light btn-sm" type="submit">Odjava</button>
        </form>
    </div>
</nav>

<div class="container py-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-1">Nadzorna ploča</h1>
            <p class="text-muted mb-0">{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <strong>Tvrtke u sustavu</strong>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Naziv</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Paket</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($organizations as $organization)
                        <tr>
                            <td>{{ $organization->name }}</td>
                            <td><code>{{ $organization->slug }}</code></td>
                            <td>{{ $organization->status->label() }}</td>
                            <td>{{ $organization->plan }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted text-center py-4">Nema registriranih tvrtki.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
