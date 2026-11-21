@extends('layouts.lpa')
@section('title', 'Listas de verificación')
@section('heading', 'Listas de verificación')
@section('content')
@if($canManage)<div class="d-flex justify-content-end mb-3"><a href="{{ route('lpa.templates.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva lista</a></div>@endif
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Lista</th><th>Área</th><th>Nivel</th><th>Preguntas</th><th>Auditorías</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($templates as $template)<tr><td>{{ $template->name }}</td><td>{{ $template->area }}</td><td>L{{ $template->level }}</td><td>{{ $template->questions_count }}</td><td>{{ $template->audits_count }}</td><td><span class="badge {{ $template->active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $template->active ? 'Activa' : 'Inactiva' }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('lpa.templates.show',$template) }}">Abrir</a></td></tr>
@empty<tr><td colspan="7" class="text-center py-4 text-secondary">No hay listas de verificación.</td></tr>@endforelse
</tbody></table></div></div>@if($templates->hasPages())<div class="card-footer">{{ $templates->links('pagination::bootstrap-5') }}</div>@endif</div>
@endsection
