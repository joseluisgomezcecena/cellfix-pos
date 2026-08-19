@extends('layouts.app')
@section('title', 'Editar sucursal — App Config')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ $location->name }}
        <small class="tw-text-sm tw-text-gray-700">Configuración para la app Celfix</small>
    </h1>
</section>

<section class="content">
    {!! Form::open(['url' => route('app-config.locations.update', $location->id), 'method' => 'PUT']) !!}

    @component('components.widget', ['class' => 'box-primary', 'title' => 'Contacto y visibilidad'])
        <div class="row">
            <div class="col-md-4 form-group">
                {!! Form::label('phone_app', 'Teléfono público (App):') !!}
                {!! Form::text('phone_app', $location->phone_app, ['class' => 'form-control', 'placeholder' => '6862474298']) !!}
                <small class="text-muted">Botón "LLAMAR" en la app llama a este número.</small>
            </div>
            <div class="col-md-4 form-group">
                {!! Form::label('latitude', 'Latitud:') !!}
                {!! Form::number('latitude', $location->latitude, ['class' => 'form-control', 'step' => '0.0000001', 'placeholder' => '32.6524671']) !!}
            </div>
            <div class="col-md-4 form-group">
                {!! Form::label('longitude', 'Longitud:') !!}
                {!! Form::number('longitude', $location->longitude, ['class' => 'form-control', 'step' => '0.0000001', 'placeholder' => '-115.4681800']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="checkbox">
                    <label>
                        {!! Form::hidden('is_public_in_app', 0) !!}
                        {!! Form::checkbox('is_public_in_app', 1, $location->is_public_in_app) !!}
                        <strong>Mostrar esta sucursal en la app Celfix</strong>
                    </label>
                </div>
                <small class="text-muted">
                    Tip: obtén las coordenadas en <a href="https://www.google.com/maps" target="_blank">Google Maps</a> — click derecho en el punto de tu sucursal → click en las coordenadas para copiarlas.
                </small>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => 'Horarios por día'])
        <div class="row">
            @foreach($days as $key => $label)
                @php $h = $hours[$key] ?? []; $closed = !empty($h['closed']); @endphp
                <div class="col-md-12" style="margin-bottom: 10px; padding: 8px; border: 1px solid #eee; border-radius: 4px;">
                    <div class="row">
                        <div class="col-md-2"><strong>{{ $label }}</strong></div>
                        <div class="col-md-2">
                            <label>
                                <input type="checkbox" name="hours[{{ $key }}][closed]" value="1" class="closed-check" @if($closed) checked @endif>
                                Cerrado
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label>Abre:</label>
                            <input type="time" name="hours[{{ $key }}][open]" value="{{ $h['open'] ?? '' }}" class="form-control input-sm hour-input" @if($closed) disabled @endif>
                        </div>
                        <div class="col-md-3">
                            <label>Cierra:</label>
                            <input type="time" name="hours[{{ $key }}][close]" value="{{ $h['close'] ?? '' }}" class="form-control input-sm hour-input" @if($closed) disabled @endif>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endcomponent

    <div class="text-center" style="margin: 20px 0;">
        <a href="{{ route('app-config.locations.index') }}" class="btn btn-default">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Guardar
        </button>
    </div>
    {!! Form::close() !!}
</section>

@stop
@section('javascript')
<script>
$(document).ready(function () {
    // Deshabilita/habilita inputs de hora al marcar "Cerrado"
    $(document).on('change', '.closed-check', function () {
        var $wrap = $(this).closest('.row');
        var closed = $(this).is(':checked');
        $wrap.find('.hour-input').prop('disabled', closed);
        if (closed) $wrap.find('.hour-input').val('');
    });
});
</script>
@endsection
