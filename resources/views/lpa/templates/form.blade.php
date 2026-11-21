@extends('layouts.lpa')
@section('title', $template ? 'Editar lista' : 'Nueva lista')
@section('heading', $template ? 'Editar lista de verificación' : 'Nueva lista de verificación')
@section('content')
<div class="row"><div class="col-xl-9"><div class="card"><div class="card-body">
<form method="POST" action="{{ $template ? route('lpa.templates.update',$template) : route('lpa.templates.store') }}">@csrf
@if($template)@method('PUT')@endif
<div class="row g-3">
    <div class="col-md-5"><label for="plant_id" class="form-label">Planta</label><select id="plant_id" name="plant_id" class="form-select" required>@foreach($plants as $plant)<option value="{{ $plant->id }}" @selected(old('plant_id',$template?->plant_id)==$plant->id)>{{ $plant->name }}</option>@endforeach</select></div>
    <div class="col-md-5"><label for="name" class="form-label">Nombre</label><input class="form-control" type="text" id="name" name="name" maxlength="255" value="{{ old('name',$template?->name) }}" required></div>
    <div class="col-md-5"><label for="area" class="form-label">Área</label><input class="form-control" type="text" id="area" name="area" maxlength="255" value="{{ old('area',$template?->area) }}" required></div>
    <div class="col-md-3"><label for="level" class="form-label">Nivel LPA</label><select id="level" name="level" class="form-select" required>@for($i=1;$i<=5;$i++)<option value="{{ $i }}" @selected(old('level',$template?->level??1)==$i)>Nivel {{ $i }}</option>@endfor</select></div>
    <div class="col-12"><label for="questions" class="form-label">Preguntas (una por línea)</label><textarea class="form-control" id="questions" name="questions" rows="10" required>{{ old('questions',$template?->questions->pluck('text')->implode("\n") ?? '') }}</textarea><div class="form-text">Entre 1 y 30 preguntas por lista.</div></div>
</div>
<div class="d-flex gap-2 mt-3"><button type="submit" class="btn btn-primary">Guardar lista</button><a href="{{ route('lpa.templates.index') }}" class="btn btn-outline-secondary">Cancelar</a></div>
</form></div></div></div></div>
@endsection
