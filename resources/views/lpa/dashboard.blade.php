@extends('layouts.lpa')
@section('title','Auditorías · PlantCore LPA')
@section('heading','Auditorías de proceso')
@section('content')
<section class="ops-intro mb-4">
    <div>
        <div class="ops-eyebrow">AUDITORÍAS POR CAPAS · CONTROL DE PROCESO</div>
        <h2 class="h3 fw-semibold mb-2">Seguimiento de auditorías</h2>
        <p class="text-secondary mb-0">Planifica evaluaciones, documenta hallazgos y da seguimiento a las acciones correctivas.</p>
    </div>
    @if($canSchedule)
    <div class="ops-intro-actions">
        <a href="{{ route('lpa.audits.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Programar auditoría</a>
        <a href="{{ route('lpa.batch.create') }}" class="btn btn-outline-secondary"><i class="bi bi-calendar-plus me-1"></i> Programar varias</a>
    </div>
    @endif
</section>
<div class="row g-3 mb-4 lpa-kpis ops-kpis">
    <div class="col-sm-6 col-xl-4"><div class="card ops-kpi h-100"><div class="card-body"><div class="ops-kpi-label"><i class="bi bi-calendar2-check me-2"></i> Programadas</div><div class="ops-kpi-number">{{ number_format($counts['planned']) }}</div><div class="ops-kpi-foot">Auditorías pendientes de ejecución</div></div></div></div>
    <div class="col-sm-6 col-xl-4"><div class="card ops-kpi ops-kpi-success h-100"><div class="card-body"><div class="ops-kpi-label"><i class="bi bi-clipboard-check me-2"></i> Completadas</div><div class="ops-kpi-number">{{ number_format($counts['completed']) }}</div><div class="ops-kpi-foot">Con resultados y evidencia registrada</div></div></div></div>
    <div class="col-sm-6 col-xl-4"><div class="card ops-kpi ops-kpi-attention h-100"><div class="card-body"><div class="ops-kpi-label"><i class="bi bi-exclamation-triangle me-2"></i> Acciones abiertas</div><div class="ops-kpi-number">{{ number_format($counts['open_actions']) }}</div><div class="ops-kpi-foot"><a href="{{ route('lpa.actions') }}">Revisar pendientes <i class="bi bi-arrow-right ms-1"></i></a></div></div></div></div>
</div>
<div class="card mb-3">
    <div class="card-header"><h2 class="card-title mb-0">Buscar auditorías</h2></div>
    <div class="card-body">
        <form method="GET" action="{{ route('lpa.dashboard') }}" class="row g-2 align-items-end">
            <div class="col-md-3"><label for="status" class="form-label">Estado</label><select class="form-select" name="status" id="status"><option value="">Todos</option><option value="planned" @selected(($filters['status']??'')==='planned')>Programada</option><option value="completed" @selected(($filters['status']??'')==='completed')>Completada</option></select></div>
            <div class="col-md-3"><label class="form-label" for="from">Desde</label><input class="form-control" type="date" name="from" id="from" value="{{ $filters['from']??'' }}"></div>
            <div class="col-md-3"><label class="form-label" for="to">Hasta</label><input class="form-control" type="date" name="to" id="to" value="{{ $filters['to']??'' }}"></div>
            <div class="col-md-3"><button class="btn btn-outline-primary w-100">Aplicar filtros</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title mb-0">Listado de auditorías</h2>
        <a href="{{ route('lpa.reports') }}" class="btn btn-sm btn-outline-primary ms-auto">Ver indicadores</a>
    </div>
    <div class="card-body p-0"><div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Folio</th><th>Nivel</th><th>Cuestionario</th><th>Área</th><th>Fecha</th><th>Auditor</th><th>Estado</th><th class="text-end">Detalle</th></tr></thead>
            <tbody>
            @forelse($audits as $audit)
                <tr>
                    <td class="fw-semibold"><a class="ops-table-link" href="{{ route('lpa.audits.show',$audit) }}">#{{ $audit->id }}</a></td>
                    <td><span class="ops-level">L{{ $audit->template->level }}</span></td>
                    <td>{{ $audit->template->name }}</td>
                    <td>{{ $audit->template->area }}</td>
                    <td class="text-nowrap">{{ $audit->scheduled_for->format('d/m/Y') }}</td>
                    <td>{{ $audit->assignee->name }}</td>
                    <td><span class="badge {{ $audit->status==='completed' ? 'text-bg-success' : 'text-bg-warning' }}">{{ $audit->status==='completed' ? 'Completada' : 'Programada' }}</span></td>
                    <td class="text-end"><a href="{{ route('lpa.audits.show',$audit) }}" class="btn btn-sm btn-outline-secondary">Abrir</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-secondary text-center py-4">No hay auditorías que coincidan con los filtros. Cambia el periodo o programa una nueva evaluación.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    @if($audits->hasPages())<div class="card-footer">{{ $audits->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
