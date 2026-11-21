<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Detalle de auditoría · PlantCore LPA</title>
@vite(['resources/css/lpa.css','resources/js/lpa.js'])
<style>
body { background:#1e232b;color:#e6e8eb; }
.demo-shell { max-width:900px; margin:0 auto; padding:34px 20px 64px; }
.demo-panel { border:1px solid #475463; border-radius:12px; padding:24px; background:#252c35; }
.demo-muted {color:#a7b1be}
.demo-question { border-bottom:1px solid #46515c;padding:16px 0; }
.demo-question:last-child {border:0}
a{color:#a7c4ff}
</style>
</head>
<body>
<main class="demo-shell">
    <a href="{{ route('lpa.public-demo.index') }}" class="d-inline-block mb-4 text-decoration-none">&larr; Volver a auditorías</a>
    <div class="demo-panel">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h1 class="h3 mb-0">{{ $audit->template->name }}</h1>
            <span class="badge {{ $audit->status==='completed' ? 'text-bg-success' : 'text-bg-warning' }}">{{ $audit->status==='completed' ? 'Completada' : 'Programada' }}</span>
        </div>
        <p class="demo-muted mb-4">{{ $audit->template->area }} · Nivel {{ $audit->template->level }} · {{ $audit->scheduled_for->format('d/m/Y') }}</p>
        <h2 class="h5">Lista de verificación</h2>
        @php($answers = $audit->answers->keyBy('audit_question_id'))
        @foreach($audit->template->questions as $question)
            @php($answer = $answers->get($question->id))
            <div class="demo-question">
                <div class="fw-semibold mb-2">{{ $loop->iteration }}. {{ $question->text }}</div>
                @if(!$answer)
                    <span class="badge text-bg-secondary">Pendiente</span>
                @else
                    <span class="badge {{ $answer->result==='pass' ? 'text-bg-success' : ($answer->result==='fail' ? 'text-bg-danger' : 'text-bg-secondary') }}">{{ $answer->result==='pass' ? 'Cumple' : ($answer->result==='fail' ? 'No cumple' : 'No aplica') }}</span>
                    @if($answer->note)<p class="mt-2 mb-1">{{ $answer->note }}</p>@endif
                    @if($answer->action)
                        <p class="demo-muted small mb-0">Acción: {{ $answer->action->description }}</p>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
    <p class="demo-muted small mt-4">Ejemplo con datos ficticios. Esta vista no permite modificar auditorías ni acciones.</p>
</main>
</body>
</html>
