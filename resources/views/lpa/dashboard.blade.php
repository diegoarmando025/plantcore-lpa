@extends('layouts.lpa')
@section('title','Auditorías · PlantCore LPA')
@section('heading','Auditorías de proceso')
@section('content')
@if($canSchedule)<div class="d-flex justify-content-end mb-3"><a href="{{ route('lpa.batch.create') }}" class="btn btn-outline-primary me-2">Programar varias</a><a href="{{ route('lpa.audits.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Programar auditoría</a></div>@endif
<div class="row g-3 mb-3 lpa-kpis">
<div class="col-sm-4"><div class="small-box text-bg-primary"><div class="inner"><h3>{{ $counts['planned'] }}</h3><p>Programadas</p></div></div></div>
<div class="col-sm-4"><div class="small-box text-bg-success"><div class="inner"><h3>{{ $counts['completed'] }}</h3><p>Completadas</p></div></div></div>
<div class="col-sm-4"><div class="small-box text-bg-warning"><div class="inner"><h3>{{ $counts['open_actions'] }}</h3><p>Acciones abiertas</p></div></div></div></div>
<div class="card mb-3"><div class="card-body"><form method="GET" action="{{ route('lpa.dashboard') }}" class="row g-2 align-items-end">
<div class="col-md-3"><label for="status" class="form-label">Estado</label><select class="form-select" name="status" id="status"><option value="">Todos</option><option value="planned" @selected(($filters['status']??'')==='planned')>Programada</option><option value="completed" @selected(($filters['status']??'')==='completed')>Completada</option></select></div>
<div class="col-md-3"><label class="form-label" for="from">Desde</label><input class="form-control" type="date" name="from" id="from" value="{{ $filters['from']??'' }}"></div>
<div class="col-md-3"><label class="form-label" for="to">Hasta</label><input class="form-control" type="date" name="to" id="to" value="{{ $filters['to']??'' }}"></div>
<div class="col-md-3"><button class="btn btn-outline-primary w-100">Aplicar filtros</button></div>
</form></div></div>
<div class="card"><div class="card-header"><h2 class="card-title">Auditorías</h2></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Folio</th><th>Nivel</th><th>Plantilla</th><th>Área</th><th>Fecha</th><th>Auditor</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($audits as $audit)<tr><td>#{{ $audit->id }}</td><td>L{{ $audit->template->level }}</td><td>{{ $audit->template->name }}</td><td>{{ $audit->template->area }}</td><td>{{ $audit->scheduled_for->format('d/m/Y') }}</td><td>{{ $audit->assignee->name }}</td><td><span class="badge {{ $audit->status==='completed' ? 'text-bg-success' : 'text-bg-warning' }}">{{ $audit->status==='completed' ? 'Completada' : 'Programada' }}</span></td><td><a href="{{ route('lpa.audits.show',$audit) }}" class="btn btn-sm btn-outline-secondary">Abrir</a></td></tr>
@empty<tr><td colspan="8" class="text-secondary text-center py-4">No se encontraron auditorías.</td></tr>@endforelse
</tbody></table></div></div>@if($audits->hasPages())<div class="card-footer">{{ $audits->links('pagination::bootstrap-5') }}</div>@endif</div>
@endsection
