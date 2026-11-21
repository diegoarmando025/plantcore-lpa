@extends('layouts.lpa')
@section('title','Programar auditoría')
@section('heading','Programar auditoría')
@section('content')
<div class="row"><div class="col-xl-7"><div class="card"><div class="card-body"><form method="POST" action="{{ route('lpa.audits.store') }}">@csrf
<div class="mb-3"><label class="form-label" for="audit_template_id">Lista de verificación</label><select class="form-select" id="audit_template_id" name="audit_template_id" required><option value="">Seleccionar</option>@foreach($templates as $template)<option value="{{ $template->id }}" @selected(old('audit_template_id')==$template->id)>L{{ $template->level }} · {{ $template->name }} · {{ $template->area }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label" for="scheduled_for">Fecha programada</label><input class="form-control" type="date" id="scheduled_for" name="scheduled_for" value="{{ old('scheduled_for', now()->toDateString()) }}" required></div>
<div class="mb-4"><label for="assigned_to" class="form-label">Auditor responsable</label><select class="form-select" id="assigned_to" name="assigned_to" required><option value="">Seleccionar</option>@foreach($auditors as $auditor)<option value="{{ $auditor->id }}" @selected(old('assigned_to',auth()->id())==$auditor->id)>{{ $auditor->name }}</option>@endforeach</select></div>
<button class="btn btn-primary">Guardar programación</button><a href="{{ route('lpa.dashboard') }}" class="btn btn-outline-secondary">Cancelar</a></form></div></div></div></div>
@endsection
