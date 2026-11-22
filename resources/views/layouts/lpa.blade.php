<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'LPA') · DevAlejandre</title>
    <link rel="icon" href="https://devalejandre.com/assets/brand/favicon.ico" sizes="any">@vite(['resources/css/lpa.css','resources/js/lpa.js'])</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary" data-bs-theme="light"><div class="app-wrapper">
<nav class="app-header navbar navbar-expand bg-body border-bottom"><div class="container-fluid">
<button class="nav-link btn" type="button" data-lte-toggle="sidebar" aria-label="Abrir menú"><i class="bi bi-list"></i></button>
<div class="ms-auto d-flex align-items-center gap-3"><span class="text-secondary small">{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Salir</button></form></div></div></nav>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark"><div class="sidebar-brand"><a href="{{ route('lpa.dashboard') }}" class="brand-link"><span class="da-sidebar-brand"><span class="da-sidebar-logo"><img src="https://devalejandre.com/assets/brand/DevAlejandre_logo.svg" alt="DevAlejandre" width="130" height="28"></span><span class="da-sidebar-product">LPA</span></span></a></div>
<div class="sidebar-wrapper"><nav class="mt-3"><ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" aria-label="Navegación">
<li class="nav-item"><a class="nav-link {{ request()->routeIs('portfolio.guide') ? 'active' : '' }}" href="{{ route('portfolio.guide') }}"><i class="nav-icon bi bi-compass"></i><p>Guía de evaluación</p></a></li>
<li class="nav-item"><a class="nav-link {{ request()->routeIs('lpa.dashboard') ? 'active' : '' }}" href="{{ route('lpa.dashboard') }}"><i class="nav-icon bi bi-clipboard-check"></i><p>Auditorías</p></a></li>
<li class="nav-item"><a class="nav-link {{ request()->routeIs('lpa.calendar') ? 'active' : '' }}" href="{{ route('lpa.calendar') }}"><i class="nav-icon bi bi-calendar3"></i><p>Calendario</p></a></li>
<li class="nav-item"><a class="nav-link {{ request()->routeIs('lpa.templates.*') ? 'active' : '' }}" href="{{ route('lpa.templates.index') }}"><i class="nav-icon bi bi-list-check"></i><p>Cuestionarios</p></a></li>
<li class="nav-item"><a class="nav-link {{ request()->routeIs('lpa.actions*') ? 'active' : '' }}" href="{{ route('lpa.actions') }}"><i class="nav-icon bi bi-exclamation-triangle"></i><p>Acciones correctivas</p></a></li>
<li class="nav-item"><a class="nav-link {{ request()->routeIs('lpa.reports') ? 'active' : '' }}" href="{{ route('lpa.reports') }}"><i class="nav-icon bi bi-bar-chart"></i><p>Indicadores</p></a></li>
<li class="nav-item"><a class="nav-link {{ request()->routeIs('lpa.trends*') ? 'active' : '' }}" href="{{ route('lpa.trends') }}"><i class="nav-icon bi bi-graph-up"></i><p>Tendencias mensuales</p></a></li>
</ul></nav></div></aside>
<main class="app-main"><div class="app-content-header"><div class="container-fluid"><h1 class="h4 mb-0">@yield('heading', 'Auditorías')</h1></div></div>
<div class="app-content"><div class="container-fluid">@if(session('ok'))<div class="alert alert-success py-2">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</div></div></main></div></body></html>
