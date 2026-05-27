@extends('layouts.app')
@section('title', 'Metas — ' . $user->first_name . ' ' . $user->last_name)

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Metas y comisiones
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            {{ strtoupper(trim($user->first_name . ' ' . $user->last_name)) }}
        </small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('tool')
                    <a href="{{ route('commission-targets.index') }}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left"></i> @lang('messages.back')
                    </a>
                @endslot

                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-3">
                        <strong>Nivel:</strong>
                        @if($level === 'VENDEDOR PLUS')
                            <span class="label label-success">{{ $level }}</span>
                        @else
                            <span class="label label-primary">{{ $level }}</span>
                        @endif
                    </div>
                    <div class="col-md-9">
                        <strong>Sucursal(es):</strong>
                        @if(empty($user_locations))
                            <span class="text-muted">— sin asignar —</span>
                        @else
                            @foreach($user_locations as $name)
                                <span class="label bg-aqua">{{ $name }}</span>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    El vendedor debe alcanzar la <strong>meta de unidades</strong> de cada categoría antes de empezar a comisionar.
                    Después, gana el <strong>pago por comisión</strong> por cada unidad adicional vendida en esa categoría.
                </div>

                {!! Form::open(['url' => route('commission-targets.update', $user->id), 'method' => 'put']) !!}

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-blue">
                                <th>CATEGORÍA / MARCA</th>
                                <th class="text-center" width="200">META (unidades)</th>
                                <th class="text-center" width="200">PAGO POR COMISIÓN ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($brands as $brand)
                                @php
                                    $existing = $targets->get($brand->id);
                                    $meta_value = $existing ? $existing->meta_units : 0;
                                    $commission_value = $existing ? $existing->commission_per_unit : 0;
                                @endphp
                                <tr>
                                    <td><strong>{{ strtoupper($brand->name) }}</strong></td>
                                    <td>
                                        <input type="number" min="0" step="1"
                                            name="targets[{{ $brand->id }}][meta_units]"
                                            value="{{ $meta_value }}"
                                            class="form-control text-center">
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" min="0" step="0.01"
                                                name="targets[{{ $brand->id }}][commission_per_unit]"
                                                value="{{ number_format($commission_value, 2, '.', '') }}"
                                                class="form-control text-right">
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @if($brands->isEmpty())
                                <tr><td colspan="3" class="text-center text-muted">No hay marcas registradas en el negocio.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 15px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar metas
                    </button>
                    <a href="{{ route('commission-targets.index') }}" class="btn btn-default">
                        @lang('messages.cancel')
                    </a>
                </div>

                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>
</section>

@stop
