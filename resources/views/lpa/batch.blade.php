@extends('layouts.lpa')
@section('title','Programación masiva')
@section('heading','Programación masiva de auditorías')
@section('content')
<div class="row"><div class="col-lg-8"><div class="card"><div class="card-body">
<form method="POST" action="{{ route('lpa.batch.store') }}">@csrf
<div class="mb-3"><label for="audit_template_id" class="form-label">Cuestionario</label>
<select class="form-select" id="audit_template_id" name="audit_template_id" required><option value="">Seleccionar</option>
@foreach($templates as $template)<option value="{{ $template->id }}" @selected(old('audit_template_id')==$template->id)>L{{ $template->level }} · {{ $template->name }} · {{ $template->area }}</option>@endforeach</select></div>
<div class="mb-3"><label for="assigned_to" class="form-label">Auditor</label><select class="form-select" id="assigned_to" name="assigned_to" required><option value="">Seleccionar</option>@foreach($auditors as $auditor)<option value="{{ $auditor->id }}" @selected(old('assigned_to')==$auditor->id)>{{ $auditor->name }}</option>@endforeach</select></div>
<div class="row g-2 mb-3"><div class="col-md-6"><label for="start_date" class="form-label">Desde</label><input class="form-control" type="date" id="start_date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" required></div>
<div class="col-md-6"><label for="end_date" class="form-label">Hasta</label><input class="form-control" type="date" id="end_date" name="end_date" value="{{ old('end_date', now()->toDateString()) }}" required></div></div>
<div class="mb-4"><label for="frequency" class="form-label">Frecuencia</label><select class="form-select" id="frequency" name="frequency"><option value="weekly" @selected(old('frequency')==='weekly')>Semanal (mismo día de la semana)</option><option value="daily" @selected(old('frequency')==='daily')>Diaria</option></select></div>
<div class="d-flex gap-2 flex-wrap"><button type="submit" class="btn btn-primary">Programar auditorías</button><a href="{{ route('lpa.dashboard') }}" class="btn btn-outline-secondary">Cancelar</a></div>
</form></div></div></div></div>
@endsection
