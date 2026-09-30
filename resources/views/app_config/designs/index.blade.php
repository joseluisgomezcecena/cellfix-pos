@extends('layouts.app')
@section('title', 'Diseños de la App')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Diseños de la App
        <small class="tw-text-sm tw-text-gray-700">Imágenes que se muestran en la app Celfix Socios</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <p class="text-muted" style="margin-bottom:20px;">
            <i class="fa fa-info-circle"></i>
            Estas imágenes las verán los clientes en su app. Al guardarlas, la app las mostrará la próxima vez que se abra.
            Respeta los tamaños recomendados para que se vean nítidas en todos los dispositivos.
        </p>

        @foreach($designs as $key => $item)
            @php
                $rec    = $item['record'];
                $meta   = $item['meta'];
                $ratio  = $meta['aspect_ratio'] ?? '—';
                $size   = $meta['recommended_size'] ?? '—';
                $max_mb = round(($meta['max_bytes'] ?? 5*1024*1024) / 1024 / 1024, 1);
            @endphp

            <div style="border:1px solid #ddd; border-radius:6px; padding:20px; margin-bottom:24px;">
                <div class="row">
                    <div class="col-md-8">
                        <h4 style="margin-top:0; font-weight:bold; color:#001F3E;">{{ $meta['label'] }}</h4>
                        <p class="text-muted">{{ $meta['description'] }}</p>

                        <div class="alert alert-info" style="padding:12px; font-size:13px;">
                            <i class="fa fa-info-circle"></i>
                            <strong>Tamaño recomendado:</strong> {{ $size }} &nbsp;·&nbsp;
                            <strong>Aspect ratio:</strong> {{ $ratio }} &nbsp;·&nbsp;
                            <strong>Máximo:</strong> {{ $max_mb }} MB<br>
                            <strong>Formatos:</strong> JPG, PNG, WEBP
                        </div>

                        {!! Form::open(['url' => url('/app-config/designs/' . $key), 'method' => 'POST', 'files' => true]) !!}
                            <input type="hidden" name="_previous_path" value="{{ $rec->image_path ?? '' }}">
                            <div class="form-group">
                                {!! Form::file('image', ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/webp', 'required']) !!}
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i>
                                {{ $rec ? 'Reemplazar imagen' : 'Subir imagen' }}
                            </button>
                            @if($rec)
                                <button type="submit"
                                        formaction="{{ url('/app-config/designs/' . $key) }}"
                                        formmethod="POST"
                                        formnovalidate
                                        onclick="return confirm('¿Eliminar esta imagen? La app volverá al diseño por defecto.');"
                                        name="_method" value="DELETE"
                                        class="btn btn-danger">
                                    <i class="fa fa-trash"></i> Eliminar
                                </button>
                            @endif
                        {!! Form::close() !!}
                    </div>

                    <div class="col-md-4 text-center">
                        <div style="font-size:12px; color:#666; margin-bottom:6px;"><strong>Imagen actual</strong></div>
                        @if($rec && $rec->image_path)
                            <img src="{{ asset('storage/' . $rec->image_path) }}"
                                 style="max-width:100%; max-height:220px; border:2px solid #ddd; border-radius:6px;"
                                 alt="{{ $meta['label'] }}">
                            @if(!empty($rec->metadata['width']) && !empty($rec->metadata['height']))
                                <div style="font-size:11px; color:#999; margin-top:6px;">
                                    {{ $rec->metadata['width'] }} × {{ $rec->metadata['height'] }} px
                                </div>
                            @endif
                            <div style="font-size:11px; color:#999; margin-top:4px;">
                                Última actualización: {{ $rec->updated_at->format('d/m/Y H:i') }}
                            </div>
                        @else
                            <div style="border:2px dashed #ccc; border-radius:6px; padding:40px 20px; color:#999;">
                                <i class="fa fa-image fa-3x" style="opacity:0.4;"></i>
                                <div style="margin-top:10px; font-size:12px;">Sin imagen configurada<br>(la app usa un diseño por defecto)</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @endcomponent
</section>

@endsection
