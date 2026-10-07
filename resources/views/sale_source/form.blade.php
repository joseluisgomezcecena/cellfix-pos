@extends('layouts.app')
@section('title', ($source->exists ? 'Editar' : 'Nuevo') . ' origen')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ $source->exists ? 'Editar origen' : 'Nuevo origen' }}
    </h1>
</section>

<section class="content">
    @php
        $url = $source->exists ? route('sale-sources.update', $source->id) : route('sale-sources.store');
        $method = $source->exists ? 'PUT' : 'POST';
    @endphp
    {!! Form::open(['url' => $url, 'method' => $method]) !!}

    @component('components.widget', ['class' => 'box-primary'])
        <div class="form-group">
            {!! Form::label('name', 'Nombre:*') !!}
            {!! Form::text('name', $source->name, ['class' => 'form-control', 'required', 'maxlength' => 150, 'placeholder' => 'Ej. Redes sociales, Volantes, Radio…']) !!}
            <small class="text-muted">Como aparecerá en el botón del modal al cobrar.</small>
        </div>

        <div class="row">
            <div class="col-md-4 form-group">
                {!! Form::label('sort_order', 'Orden:') !!}
                {!! Form::number('sort_order', $source->sort_order, ['class' => 'form-control']) !!}
                <small class="text-muted">Menor número aparece primero en el modal.</small>
            </div>
            <div class="col-md-4" style="margin-top:25px;">
                <div class="checkbox">
                    <label>
                        {!! Form::hidden('is_active', 0) !!}
                        {!! Form::checkbox('is_active', 1, $source->is_active ?? true) !!}
                        <strong>Activo</strong> (si está desactivado no aparece en el modal)
                    </label>
                </div>
            </div>
        </div>
    @endcomponent

    <div class="text-center" style="margin: 20px 0;">
        <a href="{{ route('sale-sources.index') }}" class="btn btn-default">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Guardar
        </button>
    </div>
    {!! Form::close() !!}
</section>
@stop
