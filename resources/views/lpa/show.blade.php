@extends('layouts.lpa')
@section('title','Auditoría #'.$audit->id)
@section('heading','Auditoría #'.$audit->id)
@section('content')
<div class="card mb-3"><div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap"><div><strong>{{ $audit->template->name }}</strong><div class="small text-secondary">{{ $audit->template->area }} · {{ $audit->scheduled_for->format('d/m/Y') }} · {{ $audit->assignee->name }}</div></div>
<span class="badge {{ $audit->status==='completed' ? 'text-bg-success' : 'text-bg-warning' }}">{{ $audit->status==='completed' ? 'Completada' : 'Programada' }}</span></div></div>
<div class="card"><div class="card-header"><h2 class="card-title">Lista de verificación</h2></div><div class="card-body">
@if($canComplete)
<form method="POST" action="{{ route('lpa.audits.complete',$audit) }}" id="lpa-completion-form">@csrf
<p class="small text-secondary mb-4">Evalúa cada pregunta. Cuando selecciones «No cumple», documenta la desviación en su observación. El sistema registrará el hallazgo y dará seguimiento a la acción correctiva.</p>
@foreach($audit->template->questions as $q)
<div class="border-bottom pb-3 mb-3"><label class="form-label fw-semibold" for="q_{{ $q->id }}">{{ $loop->iteration }}. {{ $q->text }}</label>
<select id="q_{{ $q->id }}" name="answers[{{ $q->id }}][result]" class="form-select mb-2 lpa-answer-result" required data-note-id="note_{{ $q->id }}"><option value="">Seleccionar resultado</option><option value="pass" @selected(old('answers.'.$q->id.'.result')==='pass')>Cumple</option><option value="fail" @selected(old('answers.'.$q->id.'.result')==='fail')>No cumple</option><option value="na" @selected(old('answers.'.$q->id.'.result')==='na')>No aplica</option></select>
<input id="note_{{ $q->id }}" name="answers[{{ $q->id }}][note]" maxlength="1000" class="form-control lpa-answer-note" value="{{ old('answers.'.$q->id.'.note') }}" placeholder="Observación (obligatoria si no cumple)"></div>
@endforeach
<div class="d-flex flex-wrap align-items-center gap-3"><button class="btn btn-primary" type="submit">Completar auditoría</button><span class="small text-secondary" id="lpa-answer-progress" role="status" aria-live="polite">Completa todas las respuestas para registrar la auditoría.</span></div></form>
@elseif($audit->status==='completed')
@foreach($audit->template->questions as $q)
@php($answer=$answers->get($q->id))
<div class="border-bottom py-3"><div class="fw-semibold">{{ $loop->iteration }}. {{ $q->text }}</div><span class="badge {{ $answer?->result==='pass' ? 'text-bg-success' : ($answer?->result==='fail' ? 'text-bg-danger' : 'text-bg-secondary') }}">{{ match($answer?->result){'pass'=>'Cumple','fail'=>'No cumple',default=>'No aplica'} }}</span>
@if($answer?->note)<div class="mt-2 small">{{ $answer->note }}</div>@endif
@if($answer?->result==='fail' && $answer->action)
<div class="mt-3 small">
 <div class="fw-semibold">Acción correctiva #{{ $answer->action->id }}</div>
 <span class="badge {{ $answer->action->status==='open' ? 'text-bg-warning' : 'text-bg-success' }}">{{ $answer->action->status==='open' ? 'Abierta' : 'Cerrada' }}</span>
 <div class="mt-1">Evidencias: {{ $answer->action->evidences->count() }}</div>
@if($answer->action->status==='closed' && $answer->action->closure_note)
<div class="mt-2 fw-semibold">Verificación de cierre</div>
<div>{{ $answer->action->closure_note }}</div>
<div class="text-secondary">{{ $answer->action->closer?->name }} · {{ $answer->action->closed_at?->format('d/m/Y H:i') }}</div>
@endif
 @foreach($answer->action->evidences as $evidence)
 <div><a href="{{ route('lpa.actions.evidences.download',$evidence) }}">{{ $evidence->original_name }}</a></div>
 @endforeach
 <a href="{{ route('lpa.actions') }}" class="d-inline-block mt-1">Ver seguimiento</a>
</div>
@endif</div>
@endforeach
@else
<div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i>Esta auditoría está asignada a otro responsable. Puedes consultar su información, pero solo el auditor asignado o un supervisor puede completarla.</div>
@endif</div></div>
@if($canComplete)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('lpa-completion-form');
    if (!form) return;
    const results = Array.from(form.querySelectorAll('.lpa-answer-result'));
    const progress = document.getElementById('lpa-answer-progress');
    const refresh = () => {
        let answered = 0;
        for (const select of results) {
            const note = document.getElementById(select.dataset.noteId);
            if (select.value) answered++;
            if (note) {
                note.required = select.value === 'fail';
                note.setAttribute('aria-required', select.value === 'fail' ? 'true' : 'false');
                note.placeholder = select.value === 'fail'
                    ? 'Describe la desviación (obligatorio)'
                    : 'Observación (opcional)';
            }
        }
        if (progress) progress.textContent = answered + ' de ' + results.length + ' respuestas completadas';
    };
    results.forEach(select => select.addEventListener('change', refresh));
    refresh();
});
</script>
@endif
<div class="mt-3"><a href="{{ route('lpa.dashboard') }}" class="btn btn-outline-secondary">Volver</a></div>
@endsection
