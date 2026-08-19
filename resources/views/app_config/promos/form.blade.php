@extends('layouts.app')
@section('title', ($promo->exists ? 'Editar' : 'Nueva') . ' promo — App Config')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ $promo->exists ? 'Editar promo' : 'Nueva promo' }}
    </h1>
</section>

<section class="content">
    @php
        $url = $promo->exists ? route('app-config.promos.update', $promo->id) : route('app-config.promos.store');
        $method = $promo->exists ? 'PUT' : 'POST';
    @endphp
    {!! Form::open(['url' => $url, 'method' => $method, 'files' => true]) !!}

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-8 form-group">
                {!! Form::label('title', 'Título:*') !!}
                {!! Form::text('title', $promo->title, ['class' => 'form-control', 'required', 'placeholder' => '¡Obtén Vidrio Templado Gratis!']) !!}
            </div>
            <div class="col-md-4 form-group">
                {!! Form::label('category', 'Categoría:') !!}
                {!! Form::text('category', $promo->category, ['class' => 'form-control', 'placeholder' => 'GENERAL / ESTUDIANTES / NUEVO']) !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('description', 'Descripción:') !!}
            {!! Form::textarea('description', $promo->description, ['class' => 'form-control', 'rows' => 3]) !!}
        </div>

        <div class="row">
            <div class="col-md-3 form-group">
                {!! Form::label('starts_at', 'Inicia:') !!}
                {!! Form::date('starts_at', $promo->starts_at ? $promo->starts_at->format('Y-m-d') : null, ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-3 form-group">
                {!! Form::label('ends_at', 'Termina:') !!}
                {!! Form::date('ends_at', $promo->ends_at ? $promo->ends_at->format('Y-m-d') : null, ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-4 form-group">
                {!! Form::label('target_location_id', 'Sucursal:') !!}
                {!! Form::select('target_location_id', $locations, $promo->target_location_id, ['class' => 'form-control', 'placeholder' => 'Todas (global)']) !!}
            </div>
            <div class="col-md-2 form-group">
                {!! Form::label('sort_order', 'Orden:') !!}
                {!! Form::number('sort_order', $promo->sort_order, ['class' => 'form-control', 'step' => 1]) !!}
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('image', 'Imagen (opcional, max 2MB):') !!}
            {!! Form::file('image', ['class' => 'form-control']) !!}
            @if($promo->exists && $promo->image_path)
                <div style="margin-top:8px;">
                    <img src="{{ asset('storage/' . $promo->image_path) }}" style="max-width:200px; border:1px solid #ddd;">
                    <br><small class="text-muted">Imagen actual. Subir nueva la reemplaza.</small>
                </div>
            @endif
        </div>

        <div class="checkbox">
            <label>
                {!! Form::hidden('is_active', 0) !!}
                {!! Form::checkbox('is_active', 1, $promo->is_active) !!}
                <strong>Activa</strong> (si está desactivada no aparece en la app aunque esté vigente)
            </label>
        </div>
    @endcomponent

    <div class="text-center" style="margin: 20px 0;">
        <a href="{{ route('app-config.promos.index') }}" class="btn btn-default">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Guardar
        </button>
    </div>
    {!! Form::close() !!}
</section>
@stop
