<tr>
    <td>
        <div class="row">
            <div class="col-sm-8 col-xs-8">
                {!! Form::select('products[' . $row_index . '][product_id]', $products, null, ['class' => 'form-control layaway_product_dropdown input-sm mousetrap', 'placeholder' => __('layaway::lang.select_product'), 'required']) !!}
            </div>
            <div class="col-sm-4 col-xs-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-addon">
                        <i class="fa fa-search"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="@lang('lang_v1.search_product')" id="search_product_for_spos_{{$row_index}}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-8 col-xs-8">
                <div class="variation_dropdown" id="variation_dropdown_{{$row_index}}"></div>
            </div>
            <div class="col-sm-4 col-xs-4">
                <div class="sub_unit_div" id="sub_unit_div_{{$row_index}}"></div>
            </div>
        </div>
    </td>

    <td>
        <div class="input-group input-group-sm">
            {!! Form::text('products[' . $row_index . '][quantity]', 1, ['class' => 'form-control input-sm layaway_quantity input_number mousetrap', 'required', 'min' => 0]) !!}
            <span class="input-group-addon layaway_unit_text">Unit</span>
        </div>
    </td>

    <td>
        <div class="input-group input-group-sm">
            <span class="input-group-addon">
                <i class="fas fa-rupee-sign" aria-hidden="true"></i>
            </span>
            {!! Form::text('products[' . $row_index . '][unit_price]', 0, ['class' => 'form-control input-sm layaway_unit_price input_number mousetrap', 'required']) !!}
        </div>
        <span class="layaway_unit_price_display">0</span>
    </td>

    <td>
        <div class="input-group input-group-sm">
            <span class="input-group-addon">
                <i class="fas fa-rupee-sign" aria-hidden="true"></i>
            </span>
            {!! Form::hidden('products[' . $row_index . '][line_total]', 0, ['class' => 'layaway_line_total']) !!}
            <span class="layaway_line_total_display">0</span>
        </div>
    </td>

    <td>
        <button type="button" class="btn btn-danger btn-xs remove_layaway_row" title="@lang('lang_v1.remove')">
            <i class="fa fa-times"></i>
        </button>
    </td>
</tr>

<script type="text/javascript">
$(document).ready(function(){
    var row_index = '{{$row_index}}';

    //Add Product suggestion box
    $('#search_product_for_spos_' + row_index)
        .autocomplete({
            source: function(request, response) {
                $.getJSON(
                    "{{route('products.getProductsApi')}}",
                    {
                        term: request.term,
                        location_id: $('select#business_location_id').val()
                    },
                    response
                );
            },
            minLength: 2,
            response: function(event,ui) {
                if (ui.content.length == 1) {
                    ui.item = ui.content[0];
                    if(ui.item.variation_id){
                        $(this).data('selected_variation_id', ui.item.variation_id);
                    }
                    $(this).data('selected_product', ui.item);
                    $(this).val(ui.item.text);
                    $(this).trigger('product_selected');

                    setTimeout(function(){
                        $('#search_product_for_spos_' + row_index).select();
                    }, 500);
                }
            },
            focus: function( event, ui ) {
                if(ui.item.variation_id){
                    $(this).data('selected_variation_id', ui.item.variation_id);
                }
                $(this).data('selected_product', ui.item);
                $(this).val(ui.item.text);
                return false;
            },
            select: function( event, ui ) {
                if(ui.item.variation_id){
                    $(this).data('selected_variation_id', ui.item.variation_id);
                }
                $(this).data('selected_product', ui.item);
                $(this).val(ui.item.text);
                $(this).trigger('product_selected');
                return false;
            }
        })
        .autocomplete( "instance" )._renderItem = function( ul, item ) {
            var string = '<div>' + item.text;
            if(item.variation){
                string += ' (' + item.variation + ')';
            }

            string += ' <br> ' + item.sub_sku;
            string += '</div>';

            return $( "<li>" )
                .append(string)
                .appendTo( ul );
        };

    $('#search_product_for_spos_' + row_index).on('product_selected', function(){
        var product = $(this).data('selected_product');
        var variation_id = $(this).data('selected_variation_id');

        if(product){
            // Set the dropdown to selected product
            $('select[name="products[' + row_index + '][product_id]"]').val(product.product_id).trigger('change');

            // Set unit price
            $('input[name="products[' + row_index + '][unit_price]"]').val(product.selling_price);

            // Update display
            var tr = $(this).closest('tr');
            tr.find('.layaway_unit_price_display').text(__currency_trans_from_en(product.selling_price, true));

            // Calculate line total
            var quantity = __read_number($('input[name="products[' + row_index + '][quantity]"]'));
            var line_total = quantity * product.selling_price;
            $('input[name="products[' + row_index + '][line_total]"]').val(line_total);
            tr.find('.layaway_line_total_display').text(__currency_trans_from_en(line_total, true));

            // Update unit text
            tr.find('.layaway_unit_text').text(product.unit_name);

            // Clear search box
            $(this).val('');

            // Calculate totals
            calculateLayawayTotal();
        }
    });

    // Initialize select2 for this row
    $('select[name="products[' + row_index + '][product_id]"]').select2();
});
</script>