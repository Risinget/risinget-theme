@extends('main_1.layouts.app')

@section('title', 'Facturas Emitidas - SIAT')

@push('styles')
<x-common.index.styles/>
<style>
    /* ==========================================================================
       PREMIUM DESIGN SYSTEM (consistent with project styles)
       ========================================================================== */
    .premium-card {
        background: #ffffff;
        border: 1px solid #e0e6ed;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 24px;
        overflow: hidden;
    }
    body.dark .premium-card {
        background: #0e1726;
        border-color: #1b2e4b;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .premium-header {
        background: #f8f9fa;
        padding: 16px 20px;
        border-bottom: 1px solid #e0e6ed;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }
    body.dark .premium-header {
        background: #182236;
        border-bottom-color: #1b2e4b;
    }

    .premium-title {
        font-size: 15px;
        font-weight: 700;
        color: #1b55e2;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    body.dark .premium-title {
        color: #00b1f4;
    }

    .premium-body {
        padding: 20px;
    }

    /* Search & Filters */
    .filter-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .filter-bar .form-control,
    .filter-bar .form-select {
        border-radius: 8px;
        font-size: 13.5px;
        border: 1px solid #bfc9d4;
        padding: 9px 14px;
        background-color: #fff;
        color: #3b3f5c;
        transition: border-color 0.2s;
    }
    body.dark .filter-bar .form-control,
    body.dark .filter-bar .form-select {
        background-color: #1b2e4b;
        border-color: #3b3f5c;
        color: #e0e6ed;
    }
    .filter-bar .form-control:focus,
    .filter-bar .form-select:focus {
        border-color: #1b55e2;
        box-shadow: 0 0 0 3px rgba(27, 85, 226, 0.15);
        outline: none;
    }
    body.dark .filter-bar .form-control:focus,
    body.dark .filter-bar .form-select:focus {
        border-color: #00b1f4;
        box-shadow: 0 0 0 3px rgba(0, 177, 244, 0.15);
    }

    /* Table Styles */
    .table-premium {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .table-premium thead th {
        background: #f1f2f3;
        color: #3b3f5c;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 12px 16px;
        border-bottom: 2px solid #e0e6ed;
        white-space: nowrap;
    }
    body.dark .table-premium thead th {
        background: #182236;
        color: #888ea8;
        border-bottom-color: #1b2e4b;
    }
    .table-premium tbody td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f2f3;
        color: #3b3f5c;
        vertical-align: middle;
    }
    body.dark .table-premium tbody td {
        border-bottom-color: #1b2e4b;
        color: #bfc9d4;
    }
    .table-premium tbody tr {
        transition: background 0.15s;
    }
    .table-premium tbody tr:hover {
        background: #f8f9ff;
    }
    body.dark .table-premium tbody tr:hover {
        background: #182236;
    }
    .table-premium tbody tr:last-child td {
        border-bottom: none;
    }

    /* Badges */
    .badge-sector {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
    .badge-educativo {
        background: rgba(0, 171, 85, 0.12);
        color: #00ab55;
        border: 1px solid rgba(0, 171, 85, 0.25);
    }
    .badge-compraventa {
        background: rgba(27, 85, 226, 0.10);
        color: #1b55e2;
        border: 1px solid rgba(27, 85, 226, 0.2);
    }
    body.dark .badge-educativo {
        background: rgba(0, 171, 85, 0.18);
    }
    body.dark .badge-compraventa {
        background: rgba(0, 177, 244, 0.15);
        color: #00b1f4;
        border-color: rgba(0, 177, 244, 0.25);
    }

    .badge-aceptada {
        background: rgba(0, 171, 85, 0.1);
        color: #00ab55;
        border: 1px solid rgba(0, 171, 85, 0.25);
    }
    .badge-rechazada {
        background: rgba(231, 81, 90, 0.1);
        color: #e7515a;
        border: 1px solid rgba(231, 81, 90, 0.25);
    }
    .badge-pendiente {
        background: rgba(226, 160, 63, 0.12);
        color: #e2a03f;
        border: 1px solid rgba(226, 160, 63, 0.25);
    }

    /* CUF field */
    .cuf-text {
        font-family: 'Courier New', monospace;
        font-size: 11px;
        color: #888ea8;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
    }
    body.dark .cuf-text {
        color: #506690;
    }

    /* Amount */
    .amount-text {
        font-weight: 700;
        color: #00ab55;
        font-size: 14px;
    }
    body.dark .amount-text {
        color: #00ab55;
    }

    /* Btn */
    .btn-premium {
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        padding: 8px 16px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: all 0.2s ease;
    }
    .btn-icon {
        border-radius: 6px;
        padding: 5px 10px;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state-icon {
        font-size: 48px;
        color: #bfc9d4;
        margin-bottom: 16px;
    }
    body.dark .empty-state-icon { color: #3b3f5c; }
    .empty-state-title {
        font-size: 16px;
        font-weight: 700;
        color: #3b3f5c;
        margin-bottom: 8px;
    }
    body.dark .empty-state-title { color: #888ea8; }
    .empty-state-subtitle {
        font-size: 13px;
        color: #888ea8;
    }

    /* Stats cards */
    .stat-card {
        background: #fff;
        border: 1px solid #e0e6ed;
        border-radius: 10px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        transition: all 0.2s;
    }
    body.dark .stat-card {
        background: #0e1726;
        border-color: #1b2e4b;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.06); }
    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .stat-icon-blue   { background: rgba(27, 85, 226, 0.12); color: #1b55e2; }
    .stat-icon-green  { background: rgba(0, 171, 85, 0.12);  color: #00ab55; }
    .stat-icon-orange { background: rgba(226, 160, 63, 0.12); color: #e2a03f; }
    body.dark .stat-icon-blue   { background: rgba(0, 177, 244, 0.15); color: #00b1f4; }
    .stat-value {
        font-size: 22px;
        font-weight: 800;
        color: #1b55e2;
        line-height: 1;
        margin-bottom: 2px;
    }
    body.dark .stat-value { color: #00b1f4; }
    .stat-label {
        font-size: 11px;
        font-weight: 600;
        color: #888ea8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .filter-bar { flex-direction: column; align-items: stretch; }
        .stat-card-col { margin-bottom: 12px; }
        .table-responsive { font-size: 12px; }
    }
</style>
@endpush

@section('content')
<div class="layout-px-spacing">
    <div class="middle-content container-xxl p-0">

        {{-- ── Breadcrumb ────────────────────────────────────────────── --}}
        <div class="page-meta">
            <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Impuestos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Facturas Emitidas</li>
                </ol>
            </nav>
        </div>

        {{-- ── Stats Cards ──────────────────────────────────────────── --}}
        <div class="row mb-3">
            @php
                $totalFacturas  = $facturas->total();
                $totalMonto     = \App\Models\Impuesto\SiatFactura::sum('monto_total');
                $totalEducativo = \App\Models\Impuesto\SiatFactura::where('codigo_documento_sector', 11)->count();
            @endphp

            <div class="col-sm-6 col-xl-4 stat-card-col mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-blue">
                        <i class="fa fa-file-invoice"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ number_format($totalFacturas) }}</div>
                        <div class="stat-label">Total Facturas</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4 stat-card-col mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-green">
                        <i class="fa fa-bolivian-sign"></i>
                    </div>
                    <div>
                        <div class="stat-value">Bs {{ number_format($totalMonto, 2) }}</div>
                        <div class="stat-label">Monto Total Emitido</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4 stat-card-col mb-3">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-orange">
                        <i class="fa fa-graduation-cap"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ number_format($totalEducativo) }}</div>
                        <div class="stat-label">Facturas Educativas</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Main Card ────────────────────────────────────────────── --}}
        <div class="premium-card">
            <div class="premium-header">
                <h5 class="premium-title">
                    <i class="fa fa-receipt"></i>
                    Facturas Emitidas
                </h5>

                {{-- Filtros --}}
                <form method="GET" action="{{ route('impuestos.facturas.index') }}" id="filterForm" class="filter-bar">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="&#xf002; Buscar NIT, Razón Social, CUF..."
                        value="{{ request('search') }}"
                        style="min-width: 260px;"
                    >

                    <select name="sector" class="form-select" style="min-width: 190px;" onchange="document.getElementById('filterForm').submit()">
                        <option value="">Todos los sectores</option>
                        <option value="1"  {{ request('sector') == '1'  ? 'selected' : '' }}>Compra/Venta (Sector 1)</option>
                        <option value="11" {{ request('sector') == '11' ? 'selected' : '' }}>Educativo (Sector 11)</option>
                    </select>

                    <button type="submit" class="btn btn-primary btn-premium">
                        <i class="fa fa-search"></i> Buscar
                    </button>

                    @if(request('search') || request('sector'))
                        <a href="{{ route('impuestos.facturas.index') }}" class="btn btn-outline-secondary btn-premium">
                            <i class="fa fa-times"></i> Limpiar
                        </a>
                    @endif
                </form>
            </div>

            <div class="premium-body p-0">
                @if($facturas->isEmpty())
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fa fa-file-invoice"></i>
                        </div>
                        <div class="empty-state-title">No se encontraron facturas</div>
                        <p class="empty-state-subtitle">
                            @if(request('search') || request('sector'))
                                No hay resultados para los filtros aplicados. <a href="{{ route('impuestos.facturas.index') }}">Limpiar filtros</a>
                            @else
                                Aún no se han emitido facturas desde el sistema SIAT.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Sector</th>
                                    <th>Cliente</th>
                                    <th>NIT / CI</th>
                                    <th>CUF</th>
                                    <th>Fecha Emisión</th>
                                    <th>Monto Total</th>
                                    <th>Estado SIAT</th>
                                    <th style="text-align:center;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($facturas as $factura)
                                <tr>
                                    {{-- ID --}}
                                    <td>
                                        <span style="font-weight:700; color:#888ea8; font-size:12px;">#{{ $factura->id }}</span>
                                    </td>

                                    {{-- Sector --}}
                                    <td>
                                        @if($factura->codigo_documento_sector == 11)
                                            <span class="badge-sector badge-educativo">
                                                <i class="fa fa-graduation-cap"></i>
                                                Educativo
                                            </span>
                                        @elseif($factura->codigo_documento_sector == 1)
                                            <span class="badge-sector badge-compraventa">
                                                <i class="fa fa-shopping-cart"></i>
                                                Compra/Venta
                                            </span>
                                        @else
                                            <span class="badge-sector badge-pendiente">
                                                Sector {{ $factura->codigo_documento_sector }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Cliente --}}
                                    <td>
                                        <span style="font-weight:600;">{{ $factura->razon_social_cliente }}</span>
                                    </td>

                                    {{-- NIT / CI --}}
                                    <td>
                                        <span style="font-family: monospace; font-size:13px;">{{ $factura->nit_cliente }}</span>
                                    </td>

                                    {{-- CUF --}}
                                    <td>
                                        <span class="cuf-text" title="{{ $factura->cuf }}">{{ $factura->cuf }}</span>
                                    </td>

                                    {{-- Fecha Emisión --}}
                                    <td style="white-space:nowrap;">
                                        <i class="fa fa-calendar-alt" style="color:#888ea8; margin-right:5px; font-size:11px;"></i>
                                        {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y H:i') }}
                                    </td>

                                    {{-- Monto --}}
                                    <td>
                                        <span class="amount-text">Bs {{ number_format($factura->monto_total, 2) }}</span>
                                    </td>

                                    {{-- Estado SIAT (codigoDescripcion) --}}
                                    <td>
                                        @php
                                            $desc  = strtoupper($factura->codigoDescripcion ?? '');
                                            $class = str_contains($desc, 'VALID') || str_contains($desc, 'ACEPT') ? 'badge-aceptada'
                                                   : (str_contains($desc, 'RECHAZ') ? 'badge-rechazada' : 'badge-pendiente');
                                        @endphp
                                        <span class="badge-sector {{ $class }}" style="font-size:10px;">
                                            {{ $factura->codigoDescripcion ?? 'PENDIENTE' }}
                                        </span>
                                    </td>

                                    {{-- Acciones --}}
                                    <td style="text-align:center; white-space:nowrap;">
                                        @if($factura->url)
                                            <a href="{{ $factura->url }}"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-info btn-icon"
                                               title="Ver QR en portal SIAT">
                                                <i class="fa fa-qrcode"></i>
                                            </a>
                                        @endif

                                        {{-- Botón XML --}}
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary btn-icon ms-1"
                                                title="Ver XML"
                                                onclick="verXml({{ $factura->id }}, `{{ addslashes($factura->documento_xml ?? '') }}`)">
                                            <i class="fa fa-code"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">
                            Mostrando {{ $facturas->firstItem() }}–{{ $facturas->lastItem() }} de {{ $facturas->total() }} facturas
                        </small>
                        {{ $facturas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal XML --}}
<div class="modal fade" id="modalXml" tabindex="-1" aria-labelledby="modalXmlLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom: 1px solid #e0e6ed;">
                <h5 class="modal-title" id="modalXmlLabel" style="font-size:15px; font-weight:700; color:#1b55e2;">
                    <i class="fa fa-code me-2"></i> Documento XML de la Factura
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0">
                <pre id="xmlContent" style="
                    margin:0;
                    padding: 20px;
                    font-size: 12px;
                    font-family: 'Courier New', monospace;
                    background: #0e1726;
                    color: #00b1f4;
                    max-height: 75vh;
                    overflow-y: auto;
                    white-space: pre-wrap;
                    word-break: break-all;
                "></pre>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e0e6ed;">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopyXml">
                    <i class="fa fa-copy me-1"></i> Copiar XML
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function verXml(id, xml) {
        document.getElementById('xmlContent').textContent = xml || '(Sin documento XML almacenado)';
        document.getElementById('modalXmlLabel').innerHTML = '<i class="fa fa-code me-2"></i> XML Factura #' + id;
        const modal = new bootstrap.Modal(document.getElementById('modalXml'));
        modal.show();
    }

    document.getElementById('btnCopyXml')?.addEventListener('click', function () {
        const text = document.getElementById('xmlContent').textContent;
        navigator.clipboard.writeText(text).then(() =>{
            this.innerHTML = '<i class="fa fa-check me-1"></i> Copiado!';
            setTimeout(() => { this.innerHTML = '<i class="fa fa-copy me-1"></i> Copiar XML'; }, 2000);
        });
    });
</script>
@endpush