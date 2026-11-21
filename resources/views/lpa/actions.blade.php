@extends('layouts.lpa')
@section('title','Acciones correctivas')
@section('heading','Acciones correctivas')
@section('content')
<div class="row g-2 mb-3 lpa-kpis">
<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body py-2"><div class="small text-secondary">Abiertas</div><div class="h4 mb-0">{{ $summary['open'] }}</div></div></div></div>
<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body py-2"><div class="small text-secondary">Vencidas</div><div class="h4 mb-0 text-danger">{{ $summary['overdue'] }}</div></div></div></div>
<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body py-2"><div class="small text-secondary">Próximos 7 días</div><div class="h4 mb-0">{{ $summary['next_7'] }}</div></div></div></div>
<div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body py-2"><div class="small text-secondary">Sin fecha</div><div class="h4 mb-0">{{ $summary['no_date'] }}</div></div></div></div>
</div>
<div class="card mb-3"><div class="card-body"><form method="GET" action="{{ route('lpa.actions') }}" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label" for="status">Estado</label><select class="form-select" id="status" name="status"><option value="">Todos</option><option value="open" @selected(($filters['status']??'')==='open')>Abiertas</option><option value="closed" @selected(($filters['status']??'')==='closed')>Cerradas</option></select></div>
<div class="col-md-3"><label class="form-label" for="owner_id">Responsable</label><select class="form-select" name="owner_id" id="owner_id"><option value="">Todos</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected(($filters['owner_id']??'')==$owner->id)>{{ $owner->name }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label" for="due">Vencimiento</label><select class="form-select" id="due" name="due"><option value="">Todos</option><option value="overdue" @selected(($filters['due']??'')==='overdue')>Vencidas</option><option value="next_7" @selected(($filters['due']??'')==='next_7')>Próximos 7 días</option><option value="no_date" @selected(($filters['due']??'')==='no_date')>Sin fecha</option></select></div>
<div class="col-md-3"><button class="btn btn-outline-primary w-100">Aplicar filtros</button></div>
</form></div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>Folio</th><th>Auditoría</th><th>Hallazgo</th><th>Responsable</th><th>Compromiso</th><th>Estado</th><th>Gestión</th></tr></thead><tbody>
@forelse($actions as $action)
@php($canManage=\App\Support\LpaAccess::role(auth()->id(),$action->answerAudit->plant_id)==='supervisor')
<tr>
<td>#{{ $action->id }}</td>
<td><a href="{{ route('lpa.audits.show',$action->answerAudit) }}">#{{ $action->answerAudit->id }}</a></td>
<td class="lpa-hallazgo">{{ $action->description }}</td>
<td>{{ $action->owner->name }}</td>
<td class="text-nowrap">{{ $action->due_date?->format('d/m/Y') ?? 'Sin fecha' }}
@if($action->status==='open' && $action->due_date && $action->due_date->lt(today()))<span class="badge text-bg-danger ms-1">Vencida</span>@endif
</td>
<td><span class="badge {{ $action->status==='open' ? 'text-bg-warning' : 'text-bg-success' }}">{{ $action->status==='open' ? 'Abierta' : 'Cerrada' }}</span></td>
<td>
@if($action->status==='open' && $canManage)
<div class="d-flex gap-2 flex-wrap">
<form method="POST" action="{{ route('lpa.actions.update',$action) }}" class="d-flex gap-1">@csrf<input type="date" name="due_date" class="form-control form-control-sm" value="{{ $action->due_date?->toDateString() }}" required aria-label="Fecha compromiso"><button class="btn btn-sm btn-outline-primary">Guardar</button></form>
<form method="POST" action="{{ route('lpa.actions.close',$action) }}">@csrf<button class="btn btn-sm btn-outline-success">Cerrar</button></form>
</div>
@endif
</td></tr>
@empty<tr><td colspan="7" class="text-center text-secondary py-4">No se encontraron acciones.</td></tr>@endforelse
</tbody></table></div></div>@if($actions->hasPages())<div class="card-footer">{{ $actions->links('pagination::bootstrap-5') }}</div>@endif</div>
@endsection
