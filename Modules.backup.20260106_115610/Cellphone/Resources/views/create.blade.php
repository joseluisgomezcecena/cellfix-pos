@extends('layouts.app')

@section('title', __('cellphone::lang.new_cellphone'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('cellphone::lang.new_cellphone')</h1>
</section>

<!-- Main content -->
<section class="content">
    <!-- Display Validation Errors -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <h4><i class="icon fa fa-ban"></i> Error!</h4>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {!! Form::open(['url' => action('\Modules\Cellphone\Http\Controllers\CellphoneController@store'), 'method' => 'post', 'id' => 'cellphone_form']) !!}

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('cellphone::lang.add_cellphone')])

                <!-- Basic Information -->
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('imei', __('cellphone::lang.imei') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-barcode"></i>
                                </span>
                                {!! Form::text('imei', null, ['class' => 'form-control', 'placeholder' => '123456789012345', 'required', 'maxlength' => '15', 'pattern' => '[0-9]{15}']) !!}
                            </div>
                            <span class="help-block">@lang('cellphone::lang.imei_help')</span>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('marca', __('cellphone::lang.marca') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-tag"></i>
                                </span>
                                {!! Form::text('marca', null, ['class' => 'form-control', 'placeholder' => 'Samsung, Apple, Xiaomi...', 'required']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('modelo', __('cellphone::lang.modelo') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-mobile"></i>
                                </span>
                                {!! Form::text('modelo', null, ['class' => 'form-control', 'placeholder' => 'Galaxy S21, iPhone 13...', 'required']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('name', __('product.product_name') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-info"></i>
                                </span>
                                {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('product.product_name'), 'required']) !!}
                            </div>
                            <span class="help-block">Nombre completo del producto (ej: Samsung Galaxy S21 128GB Negro)</span>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('estado', __('cellphone::lang.estado') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-star"></i>
                                </span>
                                {!! Form::select('estado', $estado_options, 'nuevo', ['class' => 'form-control select2', 'required', 'style' => 'width: 100%;']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('ubicacion', __('cellphone::lang.ubicacion') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::text('ubicacion', null, ['class' => 'form-control', 'placeholder' => 'Estante A-3']) !!}
                            </div>
                            <span class="help-block">@lang('cellphone::lang.ubicacion_help')</span>
                        </div>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('brand_id', __('product.brand') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-certificate"></i>
                                </span>
                                {!! Form::select('brand_id', $brands, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('category_id', __('product.category') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-tags"></i>
                                </span>
                                {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('unit_id', __('product.unit') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-balance-scale"></i>
                                </span>
                                {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'style' => 'width: 100%;']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('barcode_type', __('product.barcode_type') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-barcode"></i>
                                </span>
                                {!! Form::select('barcode_type', ['C128' => 'Code 128', 'C39' => 'Code 39', 'EAN-13' => 'EAN-13', 'EAN-8' => 'EAN-8'], 'C128', ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warranty & Tax -->
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('warranty_id', __('cellphone::lang.warranty') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-shield"></i>
                                </span>
                                {!! Form::select('warranty_id', $warranties, null, ['class' => 'form-control select2', 'placeholder' => __('cellphone::lang.select_warranty'), 'style' => 'width: 100%;']) !!}
                            </div>
                            <span class="help-block">@lang('cellphone::lang.warranty_help')</span>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('tax', __('product.applicable_tax') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-percent"></i>
                                </span>
                                {!! Form::select('tax', $tax_rates, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('tax_type', __('product.selling_price_tax_type') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-info"></i>
                                </span>
                                {!! Form::select('tax_type', ['exclusive' => __('product.exclusive'), 'inclusive' => __('product.inclusive')], 'exclusive', ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            {!! Form::label('observaciones', __('cellphone::lang.observaciones') . ':') !!}
                            {!! Form::textarea('observaciones', null, ['class' => 'form-control', 'placeholder' => __('cellphone::lang.observaciones'), 'rows' => 3]) !!}
                        </div>
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    <!-- Stock Information Section -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-success', 'title' => __('product.product_stock')])

                <div class="row">
                    <div class="col-sm-12">
                        <h4><i class="fa fa-cubes"></i> @lang('lang_v1.opening_stock')</h4>
                        <p class="help-block">
                            <i class="fa fa-info-circle"></i>
                            Agregue el inventario inicial para este celular. Si no agrega inventario aquí, puede agregarlo después mediante Compras o Ajuste de Inventario.
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('enable_stock', __('product.enable_stock') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-cubes"></i>
                                </span>
                                {!! Form::select('enable_stock', ['1' => __('lang_v1.yes'), '0' => __('lang_v1.no')], '1', ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'enable_stock']) !!}
                            </div>
                            <span class="help-block">@lang('lang_v1.enable_stock_help')</span>
                        </div>
                    </div>

                    <div class="col-sm-4" id="location_field">
                        <div class="form-group">
                            {!! Form::label('location_id', __('business.location') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']) !!}
                            </div>
                            <span class="help-block">Ubicación del negocio donde se almacenará este celular</span>
                        </div>
                    </div>

                    <div class="col-sm-4" id="quantity_field">
                        <div class="form-group">
                            {!! Form::label('opening_stock', __('lang_v1.opening_stock') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-database"></i>
                                </span>
                                {!! Form::number('opening_stock', 1, ['class' => 'form-control', 'placeholder' => '0', 'min' => '0', 'step' => '1']) !!}
                            </div>
                            <span class="help-block">Cantidad inicial en inventario (típicamente 1 para celulares)</span>
                        </div>
                    </div>
                </div>

                <div class="row" id="price_fields">
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('purchase_price', __('product.purchase_price') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                {!! Form::number('purchase_price', null, ['class' => 'form-control', 'placeholder' => '0.00', 'min' => '0', 'step' => '0.01']) !!}
                            </div>
                            <span class="help-block">Precio de compra/costo</span>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('sell_price', __('product.selling_price') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                {!! Form::number('sell_price', null, ['class' => 'form-control', 'placeholder' => '0.00', 'required', 'min' => '0', 'step' => '0.01']) !!}
                            </div>
                            <span class="help-block">Precio de venta al público</span>
                        </div>
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    <!-- Form Actions -->
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right" id="submit_cellphone_form">
                <i class="fa fa-save"></i> @lang('messages.save')
            </button>
            <a href="{{ action('\Modules\Cellphone\Http\Controllers\CellphoneController@index') }}" class="btn btn-default pull-right" style="margin-right: 10px;">
                <i class="fa fa-times"></i> @lang('messages.cancel')
            </a>
        </div>
    </div>

    {!! Form::close() !!}

</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize Select2 on all select dropdowns
        $('.select2').select2({
            placeholder: function() {
                return $(this).data('placeholder') || '@lang("messages.please_select")';
            },
            allowClear: true
        });

        // Auto-generate product name from marca and modelo
        $('#marca, #modelo').on('change keyup', function() {
            var marca = $('#marca').val();
            var modelo = $('#modelo').val();
            if (marca && modelo) {
                $('#name').val(marca + ' ' + modelo);
            }
        });

        // IMEI validation
        $('#imei').on('keypress', function(e) {
            // Only allow numbers
            var charCode = (e.which) ? e.which : e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                e.preventDefault();
                return false;
            }
        });

        // Toggle stock fields based on enable_stock selection
        $('#enable_stock').on('change', function() {
            if ($(this).val() == '1') {
                $('#location_field, #quantity_field, #price_fields').show();
            } else {
                $('#location_field, #quantity_field, #price_fields').hide();
            }
        });

        // Form validation
        $('#cellphone_form').validate({
            rules: {
                imei: {
                    required: true,
                    digits: true,
                    minlength: 15,
                    maxlength: 15
                },
                marca: {
                    required: true
                },
                modelo: {
                    required: true
                },
                name: {
                    required: true
                },
                unit_id: {
                    required: true
                },
                sell_price: {
                    required: true,
                    min: 0
                }
            },
            messages: {
                imei: {
                    required: "@lang('cellphone::lang.imei_required')",
                    digits: "@lang('cellphone::lang.imei_invalid')",
                    minlength: "@lang('cellphone::lang.imei_invalid')",
                    maxlength: "@lang('cellphone::lang.imei_invalid')"
                },
                marca: {
                    required: "@lang('cellphone::lang.marca_required')"
                },
                modelo: {
                    required: "@lang('cellphone::lang.modelo_required')"
                },
                name: {
                    required: "@lang('validation.required', ['attribute' => __('product.product_name')])"
                },
                unit_id: {
                    required: "@lang('validation.required', ['attribute' => __('product.unit')])"
                },
                sell_price: {
                    required: "@lang('validation.required', ['attribute' => __('product.selling_price')])"
                }
            }
        });
    });
</script>
@endsection
