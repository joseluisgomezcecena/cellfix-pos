@extends('layouts.app')
@section('title', __('lang_v1.add_stock_correction'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.add_stock_correction')</h1>
</section>

<section class="content">
    {!! Form::open(['url' => route('stock-corrections.store'), 'method' => 'post', 'id' => 'stock_correction_form']) !!}

    @component('components.widget', ['class' => 'box-solid'])
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                    {!! Form::select('location_id', $locations, null, [
                        'class' => 'form-control select2',
                        'id' => 'location_id',
                        'placeholder' => __('messages.please_select'),
                        'required',
                        'style' => 'width:100%;',
                    ]) !!}
                </div>
            </div>
            <div class="col-sm-8">
                <div class="form-group">
                    {!! Form::label('variation_id', __('sale.product') . ':*') !!}
                    <select name="variation_id" id="variation_id" class="form-control" required style="width:100%;"></select>
                    {!! Form::hidden('product_id', null, ['id' => 'product_id']) !!}
                    <small class="text-muted">@lang('lang_v1.select_location_first')</small>
                </div>
            </div>
        </div>

        <div class="row" id="current_stock_box" style="display:none;">
            <div class="col-sm-12">
                <div class="alert alert-warning" style="margin-bottom:10px;">
                    <i class="fas fa-cubes"></i> @lang('lang_v1.current_stock'):
                    <strong id="current_stock_label">0</strong>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('type', __('lang_v1.correction_type') . ':*') !!}
                    {!! Form::select('type', [
                        'add' => __('lang_v1.type_add'),
                        'deduct' => __('lang_v1.type_deduct'),
                    ], 'add', ['class' => 'form-control select2', 'id' => 'type', 'required', 'style' => 'width:100%;']) !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('quantity', __('sale.qty') . ':*') !!}
                    {!! Form::number('quantity', null, [
                        'class' => 'form-control',
                        'id' => 'quantity',
                        'min' => '0.01',
                        'step' => 'any',
                        'required',
                    ]) !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('reason', __('lang_v1.reason') . ':*') !!}
                    {!! Form::select('reason', $reasons, null, [
                        'class' => 'form-control select2',
                        'id' => 'reason',
                        'placeholder' => __('messages.please_select'),
                        'required',
                        'style' => 'width:100%;',
                    ]) !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('note', __('lang_v1.notes') . ':') !!}
                    {!! Form::text('note', null, ['class' => 'form-control', 'id' => 'note']) !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12 text-center">
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">@lang('messages.save')</button>
                <a href="{{ route('stock-corrections.index') }}" class="tw-dw-btn tw-dw-btn-neutral tw-dw-btn-lg tw-text-white">@lang('messages.cancel')</a>
            </div>
        </div>
    @endcomponent

    {!! Form::close() !!}
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var $product = $('#variation_id');

        $product.select2({
            placeholder: '@lang('stock_adjustment.search_product')',
            minimumInputLength: 1,
            ajax: {
                url: '{{ route('stock-corrections.search-products') }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { term: params.term, location_id: $('#location_id').val() };
                },
                processResults: function(data) {
                    return { results: data.results };
                }
            }
        });

        // Si no hay sucursal elegida, avisar
        $product.on('select2:opening', function(e) {
            if (!$('#location_id').val()) {
                toastr.warning('@lang('lang_v1.select_location_first')');
                e.preventDefault();
            }
        });

        // Al cambiar sucursal, limpiar producto
        $('#location_id').on('change', function() {
            $product.val(null).trigger('change');
            $('#product_id').val('');
            $('#current_stock_box').hide();
        });

        // Al elegir producto, guardar product_id y mostrar stock actual
        $product.on('select2:select', function(e) {
            var d = e.params.data;
            $('#product_id').val(d.product_id);
            $('#current_stock_label').text(d.current_stock);
            $('#current_stock_box').show();
        });

        $('#stock_correction_form').on('submit', function(e) {
            if (!$('#product_id').val()) {
                e.preventDefault();
                toastr.error('@lang('sale.product')');
                return false;
            }
        });
    });
</script>
@endsection
