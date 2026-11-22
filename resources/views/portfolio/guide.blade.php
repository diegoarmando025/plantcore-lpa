@extends('layouts.lpa')
@section('title','Guía de evaluación · PlantCore LPA')
@section('heading','Explorar PlantCore LPA')
@section('content')
@if($isDemo)<div class="alert alert-info"><strong>Sesión de demostración activa.</strong> Trabajas con auditorías ficticias compartidas entre visitantes. Los cambios quedan guardados para otros evaluadores. No ingreses información real.</div>@endif
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card h-100"><div class="card-body"><div class="small text-secondary">Auditorías visibles</div><div class="display-6">{{ $totalAudits }}</div><a href="{{ route('lpa.dashboard') }}" class="btn btn-outline-primary mt-2">Ver auditorías</a></div></div></div>
    <div class="col-md-8"><div class="card h-100"><div class="card-body"><h2 class="h5">Ejecuta un ciclo de auditoría</h2><p>Programa una auditoría ficticia, contesta preguntas, marca un incumplimiento y comprueba que se genere una acción correctiva. Después revisa los indicadores y exporta los resultados.</p><a href="{{ route('lpa.audits.create') }}" class="btn btn-primary">Programar auditoría</a></div></div></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h2 class="h6 mb-0">Recorrido recomendado</h2></div><div class="card-body">
    <ol class="mb-0">
        <li class="mb-2"><a href="{{ route('lpa.dashboard') }}">Auditorías</a> — consulta programadas y completadas.</li>
        <li class="mb-2"><a href="{{ route('lpa.templates.index') }}">Cuestionarios</a> — revisa preguntas, niveles y áreas.</li>
        <li class="mb-2"><a href="{{ route('lpa.audits.create') }}">Programar una auditoría</a> — selecciona plantilla y auditor.</li>
        <li class="mb-2">Abre la auditoría y completa el formulario. Si marcas «No cumple», describe el hallazgo.</li>
        <li class="mb-2"><a href="{{ route('lpa.actions') }}">Acciones correctivas</a> — consulta vencimientos y cierra una acción de ejemplo.</li>
        <li class="mb-2"><a href="{{ route('lpa.reports') }}">Indicadores</a> — revisa conformidad y cumplimiento.</li>
        <li><a href="{{ route('lpa.exports.audits') }}">Exportación CSV</a> — descarga los resultados.</li>
    </ol>
    </div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h2 class="h6 mb-0">Auditorías disponibles</h2></div><div class="card-body p-0">
    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Folio</th><th>Cuestionario</th><th>Estado</th><th></th></tr></thead><tbody>
    @forelse($sampleAudits as $audit)
    <tr><td>#{{ $audit->id }}</td><td>{{ $audit->template?->name }}</td><td>{{ $audit->status === 'completed' ? 'Completada' : 'Programada' }}</td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('lpa.audits.show', $audit) }}">Abrir</a></td></tr>
    @empty<tr><td colspan="4" class="text-center text-secondary py-4">Sin auditorías visibles. Programa la primera prueba.</td></tr>@endforelse
    </tbody></table></div></div></div></div>
</div>
<p class="text-secondary small">Demostración funcional de planificación, ejecución, acciones correctivas, reportes y permisos. Es un sistema Laravel de portafolio con datos ficticios compartidos; no es todavía un SaaS multiempresa.</p>
@endsection
