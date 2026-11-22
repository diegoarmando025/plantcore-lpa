<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Acceso · PlantCore LPA</title>
    @vite(['resources/css/lpa.css','resources/js/lpa.js'])
</head>
<body class="bg-body-tertiary portfolio-login-page">
<main class="portfolio-login-shell">
    <section class="portfolio-login-story" aria-label="Descripción del sistema">
        <div class="portfolio-login-brand"><i class="bi bi-clipboard-check me-2"></i> PlantCore <span>LPA</span></div>
        <div>
            <div class="portfolio-login-eyebrow">AUDITORÍAS POR CAPAS</div>
            <h1>De cada hallazgo a una acción verificable.</h1>
            <p>Programa auditorías, ejecuta cuestionarios, registra incumplimientos y da seguimiento a acciones correctivas desde una sola aplicación.</p>
            <div class="portfolio-login-features"><span><i class="bi bi-check-circle me-2"></i> Planes y calendario</span><span><i class="bi bi-check-circle me-2"></i> Ejecución por nivel</span><span><i class="bi bi-check-circle me-2"></i> Indicadores y seguimiento</span></div>
        </div>
        <div class="portfolio-login-footer">Demostración técnica · Datos ficticios</div>
    </section>
    <section class="portfolio-login-panel" aria-label="Acceso a la aplicación">
        <div class="portfolio-login-form">
            <div class="portfolio-login-eyebrow">ACCESO AL SISTEMA</div>
            <h2 class="h3 fw-semibold mb-2">Explora las auditorías</h2>
            <p class="text-secondary mb-4">Ingresa al escenario interactivo y recorre el proceso completo sin solicitar credenciales.</p>
            @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('portfolio.demo.enter') }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-arrow-right-circle me-2"></i> Entrar al demo interactivo</button>
            </form>
            <p class="small text-secondary mt-2 mb-4">Entorno compartido. No introduzcas información personal ni de clientes.</p>
            <div class="portfolio-login-divider"><span>Acceso privado</span></div>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label" for="email">Correo electrónico</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="username" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="mb-4"><label for="password" class="form-label">Contraseña</label><input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <button class="btn btn-outline-primary w-100" type="submit">Iniciar sesión</button>
            </form>
        </div>
    </section>
</main>
</body>
</html>
