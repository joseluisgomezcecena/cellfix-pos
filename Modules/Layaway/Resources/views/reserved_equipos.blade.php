@extends('layouts.app')
@section('title', __('lang_v1.reserved_equipos_report'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.reserved_equipos_report')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.reserved_equipos_subtitle')
        </small>
    </h1>
</section>

<section class="content">

    @component('components.filters', ['title' => __('report.filters')])
        {!! Form::open(['url' => route('layaways.reserved-equipos'), 'method' => 'get', 'class' => 'form-inline']) !!}
            <div class="form-group">
                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $locations, $location_id, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all'), 'style' => 'min-width:220px;']) !!}
            </div>
            <button type="submit" class="btn btn-primary" style="margin-left:10px;">@lang('messages.filter')</button>
        {!! Form::close() !!}
    @endcomponent

    @php $gran_total = 0; @endphp
    @forelse($by_location as $loc_name => $items)
        @component('components.widget', ['class' => 'box-primary', 'title' => strtoupper($loc_name) . ' (' . count($items) . ')'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" style="font-size:13px;">
                    <thead>
                        <tr class="bg-light-blue">
                            <th>IMEI / SKU</th>
                            <th>@lang('sale.product')</th>
                            <th>@lang('contact.customer')</th>
                            <th class="text-center">@lang('sale.qty')</th>
                            <th># @lang('lang_v1.layaway')</th>
                            <th class="text-right">@lang('lang_v1.balance_due')</th>
                            <th>@lang('lang_v1.payment_deadline')</th>
                            <th>@lang('sale.status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $r)
                            @php $gran_total += (float) $r->quantity; @endphp
                            <tr>
                                <td><strong>{{ $r->imei }}</strong></td>
                                <td>{{ $r->product_name }}</td>
                                <td>{{ $r->customer ?: '—' }}</td>
                                <td class="text-center">{{ (float) $r->quantity }}</td>
                                <td>{{ $r->layaway_number }}</td>
                                <td class="text-right">${{ number_format($r->balance_due, 2) }}</td>
                                <td>{{ $r->payment_deadline ? \Carbon\Carbon::parse($r->payment_deadline)->format('d/m/Y') : '' }}</td>
                                <td>
                                    @php $colors = ['pending' => 'label-warning', 'active' => 'label-primary']; @endphp
                                    <span class="label {{ $colors[$r->status] ?? 'label-default' }}">{{ strtoupper($r->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endcomponent
    @empty
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No hay equipos apartados activos.
        </div>
    @endforelse

    @if($gran_total > 0)
        <div class="alert alert-warning">
            <strong>Total de equipos apartados:</strong> {{ (int) $gran_total }}
        </div>
    @endif

</section>

@stop
