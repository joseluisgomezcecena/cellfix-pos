@extends('layouts.app')
@section('title', __('lang_v1.commission_targets'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.commission_targets')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.commission_targets_subtitle')
        </small>
    </h1>
</section>

<section class="content">
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Da clic en un vendedor para configurar sus metas y comisión por categoría individualmente.
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.vendors')])
        @if(empty($vendor_rows))
            <p class="text-muted text-center">
                No hay usuarios con rol <strong>VENDEDORES NIVEL 1</strong> o <strong>VENDEDOR PLUS</strong> registrados.
            </p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="vendors_commission_table">
                    <thead>
                        <tr class="bg-blue">
                            <th>VENDEDOR</th>
                            <th>NIVEL</th>
                            <th>SUCURSAL(ES)</th>
                            <th class="text-center">METAS CONFIGURADAS</th>
                            <th class="text-center">@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vendor_rows as $row)
                            @php $user = $row['user']; @endphp
                            <tr>
                                <td>
                                    <strong>{{ strtoupper(trim($user->first_name . ' ' . $user->last_name)) }}</strong>
                                    @if(!empty($user->username))
                                        <br><small class="text-muted">{{ $user->username }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($row['level'] === 'VENDEDOR PLUS')
                                        <span class="label label-success">{{ $row['level'] }}</span>
                                    @else
                                        <span class="label label-primary">{{ $row['level'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(empty($row['locations']))
                                        <span class="text-muted">— sin sucursal —</span>
                                    @else
                                        @foreach($row['locations'] as $loc_name)
                                            <span class="label bg-aqua" style="margin-right: 3px;">{{ $loc_name }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($row['configured_targets'] > 0)
                                        <span class="label label-success">{{ $row['configured_targets'] }} categorías</span>
                                    @else
                                        <span class="label label-default">Sin configurar</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('commission-targets.edit', $user->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-bullseye"></i> Configurar metas
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endcomponent
</section>

@stop
