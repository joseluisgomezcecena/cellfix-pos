@extends('layouts.app')

@section('title', __('layaway::lang.edit_layaway'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('layaway::lang.edit_layaway') - {{ $layaway->layaway_number }}</h1>
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

    {!! Form::open(['url' => route('layaway.update', $layaway->id), 'method' => 'put', 'id' => 'layaway_form']) !!}

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('layaway_number', __('layaway::lang.layaway_number') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-hashtag"></i>
                                </span>
                                {!! Form::text('layaway_number', $layaway->layaway_number, ['class' => 'form-control', 'readonly']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('contact_id', __('layaway::lang.customer') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                {!! Form::select('contact_id', $customers, $layaway->contact_id, ['class' => 'form-control select2', 'placeholder' => __('layaway::lang.select_customer'), 'disabled', 'style' => 'width: 100%;']) !!}
                                {!! Form::hidden('contact_id', $layaway->contact_id) !!}
                            </div>
                            <small class="text-muted">Customer cannot be changed after creation</small>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('business_location_id', __('layaway::lang.business_location') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::select('business_location_id', $business_locations, $layaway->business_location_id, ['class' => 'form-control select2', 'placeholder' => __('layaway::lang.select_location'), 'disabled', 'style' => 'width: 100%;']) !!}
                                {!! Form::hidden('business_location_id', $layaway->business_location_id) !!}
                            </div>
                            <small class="text-muted">Location cannot be changed after creation</small>
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('payment_deadline', __('layaway::lang.payment_deadline') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('payment_deadline', $layaway->payment_deadline ? $layaway->payment_deadline->format('d/m/Y') : null, ['class' => 'form-control', 'placeholder' => __('layaway::lang.payment_deadline'), 'required', 'readonly']) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('down_payment_percentage', __('layaway::lang.down_payment_percentage') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-percent"></i>
                                </span>
                                {!! Form::number('down_payment_percentage', $layaway->down_payment_percentage, ['class' => 'form-control', 'placeholder' => __('layaway::lang.down_payment_percentage'), 'required', 'min' => '0', 'max' => '100', 'step' => '0.01']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('status', __('layaway::lang.status') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-info-circle"></i>
                                </span>
                                {!! Form::text('status_display', ucfirst($layaway->status), ['class' => 'form-control', 'readonly']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('notes', __('layaway::lang.notes') . ':') !!}
                            {!! Form::textarea('notes', $layaway->notes, ['class' => 'form-control', 'placeholder' => __('layaway::lang.notes'), 'rows' => 3]) !!}
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('layaway::lang.products')])
                <div class="row">
                    <div class="col-sm-12">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-search"></i>
                            </span>
                            <input type="text" class="form-control mousetrap" id="search_product_for_layaway" placeholder="@lang('layaway::lang.search_product_placeholder')" autofocus />
                        </div>
                        <small class="text-muted">@lang('layaway::lang.search_product_help')</small>
                    </div>
                </div>

                <!-- Product row counter -->
                <input type="hidden" id="product_row_count" value="0">

                <div class="row">
                    <div class="col-sm-12">
                        <div class="table-responsive">
                            <table class="table table-condensed table-bordered table-th-green text-center table-striped" id="layaway_table">
                                <thead>
                                    <tr>
                                        <th class="col-sm-4 text-center">@lang('layaway::lang.product') @show_tooltip(__('tooltip.sale_product'))</th>
                                        <th class="col-sm-2 text-center">@lang('layaway::lang.quantity')</th>
                                        <th class="col-sm-2 text-center">@lang('layaway::lang.unit_price')</th>
                                        <th class="col-sm-2 text-center">@lang('layaway::lang.line_total')</th>
                                        <th class="col-sm-2 text-center"><i class="fa fa-trash" aria-hidden="true"></i></th>
                                    </tr>
                                </thead>
                                <tbody id="layaway_table_body">
                                    <!-- Product rows will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6 col-sm-offset-6">
                        <div class="table-responsive">
                            <table class="table table-condensed text-center">
                                <tr class="success">
                                    <td>
                                        <strong>@lang('layaway::lang.total_amount'):</strong>
                                    </td>
                                    <td>
                                        <span id="layaway_total_amount" class="display_currency" data-currency_symbol="true">{{ $layaway->total_amount }}</span>
                                    </td>
                                </tr>
                                <tr class="info">
                                    <td>
                                        <strong>@lang('layaway::lang.down_payment_amount'):</strong>
                                    </td>
                                    <td>
                                        <span id="layaway_down_payment" class="display_currency" data-currency_symbol="true">{{ $layaway->down_payment_amount }}</span>
                                    </td>
                                </tr>
                                <tr class="warning">
                                    <td>
                                        <strong>@lang('layaway::lang.balance_due'):</strong>
                                    </td>
                                    <td>
                                        <span id="layaway_balance_due" class="display_currency" data-currency_symbol="true">{{ $layaway->balance_due }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <button type="submit" class="btn btn-primary pull-right" id="submit_layaway">@lang('layaway::lang.update_layaway')</button>
            <a href="{{ route('layaway.index') }}" class="btn btn-default pull-right" style="margin-right: 10px;">@lang('messages.cancel')</a>
        </div>
    </div>

    {!! Form::close() !!}

</section>
<!-- /.content -->

<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

@stop

@section('javascript')
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script>

<script type="text/javascript">
$(document).ready(function(){

    // Initialize datepicker
    $('#payment_deadline').datepicker({
        autoclose: true,
        format: datepicker_date_format,
        startDate: '+1d'
    });

    // Pre-populate existing products
    var existingProducts = @json($layaway->items);
    var productRowCount = 0;

    if (existingProducts && existingProducts.length > 0) {
        existingProducts.forEach(function(item, index) {
            var rowIndex = index + 1;
            productRowCount = rowIndex;

            var row = '<tr class="product_row">' +
                '<td>' +
                    '<input type="hidden" name="products[' + rowIndex + '][product_id]" value="' + item.product_id + '" class="layaway_product_id">' +
                    '<input type="hidden" name="products[' + rowIndex + '][variation_id]" value="' + (item.variation_id || '') + '" class="layaway_variation_id">' +
                    '<div class="form-group">' +
                        '<strong>' + (item.product ? item.product.name : 'Unknown Product') + '</strong>';

            if (item.variation && item.variation.variation_name) {
                row += '<br><small>(' + item.variation.variation_name + ')</small>';
            }

            if (item.product && item.product.sku) {
                row += '<br><small>SKU: ' + item.product.sku + '</small>';
            }

            row += '</div>' +
                '</td>' +
                '<td>' +
                    '<div class="input-group input-group-sm">' +
                        '<input type="number" name="products[' + rowIndex + '][quantity]" class="form-control layaway_quantity input_number" value="' + item.quantity + '" min="0.01" step="0.01" required>' +
                        '<span class="input-group-addon">Unit</span>' +
                    '</div>' +
                '</td>' +
                '<td>' +
                    '<div class="input-group input-group-sm">' +
                        '<span class="input-group-addon"><i class="fas fa-rupee-sign"></i></span>' +
                        '<input type="number" name="products[' + rowIndex + '][unit_price]" class="form-control layaway_unit_price input_number" value="' + item.unit_price + '" min="0" step="0.01" required>' +
                    '</div>' +
                '</td>' +
                '<td>' +
                    '<input type="hidden" name="products[' + rowIndex + '][line_total]" class="layaway_line_total" value="' + (item.quantity * item.unit_price) + '">' +
                    '<span class="layaway_line_total_display display_currency">' + __currency_trans_from_en((item.quantity * item.unit_price), true) + '</span>' +
                '</td>' +
                '<td>' +
                    '<button type="button" class="btn btn-danger btn-xs remove_layaway_row" title="Remove"><i class="fa fa-times"></i></button>' +
                '</td>' +
            '</tr>';

            $('#layaway_table_body').append(row);
        });

        $('#product_row_count').val(productRowCount);
        calculateLayawayTotal();
    }

    // Product search autocomplete (similar to create form)
    if ($('#search_product_for_layaway').length) {
        $('#search_product_for_layaway')
            .autocomplete({
                source: function(request, response) {
                    var location_id = $('input[name="business_location_id"]').val();
                    console.log('Searching for:', request.term, 'in location:', location_id);

                    $.getJSON(
                        '/products/list',
                        {
                            location_id: location_id,
                            term: request.term,
                            check_qty: true
                        }
                    ).done(function(data) {
                        console.log('Products found:', data);
                        if(data.length > 0) {
                            console.log('Found', data.length, 'products. First product:', data[0].name, 'Price:', data[0].selling_price);
                        }
                        response(data);
                    }).fail(function(jqxhr, textStatus, error) {
                        console.error('Product search failed:', textStatus, error);
                        toastr.error('Product search failed: ' + error);
                        response([]);
                    });
                },
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        $(this).data('selected_product', ui.item);
                        $(this).val(ui.item.name);
                        $(this).trigger('product_selected');
                        setTimeout(function(){
                            $('#search_product_for_layaway').select();
                        }, 500);
                    } else if (ui.content.length == 0) {
                        toastr.error('No products found');
                        $('#search_product_for_layaway').select();
                    }
                },
                focus: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                    $(this).data('selected_product', ui.item);
                    $(this).val(ui.item.name);
                    return false;
                },
                select: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        toastr.error('Out of stock');
                        return false;
                    }
                    $(this).data('selected_product', ui.item);
                    $(this).val(ui.item.name);
                    $(this).trigger('product_selected');
                    return false;
                }
            })
            .autocomplete("instance")._renderItem = function(ul, item) {
                var string = '<div>' + item.name;
                if (item.variation_name) {
                    string += ' (' + item.variation_name + ')';
                }
                string += ' <br> SKU: ' + item.sub_sku;
                if (item.qty_available !== null && item.qty_available !== undefined) {
                    string += ' <br> Stock: ' + item.qty_available;
                    if (item.qty_available <= 0) {
                        string += ' <span class="text-danger">(Out of Stock)</span>';
                    }
                }
                string += '</div>';

                return $("<li>")
                    .append(string)
                    .appendTo(ul);
            };

        // Handle product selection
        $('#search_product_for_layaway').on('product_selected', function(){
            var product = $(this).data('selected_product');

            if (product) {
                // Check if product already exists in the table
                var existing_row = false;
                $('#layaway_table_body tr').each(function() {
                    var existing_product_id = $(this).find('.layaway_product_id').val();
                    var existing_variation_id = $(this).find('.layaway_variation_id').val() || '';
                    var current_variation_id = product.variation_id || '';

                    if (existing_product_id == product.product_id && existing_variation_id == current_variation_id) {
                        // Increase quantity instead of adding new row
                        var qty_input = $(this).find('.layaway_quantity');
                        var current_qty = parseFloat(qty_input.val()) || 1;
                        qty_input.val(current_qty + 1).trigger('change');
                        existing_row = true;
                        return false;
                    }
                });

                if (!existing_row) {
                    addProductRow(product);
                }

                // Clear search box and refocus
                $(this).val('').focus();
            }
        });
    }

    // Function to add product row
    function addProductRow(product) {
        var row_count = parseInt($('#product_row_count').val());
        var row_index = row_count + 1;
        $('#product_row_count').val(row_index);

        var variation_id = product.variation_id || '';
        var unit_price = product.selling_price || 0;
        var unit_name = product.unit || 'Unit';

        var row = '<tr class="product_row">' +
            '<td>' +
                '<input type="hidden" name="products[' + row_index + '][product_id]" value="' + product.product_id + '" class="layaway_product_id">' +
                '<input type="hidden" name="products[' + row_index + '][variation_id]" value="' + variation_id + '" class="layaway_variation_id">' +
                '<div class="form-group">' +
                    '<strong>' + product.name + '</strong>';

        if (product.variation_name) {
            row += '<br><small>(' + product.variation_name + ')</small>';
        }

        row += '<br><small>SKU: ' + product.sub_sku + '</small>';

        if (product.qty_available !== null) {
            row += '<br><small class="text-info">Stock: ' + product.qty_available + ' ' + unit_name + '</small>';
        }

        row += '</div>' +
            '</td>' +
            '<td>' +
                '<div class="input-group input-group-sm">' +
                    '<input type="number" name="products[' + row_index + '][quantity]" class="form-control layaway_quantity input_number" value="1" min="0.01" step="0.01" required data-stock="' + (product.qty_available || 0) + '">' +
                    '<span class="input-group-addon">' + unit_name + '</span>' +
                '</div>' +
            '</td>' +
            '<td>' +
                '<div class="input-group input-group-sm">' +
                    '<span class="input-group-addon"><i class="fas fa-rupee-sign"></i></span>' +
                    '<input type="number" name="products[' + row_index + '][unit_price]" class="form-control layaway_unit_price input_number" value="' + unit_price + '" min="0" step="0.01" required>' +
                '</div>' +
            '</td>' +
            '<td>' +
                '<input type="hidden" name="products[' + row_index + '][line_total]" class="layaway_line_total" value="0">' +
                '<span class="layaway_line_total_display display_currency">0</span>' +
            '</td>' +
            '<td>' +
                '<button type="button" class="btn btn-danger btn-xs remove_layaway_row" title="Remove"><i class="fa fa-times"></i></button>' +
            '</td>' +
        '</tr>';

        $('#layaway_table_body').append(row);

        // Calculate line total for new row
        var new_row = $('#layaway_table_body tr:last');
        calculateRowTotal(new_row);
        calculateLayawayTotal();
    }

    // Remove product row
    $(document).on('click', '.remove_layaway_row', function() {
        $(this).closest('tr').remove();
        calculateLayawayTotal();
    });

    // Quantity or price change
    $(document).on('change keyup', '.layaway_quantity, .layaway_unit_price', function() {
        var row = $(this).closest('tr');

        // Check stock availability
        if ($(this).hasClass('layaway_quantity')) {
            var qty = parseFloat($(this).val()) || 0;
            var stock = parseFloat($(this).data('stock')) || 0;

            if (qty > stock && stock > 0) {
                toastr.warning('Requested quantity (' + qty + ') is more than available stock (' + stock + ')');
            }
        }

        calculateRowTotal(row);
        calculateLayawayTotal();
    });

    // Down payment percentage change
    $(document).on('change keyup', '#down_payment_percentage', function() {
        calculateLayawayTotal();
    });

    // Calculate row total
    function calculateRowTotal(row) {
        var quantity = parseFloat(row.find('.layaway_quantity').val()) || 0;
        var unit_price = parseFloat(row.find('.layaway_unit_price').val()) || 0;
        var line_total = quantity * unit_price;

        row.find('.layaway_line_total').val(line_total);
        row.find('.layaway_line_total_display').text(__currency_trans_from_en(line_total, true));

        __currency_convert_recursively(row.find('.layaway_line_total_display'));
    }

    // Calculate layaway total
    function calculateLayawayTotal() {
        var total_amount = 0;

        $('.layaway_line_total').each(function() {
            total_amount += parseFloat($(this).val()) || 0;
        });

        var down_payment_percentage = parseFloat($('#down_payment_percentage').val()) || 0;
        var down_payment_amount = (total_amount * down_payment_percentage) / 100;
        var balance_due = total_amount - down_payment_amount;

        $('#layaway_total_amount').text(__currency_trans_from_en(total_amount, true));
        $('#layaway_down_payment').text(__currency_trans_from_en(down_payment_amount, true));
        $('#layaway_balance_due').text(__currency_trans_from_en(balance_due, true));

        __currency_convert_recursively($('#layaway_total_amount, #layaway_down_payment, #layaway_balance_due'));
    }

    // Form validation
    $('#layaway_form').submit(function(e) {
        if ($('#layaway_table_body tr').length == 0) {
            toastr.error("@lang('layaway::lang.select_products_first')");
            e.preventDefault();
            return false;
        }

        var down_payment = parseFloat($('#down_payment_percentage').val()) || 0;
        if (down_payment < 0 || down_payment > 100) {
            toastr.error("@lang('layaway::lang.invalid_down_payment')");
            e.preventDefault();
            return false;
        }

        // Check for stock issues
        var stock_error = false;
        $('#layaway_table_body tr').each(function() {
            var qty = parseFloat($(this).find('.layaway_quantity').val()) || 0;
            var stock = parseFloat($(this).find('.layaway_quantity').data('stock')) || 0;

            if (qty > stock && stock > 0) {
                stock_error = true;
                return false;
            }
        });

        if (stock_error) {
            toastr.error('Some products have insufficient stock. Please check quantities.');
            e.preventDefault();
            return false;
        }
    });

    // Quick add customer (disabled in edit mode)
    $(document).on('click', '.add_new_customer', function() {
        toastr.warning('Customer cannot be changed when editing a layaway');
    });

    // Initialize currency formatting for pre-populated amounts
    __currency_convert_recursively($('#layaway_total_amount, #layaway_down_payment, #layaway_balance_due'));
    __currency_convert_recursively($('.layaway_line_total_display'));
});
</script>
@endsection