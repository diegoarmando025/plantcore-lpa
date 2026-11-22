@extends('layouts.lpa')
@section('title','Auditoría #'.$audit->id)
@section('heading','Auditoría #'.$audit->id)
@section('content')
<div class="card mb-3"><div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap"><div><strong>{{ $audit->template->name }}</strong><div class="small text-secondary">{{ $audit->template->area }} · {{ $audit->scheduled_for->format('d/m/Y') }} · {{ $audit->assignee->name }}</div></div>
<span class="badge {{ $audit->status==='completed' ? 'text-bg-success' : 'text-bg-warning' }}">{{ $audit->status==='completed' ? 'Completada' : 'Programada' }}</span></div></div>
<div class="card"><div class="card-header"><h2 class="card-title">Lista de verificación</h2></div><div class="card-body">
@if($canComplete)
<form method="POST" action="{{ route('lpa.audits.complete',$audit) }}">@csrf
@foreach($audit->template->questions as $q)
<div class="border-bottom pb-3 mb-3"><label class="form-label fw-semibold" for="q_{{ $q->id }}">{{ $loop->iteration }}. {{ $q->text }}</label>
<select id="q_{{ $q->id }}" name="answers[{{ $q->id }}][result]" class="form-select mb-2" required><option value="">Seleccionar resultado</option><option value="pass" @selected(old('answers.'.$q->id.'.result')==='pass')>Cumple</option><option value="fail" @selected(old('answers.'.$q->id.'.result')==='fail')>No cumple</option><option value="na" @selected(old('answers.'.$q->id.'.result')==='na')>No aplica</option></select>
<input name="answers[{{ $q->id }}][note]" maxlength="1000" class="form-control" value="{{ old('answers.'.$q->id.'.note') }}" placeholder="Observación (obligatoria si no cumple)"></div>
@endforeach
<button class="btn btn-primary">Completar auditoría</button></form>
@elseif($audit->status==='completed')
@foreach($audit->template->questions as $q)
@php($answer=$answers->get($q->id))
<div class="border-bottom py-3"><div class="fw-semibold">{{ $loop->iteration }}. {{ $q->text }}</div><span class="badge {{ $answer?->result==='pass' ? 'text-bg-success' : ($answer?->result==='fail' ? 'text-bg-danger' : 'text-bg-secondary') }}">{{ match($answer?->result){'pass'=>'Cumple','fail'=>'No cumple',default=>'No aplica'} }}</span>
@if($answer?->note)<div class="mt-2 small">{{ $answer->note }}</div>@endif</div>
@endforeach
@else
<div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i>Esta auditoría está asignada a otro responsable. Puedes consultar su información, pero solo el auditor asignado o un supervisor puede completarla.</div>
@endif</div></div>
<div class="mt-3"><a href="{{ route('lpa.dashboard') }}" class="btn btn-outline-secondary">Volver</a></div>
@endsection
