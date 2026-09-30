@extends('layouts.app')
@section('title', ($course->exists ? 'Editar' : 'Nuevo') . ' curso — App Config')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ $course->exists ? 'Editar curso' : 'Nuevo curso' }}
    </h1>
</section>

<section class="content">
    @php
        $url = $course->exists ? route('app-config.courses.update', $course->id) : route('app-config.courses.store');
        $method = $course->exists ? 'PUT' : 'POST';
        $startsVal = $course->starts_at ? \Carbon\Carbon::parse($course->starts_at)->format('Y-m-d\TH:i') : '';
        $endsVal   = $course->ends_at ? \Carbon\Carbon::parse($course->ends_at)->format('Y-m-d\TH:i') : '';
    @endphp
    {!! Form::open(['url' => $url, 'method' => $method, 'files' => true]) !!}

    @component('components.widget', ['class' => 'box-primary'])
        <div class="form-group">
            {!! Form::label('title', 'Título:*') !!}
            {!! Form::text('title', $course->title, ['class' => 'form-control', 'required', 'placeholder' => 'Curso de reparación básica de celulares']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('description', 'Descripción:') !!}
            {!! Form::textarea('description', $course->description, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Aprende a diagnosticar problemas comunes...']) !!}
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                {!! Form::label('instructor_name', 'Instructor:') !!}
                {!! Form::text('instructor_name', $course->instructor_name, ['class' => 'form-control', 'placeholder' => 'Ing. Juan Pérez']) !!}
            </div>
            <div class="col-md-6 form-group">
                {!! Form::label('target_location_id', 'Sucursal:') !!}
                {!! Form::select('target_location_id', $locations, $course->target_location_id, ['class' => 'form-control', 'placeholder' => 'Todas / sin sucursal específica']) !!}
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 form-group">
                {!! Form::label('starts_at', 'Inicia:*') !!}
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $startsVal) }}" class="form-control" required>
                <small class="text-muted">Fecha y hora de inicio.</small>
            </div>
            <div class="col-md-4 form-group">
                {!! Form::label('ends_at', 'Termina:*') !!}
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $endsVal) }}" class="form-control" required>
                <small class="text-muted">Fecha y hora de fin.</small>
            </div>
            <div class="col-md-4 form-group">
                {!! Form::label('capacity', 'Capacidad máxima:') !!}
                {!! Form::number('capacity', $course->capacity ?? 0, ['class' => 'form-control', 'min' => 0, 'max' => 10000]) !!}
                <small class="text-muted">0 = ilimitado. Al llegar al límite, la app impide nuevas inscripciones.</small>
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('image', 'Imagen del curso (opcional):') !!}
            {!! Form::file('image', ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/webp']) !!}
            <div class="alert alert-info" style="margin-top:8px; padding:10px; font-size:13px;">
                <i class="fa fa-info-circle"></i>
                <strong>Tamaño recomendado:</strong> 1200 × 675 px (aspect ratio 16:9) · JPG/PNG/WEBP · Máx 2 MB
            </div>
            @if($course->exists && $course->image_path)
                <div style="margin-top:8px;">
                    <img src="{{ asset('storage/' . $course->image_path) }}" style="max-width:300px; border:1px solid #ddd; border-radius:4px;">
                    <br><small class="text-muted">Imagen actual. Subir nueva la reemplaza.</small>
                </div>
            @endif
        </div>

        <div class="checkbox">
            <label>
                {!! Form::hidden('is_active', 0) !!}
                {!! Form::checkbox('is_active', 1, $course->is_active ?? true) !!}
                <strong>Activo</strong> (si está desactivado no aparece en la app aunque esté programado)
            </label>
        </div>
    @endcomponent

    <div class="text-center" style="margin: 20px 0;">
        <a href="{{ route('app-config.courses.index') }}" class="btn btn-default">Cancelar</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Guardar
        </button>
    </div>
    {!! Form::close() !!}
</section>
@stop
