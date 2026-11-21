@extends('layouts.lpa')
@section('title', $template->name)
@section('heading', $template->name)
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
<span class="badge text-bg-primary">Nivel {{ $template->level }}</span><span class="text-secondary">{{ $template->area }}</span><span class="badge {{ $template->active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $template->active ? 'Activa' : 'Inactiva' }}</span>
<div class="ms-auto d-flex gap-2">
@if($canManage && !$used)<a class="btn btn-outline-primary" href="{{ route('lpa.templates.edit',$template) }}">Editar</a>@endif
@if($canManage)<form action="{{ route('lpa.templates.toggle',$template) }}" method="POST">@csrf<button class="btn btn-outline-secondary">{{ $template->active ? 'Desactivar' : 'Activar' }}</button></form>@endif
</div></div>
@if($used)<p class="text-secondary small">Esta lista ya se utilizó en auditorías. Sus preguntas permanecen bloqueadas para conservar el historial.</p>@endif
<div class="card"><div class="card-header"><h2 class="card-title">Preguntas de la lista</h2></div><div class="card-body p-0"><table class="table mb-0"><thead><tr><th class="text-nowrap">N.º</th><th>Pregunta</th></tr></thead><tbody>@foreach($template->questions as $q)<tr><td>{{ $loop->iteration }}</td><td>{{ $q->text }}</td></tr>@endforeach</tbody></table></div></div>
<div class="mt-3"><a href="{{ route('lpa.templates.index') }}" class="btn btn-outline-secondary">Volver</a></div>
@endsection
