@extends('layouts.lpa')
@section('title', 'Calendario LPA')
@section('heading', 'Calendario de auditorías')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a class="btn btn-outline-secondary" href="{{ route('lpa.calendar', ['month'=>$start->subMonth()->format('Y-m'),'plant_id'=>$filters['plant_id']??null,'level'=>$filters['level']??null]) }}">Anterior</a>
    <strong class="mx-2">{{ ucfirst($start->locale('es')->translatedFormat('F Y')) }}</strong>
    <a class="btn btn-outline-secondary" href="{{ route('lpa.calendar', ['month'=>$start->addMonth()->format('Y-m'),'plant_id'=>$filters['plant_id']??null,'level'=>$filters['level']??null]) }}">Siguiente</a>
</div>
<div class="card mb-3"><div class="card-body"><form class="row g-2 align-items-end" method="GET" action="{{ route('lpa.calendar') }}">
    <div class="col-sm-4"><label for="month" class="form-label">Mes</label><input class="form-control" type="month" name="month" id="month" value="{{ $start->format('Y-m') }}" required></div>
    <div class="col-sm-4"><label for="plant_id" class="form-label">Planta</label><select class="form-select" id="plant_id" name="plant_id"><option value="">Todas mis plantas</option>@foreach($plants as $plant)<option value="{{ $plant->id }}" @selected(($filters['plant_id']??'')==$plant->id)>{{ $plant->name }}</option>@endforeach</select></div>
    <div class="col-sm-2"><label for="level" class="form-label">Nivel</label><select class="form-select" id="level" name="level"><option value="">Todos</option>@for($level=1;$level<=5;$level++)<option value="{{ $level }}" @selected(($filters['level']??'')==$level)>L{{ $level }}</option>@endfor</select></div>
    <div class="col-sm-2"><button class="btn btn-primary w-100">Filtrar</button></div>
</form></div></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-bordered mb-0 lpa-calendar">
<thead><tr>@foreach(['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $name)<th scope="col" class="text-center">{{ $name }}</th>@endforeach</tr></thead>
<tbody>@foreach(array_chunk($days, 7) as $week)<tr>@foreach($week as $day)<td class="align-top {{ $day ? '' : 'bg-body-secondary' }}">
@if($day)<div class="small fw-semibold mb-2">{{ $day->day }}</div>
@foreach($audits->get($day->toDateString(), collect()) as $audit)
<a class="d-block text-decoration-none mb-1 rounded px-2 py-1 {{ $audit->status==='completed' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}" href="{{ route('lpa.audits.show',$audit) }}"><span class="fw-semibold">#{{ $audit->id }} · L{{ $audit->template->level }}</span><span class="d-block small text-truncate">{{ $audit->template->area }} · {{ $audit->assignee->name }}</span></a>
@endforeach
@endif
</td>@endforeach</tr>@endforeach</tbody>
</table></div></div></div>
@endsection
