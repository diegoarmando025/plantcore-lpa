@extends('layouts.lpa')
@section('title','Tendencias LPA')
@section('heading','Tendencias por planta y mes')
@section('content')
<div class="card mb-3"><div class="card-body">
<form method="GET" action="{{ route('lpa.trends') }}" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label" for="from">Desde</label><input class="form-control" type="date" id="from" name="from" value="{{ $from->toDateString() }}"></div>
<div class="col-md-3"><label class="form-label" for="to">Hasta</label><input class="form-control" type="date" id="to" name="to" value="{{ $to->toDateString() }}"></div>
<div class="col-md-4"><label class="form-label" for="plant_id">Planta</label><select class="form-select" id="plant_id" name="plant_id"><option value="">Todas mis plantas</option>@foreach($plants as $plant)<option value="{{ $plant->id }}" @selected($selected === (int)$plant->id)>{{ $plant->name }} ({{ $plant->code }})</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Consultar</button></div>
</form></div></div>
<div class="d-flex flex-wrap gap-2 mb-3">
<a class="btn btn-outline-secondary" href="{{ route('lpa.reports') }}">Indicadores generales</a>
<a class="btn btn-outline-primary" href="{{ route('lpa.trends.export', request()->only(['from','to','plant_id'])) }}">Descargar CSV</a>
</div>
<div class="card"><div class="card-header"><h2 class="card-title">Cumplimiento mensual y acciones correctivas</h2></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Mes programado</th><th>Planta</th><th>Programadas</th><th>Completadas</th><th>Cumplimiento</th><th>Acciones</th><th>Abiertas</th><th>Vencidas</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row->month }}</td><td>{{ $row->plant_name }}</td><td>{{ $row->planned_count }}</td><td>{{ $row->completed_count }}</td><td>{{ $row->completion_pct === null ? '—' : $row->completion_pct.'%' }}</td><td>{{ $row->actions_count }}</td><td>{{ $row->open_count }}</td><td>{{ $row->overdue_count }}</td></tr>
@empty<tr><td colspan="8" class="text-center text-secondary py-4">No hay auditorías en este periodo.</td></tr>@endforelse
</tbody></table></div></div></div>
<p class="text-secondary small mt-3">Las auditorías se agrupan por mes de programación, aunque se completen después. Las columnas «Abiertas» y «Vencidas» reflejan el estado actual de las acciones originadas en esas auditorías, no una fotografía histórica del cierre de cada mes. Periodo máximo: 366 días.</p>
@endsection
