@extends('layouts.app')
@section('title', __('lang_v1.exchange_rate_settings'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.exchange_rate_settings')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.exchange_rate_subtitle')
        </small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.cash_exchange_rate')])
        {!! Form::open(['url' => route('exchange-rate.update'), 'method' => 'post']) !!}

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('cash_exchange_rate', __('lang_v1.usd_to_mxn_rate') . ':*') !!}
                    <div class="input-group" style="font-size: 18px;">
                        <span class="input-group-addon">1 USD =</span>
                        {!! Form::number('cash_exchange_rate',
                            $business->cash_exchange_rate ?? 18.00,
                            [
                                'class' => 'form-control',
                                'step' => '0.01',
                                'min' => '0',
                                'required',
                                'style' => 'font-size: 18px; height: 42px;',
                            ]
                        ) !!}
                        <span class="input-group-addon">MXN</span>
                    </div>
                    <span class="help-block">
                        @lang('lang_v1.exchange_rate_help')
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    @lang('lang_v1.exchange_rate_info')
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                    <i class="fas fa-save"></i> @lang('messages.save')
                </button>
            </div>
        </div>

        {!! Form::close() !!}
    @endcomponent
</section>

@stop
