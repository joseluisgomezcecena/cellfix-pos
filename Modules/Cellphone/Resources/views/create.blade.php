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
            <h4><i class="icon fa fa-ban"></i> @lang('cellphone::lang.error')</h4>
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
                                {!! Form::text('imei', null, ['class' => 'form-control', 'placeholder' => 'ABC123456789012', 'required', 'maxlength' => '15', 'pattern' => '[A-Za-z0-9]{15}']) !!}
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
                            {!! Form::label('name', __('cellphone::lang.product_name') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-info"></i>
                                </span>
                                {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('cellphone::lang.product_name'), 'required']) !!}
                            </div>
                            <span class="help-block">@lang('cellphone::lang.product_name_help')</span>
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
                            {!! Form::label('brand_id', __('cellphone::lang.brand') . ':') !!}
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
                            {!! Form::label('category_id', __('cellphone::lang.category') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-tags"></i>
                                </span>
                                {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;', 'id' => 'category_id']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-3 @if(!(session('business.enable_category') && session('business.enable_sub_category'))) hide @endif">
                        <div class="form-group">
                            {!! Form::label('sub_category_id', __('cellphone::lang.sub_category') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-tags"></i>
                                </span>
                                {!! Form::select('sub_category_id', $sub_categories, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;', 'id' => 'sub_category_id']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('unit_id', __('cellphone::lang.unit') . ':*') !!}
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
                            {!! Form::label('barcode_type', __('cellphone::lang.barcode_type') . ':') !!}
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
                            {!! Form::label('tax', __('cellphone::lang.applicable_tax') . ':') !!}
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
                            {!! Form::label('tax_type', __('cellphone::lang.selling_price_tax_type') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-info"></i>
                                </span>
                                {!! Form::select('tax_type', ['exclusive' => __('cellphone::lang.exclusive'), 'inclusive' => __('cellphone::lang.inclusive')], 'exclusive', ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
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
            @component('components.widget', ['class' => 'box-success', 'title' => __('cellphone::lang.product_stock')])

                <div class="row">
                    <div class="col-sm-12">
                        <h4><i class="fa fa-cubes"></i> @lang('cellphone::lang.opening_stock')</h4>
                        <p class="help-block">
                            <i class="fa fa-info-circle"></i>
                            @lang('cellphone::lang.opening_stock_info')
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('enable_stock', __('cellphone::lang.enable_stock') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-cubes"></i>
                                </span>
                                {!! Form::select('enable_stock', ['1' => __('cellphone::lang.yes'), '0' => __('cellphone::lang.no')], '1', ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'enable_stock']) !!}
                            </div>
                            <span class="help-block">@lang('cellphone::lang.enable_stock_help')</span>
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
                            <span class="help-block">@lang('cellphone::lang.business_location_help')</span>
                        </div>
                    </div>

                    <div class="col-sm-4" id="quantity_field">
                        <div class="form-group">
                            {!! Form::label('opening_stock', __('cellphone::lang.opening_stock') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-database"></i>
                                </span>
                                {!! Form::number('opening_stock', 1, ['class' => 'form-control', 'placeholder' => '0', 'min' => '0', 'step' => '1']) !!}
                            </div>
                            <span class="help-block">@lang('cellphone::lang.opening_stock_qty_help')</span>
                        </div>
                    </div>
                </div>

                <div class="row" id="price_fields">
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('purchase_price', __('cellphone::lang.purchase_price') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                {!! Form::number('purchase_price', null, ['class' => 'form-control', 'placeholder' => '0.00', 'min' => '0', 'step' => '0.01']) !!}
                            </div>
                            <span class="help-block">@lang('cellphone::lang.purchase_price_help')</span>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('sell_price', __('cellphone::lang.selling_price') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                {!! Form::number('sell_price', null, ['class' => 'form-control', 'placeholder' => '0.00', 'required', 'min' => '0', 'step' => '0.01']) !!}
                            </div>
                            <span class="help-block">@lang('cellphone::lang.sell_price_help')</span>
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

        // Load subcategories when category is selected
        $('#category_id').on('change', function() {
            var category_id = $(this).val();
            if (category_id) {
                $.ajax({
                    method: 'POST',
                    url: '/products/get_sub_categories',
                    dataType: 'html',
                    data: {
                        cat_id: category_id
                    },
                    success: function(result) {
                        $('#sub_category_id').empty().html(result).select2();
                        $('#sub_category_id').closest('.col-sm-3').removeClass('hide');
                    }
                });
            } else {
                $('#sub_category_id').empty().html('<option value="">@lang("messages.please_select")</option>').select2();
            }
        });

        // Auto-generate product name from marca and modelo
        $('#marca, #modelo').on('change keyup', function() {
            var marca = $('#marca').val();
            var modelo = $('#modelo').val();
            if (marca && modelo) {
                $('#name').val(marca + ' ' + modelo);
            }
        });

        // IMEI validation - allow alphanumeric characters
        $('#imei').on('keypress', function(e) {
            var charCode = (e.which) ? e.which : e.keyCode;
            // Allow: 0-9 (48-57), A-Z (65-90), a-z (97-122)
            if (charCode > 31 &&
                !(charCode >= 48 && charCode <= 57) &&
                !(charCode >= 65 && charCode <= 90) &&
                !(charCode >= 97 && charCode <= 122)) {
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

        // Add custom alphanumeric validation method for IMEI
        $.validator.addMethod("alphanumeric15", function(value, element) {
            return this.optional(element) || /^[A-Za-z0-9]{15}$/.test(value);
        }, "@lang('cellphone::lang.imei_invalid')");

        // Form validation
        $('#cellphone_form').validate({
            rules: {
                imei: {
                    required: true,
                    alphanumeric15: true,
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
                    alphanumeric15: "@lang('cellphone::lang.imei_invalid')",
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
                    required: "@lang('validation.required', ['attribute' => __('cellphone::lang.product_name')])"
                },
                unit_id: {
                    required: "@lang('validation.required', ['attribute' => __('cellphone::lang.unit')])"
                },
                sell_price: {
                    required: "@lang('validation.required', ['attribute' => __('cellphone::lang.selling_price')])"
                }
            }
        });
    });
</script>
@endsection
