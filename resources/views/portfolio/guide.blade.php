@extends('layouts.lpa')
@section('title','Centro de evaluación · PlantCore LPA')
@section('heading','Centro de evaluación')
@section('content')
<section class="portfolio-hero mb-4">
  <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
    <div>
      <div class="portfolio-eyebrow mb-2"><i class="bi bi-shield-check me-1"></i> PLANTCORE / AUDITORÍAS POR CAPAS</div>
      <h2 class="h3 fw-semibold mb-2">Auditorías de proceso, del plan a la acción correctiva.</h2>
      <p class="mb-0 portfolio-subtitle">Explora un flujo operativo real: programa, ejecuta, registra hallazgos y consulta indicadores. Los datos de este entorno son ficticios.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
      <a class="btn btn-primary" href="{{ route('lpa.audits.create') }}"><i class="bi bi-plus-lg me-1"></i> Nueva auditoría</a>
      <a class="btn btn-outline-secondary" href="{{ route('lpa.reports') }}">Ver indicadores</a>
    </div>
  </div>
</section>
@if($isDemo)
<div class="portfolio-demo-note mb-4"><i class="bi bi-info-circle me-2"></i><strong>Modo evaluación:</strong> puedes crear y modificar datos de prueba. Los registros se comparten entre visitantes; no introduzcas información real.</div>
@endif
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card portfolio-stat h-100"><div class="card-body"><div class="text-secondary small">Auditorías disponibles</div><div class="portfolio-stat-value">{{ number_format($totalAudits) }}</div><div class="small text-secondary">Registros consultados de tu planta autorizada</div></div></div></div>
  <div class="col-md-4"><div class="card portfolio-stat h-100"><div class="card-body"><div class="text-secondary small">Proceso</div><div class="h5 mt-3 mb-2">Planeación → ejecución</div><div class="small text-secondary">Cuestionarios por nivel y seguimiento de auditorías</div></div></div></div>
  <div class="col-md-4"><div class="card portfolio-stat h-100"><div class="card-body"><div class="text-secondary small">Resultados</div><div class="h5 mt-3 mb-2">Hallazgos → acciones</div><div class="small text-secondary">Vencimientos, responsables y exportaciones CSV</div></div></div></div>
</div>
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3"><div><h2 class="h5 mb-1">Prueba el sistema</h2><p class="small text-secondary mb-0">Cada acceso abre una función operativa, no una pantalla simulada.</p></div></div>
<div class="row g-3 mb-4">
 @foreach([
  ['01','Planificar','Programa auditorías individuales o por lote.','lpa.batch.create','Programar varias','bi-calendar-plus'],
  ['02','Ejecutar','Abre una auditoría y captura respuestas por pregunta.','lpa.dashboard','Abrir auditorías','bi-clipboard-check'],
  ['03','Resolver','Consulta incumplimientos, responsables y fechas compromiso.','lpa.actions','Ver acciones','bi-exclamation-triangle'],
  ['04','Analizar','Compara cumplimiento mensual por planta y exporta resultados.','lpa.trends','Ver tendencias','bi-graph-up-arrow'],
 ] as [$number,$title,$description,$route,$button,$icon])
 <div class="col-md-6 col-xl-3"><div class="card portfolio-step h-100"><div class="card-body d-flex flex-column">
   <div class="d-flex justify-content-between align-items-center mb-3"><span class="portfolio-step-number">{{ $number }}</span><i class="bi {{ $icon }} fs-4 text-secondary"></i></div>
   <h3 class="h6 fw-semibold">{{ $title }}</h3><p class="text-secondary small flex-grow-1">{{ $description }}</p>
   <a class="btn btn-sm btn-outline-primary align-self-start" href="{{ route($route) }}">{{ $button }} <i class="bi bi-arrow-right ms-1"></i></a>
 </div></div></div>
 @endforeach
</div>
<div class="card mb-4"><div class="card-header d-flex justify-content-between align-items-center"><h2 class="h6 mb-0">Auditorías para explorar</h2><a href="{{ route('lpa.dashboard') }}" class="small">Ver todas</a></div>
<div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Folio</th><th>Cuestionario</th><th>Estado</th><th class="text-end">Acción</th></tr></thead><tbody>
@forelse($sampleAudits as $audit)
<tr><td class="fw-semibold">#{{ $audit->id }}</td><td>{{ $audit->template?->name }}</td><td><span class="badge {{ $audit->status === 'completed' ? 'text-bg-success' : 'text-bg-warning' }}">{{ $audit->status === 'completed' ? 'Completada' : 'Programada' }}</span></td><td class="text-end"><a href="{{ route('lpa.audits.show', $audit) }}" class="btn btn-sm btn-outline-secondary">Abrir</a></td></tr>
@empty<tr><td colspan="4" class="text-center text-secondary py-4">Todavía no hay auditorías visibles. Puedes programar una desde esta pantalla.</td></tr>@endforelse
</tbody></table></div></div></div>
<p class="small text-secondary">PlantCore LPA es una demostración funcional de software industrial desarrollada con Laravel. No incluye aún suscripciones ni separación multiempresa para ofrecerse como SaaS comercial.</p>
@endsection
