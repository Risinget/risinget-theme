@extends('main_1.layouts.app')

@section('title', 'VSCode Theme Test - Blade PHP')

@php
    use Carbon\Carbon;

    $user = auth()->user();

    $estado = request('estado', 'activo');

    $items = collect([
        [
            'id' => 1,
            'nombre' => 'Factura Educativa',
            'sector' => 11,
            'monto' => 1500.75,
            'estado' => 'VALIDADA',
            'fecha' => now(),
        ],
        [
            'id' => 2,
            'nombre' => 'Factura Compra/Venta',
            'sector' => 1,
            'monto' => 245.50,
            'estado' => 'PENDIENTE',
            'fecha' => now()->subDay(),
        ],
        [
            'id' => 3,
            'nombre' => 'Factura Rechazada',
            'sector' => 1,
            'monto' => 99.99,
            'estado' => 'RECHAZADA',
            'fecha' => now()->subDays(3),
        ],
    ]);

    $total = $items->sum('monto');

    function badgeEstado(string $estado): string
    {
        return match (strtoupper($estado)) {
            'VALIDADA', 'ACEPTADA' => 'badge-success',
            'RECHAZADA' => 'badge-danger',
            default => 'badge-warning',
        };
    }
@endphp

@push('styles')
<style>
    :root {
        --primary: #1b55e2;
        --success: #00ab55;
        --danger: #e7515a;
        --warning: #e2a03f;
        --dark-bg: #0e1726;
        --border: #e0e6ed;
    }

    body.dark {
        background: var(--dark-bg);
        color: #bfc9d4;
    }

    .theme-test-card {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: 0 6px 24px rgba(0, 0, 0, .05);
        overflow: hidden;
    }

    body.dark .theme-test-card {
        background: #182236;
        border-color: #1b2e4b;
    }

    .theme-test-header {
        padding: 18px 22px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .theme-test-title {
        margin: 0;
        color: var(--primary);
        font-weight: 800;
    }

    .theme-test-body {
        padding: 22px;
    }

    .code-preview {
        background: #0e1726;
        color: #00b1f4;
        padding: 16px;
        border-radius: 10px;
        font-family: "Fira Code", "Courier New", monospace;
        font-size: 13px;
        white-space: pre-wrap;
    }

    .badge-custom {
        display: inline-flex;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-success { background: rgba(0, 171, 85, .12); color: var(--success); }
    .badge-danger  { background: rgba(231, 81, 90, .12); color: var(--danger); }
    .badge-warning { background: rgba(226, 160, 63, .12); color: var(--warning); }

    @media (max-width: 768px) {
        .theme-test-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endpush

@section('content')
<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">

        {{-- Breadcrumb --}}
        <div class="page-meta">
            <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('home') }}">Inicio</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Test Blade
                    </li>
                </ol>
            </nav>
        </div>

        {{-- Alertas --}}
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @elseif(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @else
            <div class="alert alert-info">
                Archivo de prueba para colores de sintaxis Blade, PHP, HTML, CSS y JS.
            </div>
        @endif

        <div class="theme-test-card">
            <div class="theme-test-header">
                <h4 class="theme-test-title">
                    <i class="fa fa-code"></i>
                    VSCode Theme Test
                </h4>

                <form method="GET" action="{{ url()->current() }}" class="d-flex gap-2">
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="activo" @selected($estado === 'activo')>Activo</option>
                        <option value="inactivo" @selected($estado === 'inactivo')>Inactivo</option>
                    </select>

                    <button class="btn btn-primary">
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="theme-test-body">

                {{-- Variables --}}
                <h5>Usuario</h5>

                <p>
                    Hola,
                    <strong>{{ $user->name ?? 'Invitado' }}</strong>
                </p>

                <p>
                    Fecha actual:
                    <code>{{ Carbon::now()->format('d/m/Y H:i:s') }}</code>
                </p>

                {{-- Componentes --}}
                <x-common.index.styles />

                <x-alert type="info" :dismissible="true">
                    Este es un componente Blade de ejemplo.
                </x-alert>

                {{-- Tabla --}}
                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Sector</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($items as $item)
                                <tr @class([
                                    'table-success' => $item['estado'] === 'VALIDADA',
                                    'table-danger' => $item['estado'] === 'RECHAZADA',
                                ])>
                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        {{ $item['nombre'] }}

                                        @if($loop->first)
                                            <span class="badge bg-primary">Nuevo</span>
                                        @endif
                                    </td>

                                    <td>
                                        @switch($item['sector'])
                                            @case(11)
                                                Educativo
                                                @break

                                            @case(1)
                                                Compra/Venta
                                                @break

                                            @default
                                                Otro
                                        @endswitch
                                    </td>

                                    <td>
                                        Bs {{ number_format($item['monto'], 2) }}
                                    </td>

                                    <td>
                                        <span class="badge-custom {{ badgeEstado($item['estado']) }}">
                                            {{ $item['estado'] }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $item['fecha']->format('d/m/Y H:i') }}
                                    </td>

                                    <td class="text-center">
                                        <a href="{{ route('facturas.show', $item['id']) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Ver
                                        </a>

                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                onclick="showItem(@js($item))">
                                            JS
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No existen registros.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="3">Total</th>
                                <th colspan="4">Bs {{ number_format($total, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Formularios --}}
                <form action="{{ route('facturas.store') }}" method="POST" class="mt-4">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="razon_social" class="form-label">
                                Razón Social
                            </label>

                            <input type="text"
                                   id="razon_social"
                                   name="razon_social"
                                   value="{{ old('razon_social') }}"
                                   class="form-control @error('razon_social') is-invalid @enderror"
                                   placeholder="Ej: Empresa Demo SRL">

                            @error('razon_social')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="monto" class="form-label">
                                Monto
                            </label>

                            <input type="number"
                                   id="monto"
                                   name="monto"
                                   step="0.01"
                                   class="form-control"
                                   value="{{ old('monto', 0) }}">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i>
                        Guardar
                    </button>
                </form>

                {{-- Código visual --}}
                <h5 class="mt-5">Bloque de código</h5>

                <pre class="code-preview"><code>{
    "blade": true,
    "php": "8.2",
    "framework": "Laravel",
    "theme_test": ["directives", "components", "css", "js"]
}</code></pre>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const appConfig = {
        debug: @json(config('app.debug')),
        locale: @json(app()->getLocale()),
        timestamp: '{{ now()->toIso8601String() }}',
    };

    function showItem(item) {
        console.group('Factura seleccionada');
        console.log('ID:', item.id);
        console.log('Nombre:', item.nombre);
        console.log('Monto:', item.monto);
        console.table(item);
        console.groupEnd();

        alert(`Factura #${item.id}: ${item.nombre}`);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach((row, index) => {
            row.dataset.index = index;

            row.addEventListener('mouseenter', event => {
                event.currentTarget.style.cursor = 'pointer';
            });
        });

        try {
            localStorage.setItem('theme-test', JSON.stringify(appConfig));
        } catch (error) {
            console.error('No se pudo guardar en localStorage:', error);
        }
    });
</script>
@endpush
