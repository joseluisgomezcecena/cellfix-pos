@extends('layouts.app')
@section('title', ($benefit->exists ? 'Editar' : 'Nuevo') . ' beneficio — App Config')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ $benefit->exists ? 'Editar beneficio' : 'Nuevo beneficio' }}
    </h1>
</section>

<section class="content">
    @php
        $url = $benefit->exists ? route('app-config.benefits.update', $benefit->id) : route('app-config.benefits.store');
        $method = $benefit->exists ? 'PUT' : 'POST';
    @endphp
    {!! Form::open(['url' => $url, 'method' => $method]) !!}

    @component('components.widget', ['class' => 'box-primary'])
        <div class="form-group">
            {!! Form::label('title', 'Título:*') !!}
            {!! Form::text('title', $benefit->title, ['class' => 'form-control', 'required', 'placeholder' => 'Cupón de Regalo $100 / 50% de descuento en Cases']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('description', 'Descripción:') !!}
            {!! Form::textarea('description', $benefit->description, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>

        <div class="row">
            <div class="col-md-3 form-group">
                {!! Form::label('value_type', 'Tipo de valor:*') !!}
                {!! Form::select('value_type', ['amount' => 'Monto ($)', 'percent' => 'Porcentaje (%)', 'text' => 'Texto libre'], $benefit->value_type, ['class' => 'form-control', 'id' => 'value_type']) !!}
            </div>
            <div class="col-md-3 form-group value-numeric">
                {!! Form::label('value', 'Valor:') !!}
                {!! Form::number('value', $benefit->value, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'placeholder' => '100']) !!}
                <small class="text-muted">Ejemplo: 100 (para $100) o 50 (para 50%)</small>
            </div>
            <div class="col-md-3 form-group value-text" style="display:none;">
                {!! Form::label('value_text', 'Etiqueta:') !!}
                {!! Form::text('value_text', $benefit->value_text, ['class' => 'form-control', 'maxlength' => 100, 'placeholder' => '2x1']) !!}
            </div>
            <div class="col-md-3 form-group">
                {!! Form::label('min_purchase', 'Compra mínima ($):') !!}
                {!! Form::number('min_purchase', $benefit->min_purchase, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'placeholder' => '499']) !!}
            </div>
            <div class="col-md-3 form-group">
                {!! Form::label('sort_order', 'Orden:') !!}
                {!! Form::number('sort_order', $benefit->sort_order, ['class' => 'form-control']) !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('conditions', 'Condiciones (aparece en asterisco):') !!}
            {!! Form::text('conditions', $benefit->conditions, ['class' => 'form-control', 'placeholder' => '*No acumulable en otras promociones']) !!}
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                {!! Form::label('target_location_id', 'Sucursal:') !!}
                {!! Form::select('target_location_id', $locations, $benefit->target_location_id, ['class' => 'form-control', 'placeholder' => 'Todas (global)']) !!}
            </div>
            <div class="col-md-6" style="margin-top:25px;">
                <div class="checkbox">
                    <label>
                        {!! Form::hidden('is_active', 0) !!}
                        {!! Form::checkbox('is_active', 1, $benefit->is_active) !!}
                        <strong>Activo</strong>
                    </label>
                </div>
            </div>
        </div>
    @endcomponent

    <div class="text-center" style="margin: 20px 0;">
        <a href="{{ route('app-config.benefits.index') }}" class="btn btn-default">Cancelar</a>
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
    function toggleValueFields() {
        var v = $('#value_type').val();
        if (v === 'text') {
            $('.value-numeric').hide();
            $('.value-text').show();
        } else {
            $('.value-numeric').show();
            $('.value-text').hide();
        }
    }
    toggleValueFields();
    $('#value_type').on('change', toggleValueFields);
});
</script>
@endsection
