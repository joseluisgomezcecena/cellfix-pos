@extends('layouts.app')
@section('title', 'Ajuste Masivo de Stock')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Ajuste Masivo de Stock
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            Sube una hoja de Excel por sucursal con el conteo físico para corregir todo el inventario de una vez
        </small>
    </h1>
</section>

<section class="content">
    @if(session('status'))
        @php $st = session('status'); @endphp
        <div class="alert alert-{{ $st['success'] ? 'success' : 'danger' }}" style="margin-bottom: 12px;">
            {!! $st['msg'] !!}
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => '1. Descargar plantilla'])
                <p class="text-muted">
                    Selecciona una sucursal y descarga la hoja de Excel con todos los productos activos y su stock actual.
                </p>
                <form method="GET" action="{{ route('stock-bulk-adjust.template') }}">
                    <div class="form-group">
                        <label>Sucursal:</label>
                        <select name="location_id" class="form-control" required>
                            <option value="">— Seleccionar sucursal —</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-download"></i> Descargar plantilla Excel
                    </button>
                </form>

                <hr>
                <h5><strong>Cómo llenar la plantilla:</strong></h5>
                <ol style="font-size: 13px;">
                    <li>La columna <strong>STOCK_NUEVO</strong> (verde, a la derecha) es la única que debes llenar.</li>
                    <li>Escribe el <strong>conteo físico real</strong> de cada producto.</li>
                    <li>Si un producto NO quieres tocarlo, <strong>deja la celda vacía</strong>.</li>
                    <li>NO modifiques las columnas grises (variation_id, sku, nombre, stock_actual).</li>
                    <li>NO agregues ni borres filas.</li>
                    <li>Guarda como .xlsx y súbelo del lado derecho.</li>
                </ol>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-success', 'title' => '2. Subir archivo lleno'])
                <p class="text-muted">
                    Después de llenar la columna STOCK_NUEVO, sube el archivo aquí. Se aplicarán las diferencias.
                </p>
                <form method="POST" action="{{ route('stock-bulk-adjust.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Sucursal (debe coincidir con la del archivo):</label>
                        <select name="location_id" class="form-control" required>
                            <option value="">— Seleccionar sucursal —</option>
                            @foreach($locations as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Archivo Excel (.xlsx, .xls o .csv):</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <div class="form-group">
                        <label>Nota (opcional):</label>
                        <input type="text" name="note" class="form-control"
                            placeholder="ej. Inventario físico mayo 2026">
                    </div>
                    <button type="submit" class="btn btn-success"
                        onclick="return confirm('¿Aplicar el ajuste? Esta acción NO se puede deshacer automáticamente.');">
                        <i class="fas fa-upload"></i> Aplicar ajuste
                    </button>
                </form>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning" style="margin-top: 10px;">
                <strong>⚠️ Importante:</strong>
                <ul style="margin-bottom: 0;">
                    <li>Cada ajuste queda registrado en <strong>/stock-corrections</strong> (Entrada o Salida con motivo "Conteo físico") para auditoría completa.</li>
                    <li>Las <strong>Entradas</strong> crean automáticamente el respaldo de compra para que el POS pueda vender la nueva existencia sin error.</li>
                    <li>Si en una fila escribes el mismo número que el stock actual, no pasa nada para ese producto.</li>
                    <li>Para ajustes <strong>de un solo producto</strong>, mejor usa la pantalla <a href="/stock-corrections" style="color: #2196f3;"><strong>Corrección de Inventario</strong></a>.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

@stop
