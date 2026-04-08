<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-mobile"></i> @lang('cellphone::lang.dashboard_widget_title')
        </h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <div class="info-box bg-aqua">
                    <span class="info-box-icon"><i class="fa fa-mobile"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">@lang('cellphone::lang.total_cellphones')</span>
                        <span class="info-box-number">{{ $data['total_cellphones'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($data['by_marca']) && $data['by_marca']->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <h4>@lang('cellphone::lang.by_marca')</h4>
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>@lang('cellphone::lang.marca')</th>
                            <th class="text-right">@lang('cellphone::lang.total_cellphones')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['by_marca'] as $marca)
                            <tr>
                                <td>{{ $marca->marca ?? 'N/A' }}</td>
                                <td class="text-right">{{ $marca->count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if(!empty($data['by_estado']) && $data['by_estado']->count() > 0)
        <div class="row">
            <div class="col-md-12">
                <h4>@lang('cellphone::lang.by_estado')</h4>
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>@lang('cellphone::lang.estado')</th>
                            <th class="text-right">@lang('cellphone::lang.total_cellphones')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['by_estado'] as $estado)
                            @php
                                $estado_options = config('cellphone.estado_options');
                                $estado_label = $estado_options[$estado->estado] ?? $estado->estado;
                            @endphp
                            <tr>
                                <td>{{ $estado_label }}</td>
                                <td class="text-right">{{ $estado->count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-md-12 text-center">
                <a href="{{ action('\\Modules\\Cellphone\\Http\\Controllers\\CellphoneController@index') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-list"></i> @lang('cellphone::lang.all_cellphones')
                </a>
            </div>
        </div>
    </div>
</div>
