<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Auditorías de proceso · PlantCore LPA</title>
    @vite(['resources/css/lpa.css', 'resources/js/lpa.js'])
    <style>
        body { background:#1e232b; color:#e6e8eb; }
        .demo-shell { max-width:1180px; margin:0 auto; padding:34px 20px 64px; }
        .demo-top { border-bottom:1px solid #414952; padding-bottom:20px; margin-bottom:30px; }
        .demo-heading { font-weight:650; letter-spacing:-.03em; }
        .demo-muted { color:#a7b1be; }
        .demo-kpi { border:1px solid #475463; border-radius:12px; padding:18px; background:#252c35; height:100%; }
        .demo-kpi strong { font-size:2rem; display:block; line-height:1.2; }
        .demo-panel { border:1px solid #475463; border-radius:12px; padding:20px; background:#252c35; }
        .table { --bs-table-bg:transparent; }
        a { color:#a7c4ff; }
        .demo-tag { background:#344458; color:#dce8ff; font-size:.75rem; padding:4px 10px; border-radius:100px; }
    </style>
</head>
<body>
<main class="demo-shell">
    <div class="demo-top d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="small demo-muted fw-semibold mb-2">PLANTCORE LPA</div>
            <h1 class="demo-heading h2 mb-1">Auditorías por niveles</h1>
            <p class="demo-muted mb-0">Ejemplo de operación industrial · {{ $plant->name }}</p>
        </div>
        <span class="demo-tag">Consulta pública · Solo lectura</span>
    </div>

    <section class="row g-3 mb-4" aria-label="Resumen de indicadores">
        <div class="col-6 col-md-4"><div class="demo-kpi"><strong>{{ $counts['planned'] }}</strong><span class="demo-muted">Programadas</span></div></div>
        <div class="col-6 col-md-4"><div class="demo-kpi"><strong>{{ $counts['completed'] }}</strong><span class="demo-muted">Completadas</span></div></div>
        <div class="col-12 col-md-4"><div class="demo-kpi"><strong>{{ $counts['open_actions'] }}</strong><span class="demo-muted">Acciones abiertas</span></div></div>
    </section>

    <section class="demo-panel mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Auditorías</h2>
            <span class="small demo-muted">Filtra por nivel y estado</span>
        </div>
        <form method="GET" action="{{ route('lpa.public-demo.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-6 col-md-3">
                <label class="form-label" for="level">Nivel</label>
                <select name="level" id="level" class="form-select">
                    <option value="">Todos</option>
                    @for($i=1; $i<=5; $i++)
                        <option value="{{ $i }}" @selected(($filters['level'] ?? null) == $i)>Nivel {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="status">Estado</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="planned" @selected(($filters['status'] ?? '') === 'planned')>Programada</option>
                    <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completada</option>
                </select>
            </div>
            <div class="col-12 col-md-3"><button class="btn btn-outline-primary w-100" type="submit">Filtrar auditorías</button></div>
            <div class="col-12 col-md-3"><a href="{{ route('lpa.public-demo.index') }}" class="btn btn-outline-secondary w-100">Restablecer</a></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Lista de verificación</th><th>Área</th><th>Nivel</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse($audits as $audit)
                <tr>
                    <td>{{ $audit->template->name }}</td>
                    <td>{{ $audit->template->area }}</td>
                    <td>L{{ $audit->template->level }}</td>
                    <td>{{ $audit->scheduled_for->format('d/m/Y') }}</td>
                    <td><span class="badge {{ $audit->status==='completed' ? 'text-bg-success' : 'text-bg-warning' }}">{{ $audit->status==='completed' ? 'Completada' : 'Programada' }}</span></td>
                    <td><a href="{{ route('lpa.public-demo.show',$audit) }}" class="btn btn-sm btn-outline-secondary">Ver detalle</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center demo-muted py-4">No hay auditorías para estos filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($audits->hasPages())<div class="mt-3">{{ $audits->links('pagination::bootstrap-5') }}</div>@endif
    </section>

    <section class="demo-panel">
        <h2 class="h5 mb-3">Seguimiento de hallazgos</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Acción correctiva</th><th>Área</th><th>Compromiso</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse($actions as $action)
                    <tr>
                        <td>{{ $action->description }}</td>
                        <td>{{ $action->answerAudit?->template?->area }}</td>
                        <td>{{ $action->due_date?->format('d/m/Y') ?? 'Sin definir' }}</td>
                        <td>
                            @if($action->status==='closed')<span class="badge text-bg-success">Cerrada</span>
                            @elseif($action->due_date && $action->due_date->isBefore(today()))<span class="badge text-bg-danger">Vencida</span>
                            @else<span class="badge text-bg-warning">Abierta</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="demo-muted text-center py-4">No hay acciones correctivas en el escenario.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <footer class="demo-muted small mt-4">Datos ficticios de demostración. Las operaciones de programación, ejecución y administración requieren acceso privado.</footer>
</main>
</body>
</html>
