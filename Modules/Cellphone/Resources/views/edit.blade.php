@extends('layouts.app')

@section('title', __('cellphone::lang.edit_cellphone'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('cellphone::lang.edit_cellphone')</h1>
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

    <!-- Diagnostic Warnings -->
    @php
        $hasIssues = false;
        $issues = [];

        // Check cellphone flag
        if (!$cellphone->isCellphone()) {
            $hasIssues = true;
            $issues[] = __('cellphone::lang.diagnostic_missing_flag');
        }

        // Check enable_stock flag
        if ($cellphone->enable_stock != 1) {
            $hasIssues = true;
            $issues[] = __('cellphone::lang.diagnostic_stock_disabled');
        }

        // Check product_locations
        $hasLocations = DB::table('product_locations')->where('product_id', $cellphone->id)->exists();
        if (!$hasLocations) {
            $hasIssues = true;
            $issues[] = __('cellphone::lang.diagnostic_no_locations');
        }
    @endphp

    @if ($hasIssues)
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h4><i class="icon fa fa-warning"></i> @lang('cellphone::lang.diagnostic_title')</h4>
            <p>@lang('cellphone::lang.diagnostic_intro')</p>
            <ul>
                @foreach ($issues as $issue)
                    <li>{!! $issue !!}</li>
                @endforeach
            </ul>
            <p><strong>@lang('cellphone::lang.diagnostic_solution')</strong></p>
            <p>@lang('cellphone::lang.diagnostic_command'): <code>php artisan cellphone:repair</code></p>
        </div>
    @endif

    {!! Form::model($cellphone, ['url' => action('\Modules\Cellphone\Http\Controllers\CellphoneController@update', [$cellphone->id]), 'method' => 'put', 'id' => 'cellphone_form']) !!}

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('cellphone::lang.edit_cellphone')])

                <!-- Basic Information -->
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('imei', __('cellphone::lang.imei') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-barcode"></i>
                                </span>
                                {!! Form::text('imei', $cellphone->sku, ['class' => 'form-control', 'placeholder' => 'ABC123456789012', 'required', 'maxlength' => '15', 'pattern' => '[A-Za-z0-9]{15}']) !!}
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
                                {!! Form::text('marca', $cellphone->marca, ['class' => 'form-control', 'placeholder' => 'Samsung, Apple, Xiaomi...', 'required']) !!}
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
                                {!! Form::text('modelo', $cellphone->modelo, ['class' => 'form-control', 'placeholder' => 'Galaxy S21, iPhone 13...', 'required']) !!}
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
                                {!! Form::select('estado', $estado_options, $cellphone->estado, ['class' => 'form-control select2', 'required', 'style' => 'width: 100%;']) !!}
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
                                {!! Form::text('ubicacion', $cellphone->ubicacion, ['class' => 'form-control', 'placeholder' => 'Estante A-3']) !!}
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
                                {!! Form::select('barcode_type', ['C128' => 'Code 128', 'C39' => 'Code 39', 'EAN-13' => 'EAN-13', 'EAN-8' => 'EAN-8'], null, ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
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
                                {!! Form::select('tax_type', ['exclusive' => __('cellphone::lang.exclusive'), 'inclusive' => __('cellphone::lang.inclusive')], null, ['class' => 'form-control select2', 'style' => 'width: 100%;']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            {!! Form::label('observaciones', __('cellphone::lang.observaciones') . ':') !!}
                            {!! Form::textarea('observaciones', $cellphone->observaciones, ['class' => 'form-control', 'placeholder' => __('cellphone::lang.observaciones'), 'rows' => 3]) !!}
                        </div>
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    <!-- Stock & Pricing Management Section -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-success', 'title' => __('cellphone::lang.stock_and_pricing')])

                <div class="row">
                    <div class="col-sm-12">
                        <p class="text-muted">
                            <i class="fa fa-info-circle"></i>
                            @lang('cellphone::lang.stock_info')
                        </p>
                    </div>
                </div>

                <hr>

                <!-- Pricing Section -->
                @php
                    $variation = DB::table('variations')
                        ->join('product_variations', 'variations.product_variation_id', '=', 'product_variations.id')
                        ->where('product_variations.product_id', $cellphone->id)
                        ->select('variations.*')
                        ->first();
                @endphp

                <div class="row">
                    <div class="col-sm-12">
                        <h4><i class="fa fa-dollar"></i> @lang('cellphone::lang.pricing')</h4>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('purchase_price', __('cellphone::lang.purchase_price') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                {!! Form::text('purchase_price', $variation->default_purchase_price ?? 0, ['class' => 'form-control input_number', 'placeholder' => '0.00']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('sell_price', __('cellphone::lang.sell_price') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                {!! Form::text('sell_price', $variation->default_sell_price ?? 0, ['class' => 'form-control input_number', 'placeholder' => '0.00']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('sell_price_inc_tax', __('cellphone::lang.sell_price_inc_tax') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                {!! Form::text('sell_price_inc_tax', $variation->sell_price_inc_tax ?? 0, ['class' => 'form-control', 'placeholder' => '0.00', 'readonly']) !!}
                            </div>
                            <span class="help-block">@lang('cellphone::lang.auto_calculated_tax')</span>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('profit_percent', __('cellphone::lang.profit_percent') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-percent"></i>
                                </span>
                                {!! Form::text('profit_percent', $variation->profit_percent ?? 0, ['class' => 'form-control input_number', 'placeholder' => '0']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Current Stock Display -->
                <div class="row">
                    <div class="col-sm-12">
                        <h4><i class="fa fa-cubes"></i> @lang('cellphone::lang.current_stock')</h4>
                    </div>
                </div>

                @if($stock_details && count($stock_details) > 0)
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="stock_table">
                                    <thead>
                                        <tr class="bg-gray">
                                            <th>@lang('cellphone::lang.location')</th>
                                            <th>@lang('cellphone::lang.quantity_available')</th>
                                            <th>@lang('cellphone::lang.adjustment_type')</th>
                                            <th>@lang('cellphone::lang.adjustment_quantity')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stock_details as $stock)
                                            <tr>
                                                <td><strong>{{ $stock->location_name }}</strong></td>
                                                <td>
                                                    <span class="label label-{{ $stock->qty_available > 0 ? 'success' : 'warning' }}" style="font-size: 14px;">
                                                        {{ $stock->qty_available }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <select name="stock_adjustments[{{ $stock->location_id }}][type]" class="form-control">
                                                        <option value="add">@lang('cellphone::lang.add')</option>
                                                        <option value="subtract">@lang('cellphone::lang.subtract')</option>
                                                        <option value="set">@lang('cellphone::lang.set_to_exact')</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           name="stock_adjustments[{{ $stock->location_id }}][quantity]"
                                                           class="form-control input_number"
                                                           placeholder="0"
                                                           min="0"
                                                           step="1">
                                                    <span class="help-block"><small>@lang('cellphone::lang.leave_empty_no_changes')</small></span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> @lang('cellphone::lang.no_stock_locations')
                            </div>
                        </div>
                    </div>
                @endif

                <hr>

                <!-- Add Stock to New Location -->
                <div class="row">
                    <div class="col-sm-12">
                        <h4><i class="fa fa-plus-circle"></i> @lang('cellphone::lang.add_to_location')</h4>
                    </div>
                </div>

                <div id="new_location_stock_container">
                    <div class="row new-location-row">
                        <div class="col-sm-5">
                            <div class="form-group">
                                {!! Form::label('new_location', __('cellphone::lang.select_location') . ':') !!}
                                {!! Form::select('new_location_stock[0][location_id]', $locations, null, ['class' => 'form-control select2', 'placeholder' => __('cellphone::lang.select_location'), 'style' => 'width: 100%;']) !!}
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="form-group">
                                {!! Form::label('new_quantity', __('cellphone::lang.initial_quantity') . ':') !!}
                                {!! Form::number('new_location_stock[0][quantity]', null, ['class' => 'form-control', 'placeholder' => '0', 'min' => '0', 'step' => '1']) !!}
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-success btn-block add-location-row">
                                    <i class="fa fa-plus"></i> @lang('cellphone::lang.add_another')
                                </button>
                            </div>
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
                <i class="fa fa-save"></i> @lang('messages.update')
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

        // Dynamic location row addition
        var locationRowIndex = 1;
        $('.add-location-row').on('click', function() {
            var newRow = `
                <div class="row new-location-row" style="margin-top: 10px;">
                    <div class="col-sm-5">
                        <div class="form-group">
                            <select name="new_location_stock[${locationRowIndex}][location_id]" class="form-control select2-location" style="width: 100%;">
                                <option value="">@lang('cellphone::lang.select_location')</option>
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-5">
                        <div class="form-group">
                            <input type="number" name="new_location_stock[${locationRowIndex}][quantity]" class="form-control" placeholder="0" min="0" step="1">
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <button type="button" class="btn btn-danger btn-block remove-location-row">
                                <i class="fa fa-trash"></i> @lang('cellphone::lang.remove')
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('#new_location_stock_container').append(newRow);
            $('.select2-location').select2({
                placeholder: '@lang("cellphone::lang.select_location")',
                allowClear: true
            });
            locationRowIndex++;
        });

        // Remove location row
        $(document).on('click', '.remove-location-row', function() {
            $(this).closest('.new-location-row').remove();
        });

        // Auto-calculate sell price with tax
        function calculateSellPriceWithTax() {
            var sellPrice = parseFloat($('#sell_price').val()) || 0;
            var taxId = $('#tax').val();
            var taxType = $('#tax_type').val();

            if (taxId && taxType === 'exclusive') {
                // Get tax rate from the select option text (if available)
                // In a real scenario, you'd fetch this via AJAX or have it in a data attribute
                var sellPriceIncTax = sellPrice; // For now, just show the base price
                $('#sell_price_inc_tax').val(sellPriceIncTax.toFixed(2));
            } else {
                $('#sell_price_inc_tax').val(sellPrice.toFixed(2));
            }
        }

        $('#sell_price, #tax, #tax_type').on('change keyup', function() {
            calculateSellPriceWithTax();
        });

        // Initialize on page load
        calculateSellPriceWithTax();

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
                }
            }
        });
    });
</script>
@endsection
