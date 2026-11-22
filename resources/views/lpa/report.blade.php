@extends('layouts.lpa')
@section('title','Indicadores')
@section('heading','Indicadores de auditoría')
@section('content')
<div class="card mb-3"><div class="card-body"><form method="GET" action="{{ route('lpa.reports') }}" class="row g-2 align-items-end">
<div class="col-sm-4"><label class="form-label" for="from">Desde</label><input type="date" class="form-control" id="from" name="from" value="{{ $filters['from']??'' }}"></div>
<div class="col-sm-4"><label class="form-label" for="to">Hasta</label><input type="date" class="form-control" id="to" name="to" value="{{ $filters['to']??'' }}"></div>
<div class="col-sm-4"><button class="btn btn-outline-primary w-100">Consultar</button></div>
</form></div></div>
<div class="d-flex gap-2 flex-wrap mb-3"><a class="btn btn-primary" href="{{ route('lpa.exports.summary', $filters) }}">Resumen consolidado CSV</a><a class="btn btn-outline-secondary" href="{{ route('lpa.exports.audits', $filters) }}">Exportar auditorías CSV</a><a class="btn btn-outline-secondary" href="{{ route('lpa.exports.actions', $filters) }}">Exportar acciones CSV</a></div>
<div class="row g-3 mb-3 lpa-kpis"><div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="text-secondary">Programadas</div><div class="fs-2 fw-bold">{{ $data['planned'] }}</div></div></div></div>
<div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="text-secondary">Completadas</div><div class="fs-2 fw-bold">{{ $data['completed'] }}</div></div></div></div>
<div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="text-secondary">Cumplimiento del plan</div><div class="fs-2 fw-bold">{{ $data['completion_pct']===null ? '—' : $data['completion_pct'].'%' }}</div></div></div></div></div>
<div class="card"><div class="card-header"><h2 class="card-title">Resultados de preguntas</h2></div><div class="card-body"><div class="row g-3">
<div class="col-sm-3"><div class="text-secondary small">Cumple</div><div class="fs-4 fw-semibold">{{ $data['pass'] }}</div></div>
<div class="col-sm-3"><div class="text-secondary small">No cumple</div><div class="fs-4 fw-semibold">{{ $data['fail'] }}</div></div>
<div class="col-sm-3"><div class="text-secondary small">No aplica</div><div class="fs-4 fw-semibold">{{ $data['na'] }}</div></div>
<div class="col-sm-3"><div class="text-secondary small">Conformidad</div><div class="fs-4 fw-semibold">{{ $data['conformity_pct']===null ? '—' : $data['conformity_pct'].'%' }}</div></div>
</div><p class="small text-secondary mt-3 mb-0">Conformidad = respuestas que cumplen / respuestas aplicables. “No aplica” se excluye del denominador.</p></div></div>
<div class="card mt-3"><div class="card-header"><h2 class="card-title">Cumplimiento por nivel LPA</h2></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>Nivel</th><th>Programadas</th><th>Completadas</th><th>Cumplimiento</th></tr></thead><tbody>
@forelse($levels as $level)<tr><td>L{{ $level->level }}</td><td>{{ $level->scheduled }}</td><td>{{ $level->finished }}</td><td>
@if($level->scheduled)
@php($percent=round($level->finished*100/$level->scheduled,1))
<div class="d-flex align-items-center gap-2" style="min-width:145px">
  <div class="progress flex-grow-1" role="progressbar" aria-label="Cumplimiento nivel L{{ $level->level }}" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" style="height:7px">
    <div class="progress-bar bg-{{ $percent >= 90 ? 'success' : ($percent >= 70 ? 'info' : 'warning') }}" style="width:{{ $percent }}%"></div>
  </div>
  <span class="small fw-semibold text-nowrap">{{ $percent }}%</span>
</div>
@else
<span class="text-secondary">—</span>
@endif
</td></tr>
@empty<tr><td colspan="4" class="text-secondary text-center py-3">Sin auditorías en el periodo.</td></tr>@endforelse
</tbody></table></div></div></div>
<div class="card mt-3"><div class="card-header"><h2 class="card-title">Acciones por responsable</h2></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>Responsable</th><th>Total</th><th>Abiertas</th><th>Vencidas</th></tr></thead><tbody>
@forelse($ownerSummary as $owner)<tr><td>{{ $owner->owner_name }}</td><td>{{ $owner->total }}</td><td>{{ $owner->open_count }}</td><td>{{ $owner->overdue_count }}</td></tr>
@empty<tr><td colspan="4" class="text-secondary text-center py-3">Sin acciones en el periodo.</td></tr>@endforelse
</tbody></table></div></div></div>
@endsection
