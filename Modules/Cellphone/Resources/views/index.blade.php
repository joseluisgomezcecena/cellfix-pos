@extends('layouts.app')

@section('title', __('cellphone::lang.module_name'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('cellphone::lang.module_name')
        <small>@lang('cellphone::lang.all_cellphones')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('cellphone::lang.all_cellphones')])
        @can('cellphone.create')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{ action('\\Modules\\Cellphone\\Http\\Controllers\\CellphoneController@create') }}">
                        <i class="fa fa-plus"></i> @lang('cellphone::lang.new_cellphone')</a>
                </div>
            @endslot
        @endcan

        @can('cellphone.view')
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-2">
                                <label>@lang('cellphone::lang.marca'):</label>
                                {!! Form::text('filter_marca', null, ['class' => 'form-control', 'placeholder' => __('cellphone::lang.select_marca')]) !!}
                            </div>
                            <div class="col-md-2">
                                <label>@lang('cellphone::lang.modelo'):</label>
                                {!! Form::text('filter_modelo', null, ['class' => 'form-control', 'placeholder' => __('cellphone::lang.select_modelo')]) !!}
                            </div>
                            <div class="col-md-2">
                                <label>@lang('cellphone::lang.imei'):</label>
                                {!! Form::text('filter_imei', null, ['class' => 'form-control', 'placeholder' => __('cellphone::lang.imei')]) !!}
                            </div>
                            <div class="col-md-2">
                                <label>@lang('cellphone::lang.estado'):</label>
                                {!! Form::select('filter_estado', $estado_options, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]) !!}
                            </div>
                            <div class="col-md-2">
                                <label>@lang('cellphone::lang.warranty'):</label>
                                {!! Form::select('filter_warranty', $warranties, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]) !!}
                            </div>
                            <div class="col-md-2">
                                <label>@lang('cellphone::lang.active_status'):</label>
                                {!! Form::select('active_state', ['active' => __('cellphone::lang.active'), 'inactive' => __('cellphone::lang.inactive'), 'all' => __('cellphone::lang.all')], 'active', ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'active_state']) !!}
                            </div>
                        </div>
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary" id="filter_cellphones" style="height: 34px; padding: 6px 12px; display: inline-flex; align-items: center; font-size: 14px;">
                                    <i class="fa fa-filter" style="margin-right: 5px;"></i> @lang('cellphone::lang.filter')
                                </button>
                                <button type="button" class="btn btn-default" id="clear_filters" style="height: 34px; padding: 6px 12px; display: inline-flex; align-items: center; font-size: 14px;">
                                    <i class="fa fa-times" style="margin-right: 5px;"></i> @lang('cellphone::lang.clear_filters')
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="cellphone_table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all-cellphones" /></th>
                            <th>@lang('cellphone::lang.th_imei')</th>
                            <th>@lang('cellphone::lang.th_marca')</th>
                            <th>@lang('cellphone::lang.th_modelo')</th>
                            <th>@lang('cellphone::lang.th_estado')</th>
                            <th>@lang('cellphone::lang.th_warranty')</th>
                            <th>@lang('cellphone::lang.th_ubicacion')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td colspan="8">
                                @can('cellphone.update')
                                <div style="display: flex; gap: 10px;">
                                    {!! Form::open(['url' => action('\Modules\Cellphone\Http\Controllers\CellphoneController@massDeactivate'), 'method' => 'post', 'id' => 'mass_deactivate_form']) !!}
                                    {!! Form::hidden('selected_products', null, ['id' => 'selected_products']) !!}
                                    <button type="submit" class="btn btn-warning" id="deactivate-selected">
                                        <i class="fa fa-ban"></i> @lang('cellphone::lang.deactivate_selected')
                                    </button>
                                    {!! Form::close() !!}
                                </div>
                                @endcan
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endcan
    @endcomponent

</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var cellphone_table = $('#cellphone_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ action("\\Modules\\Cellphone\\Http\\Controllers\\CellphoneController@index") }}',
                data: function(d) {
                    d.marca = $('#filter_marca').val();
                    d.modelo = $('#filter_modelo').val();
                    d.imei = $('#filter_imei').val();
                    d.estado = $('#filter_estado').val();
                    d.warranty_id = $('#filter_warranty').val();
                    d.active_state = $('#active_state').val();
                }
            },
            columns: [
                {
                    data: 'id',
                    name: 'id',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return '<input type="checkbox" class="row-select" value="' + data + '">';
                    }
                },
                { data: 'imei', name: 'sku' },
                { data: 'marca', name: 'product_custom_field1' },
                { data: 'modelo', name: 'product_custom_field2' },
                { data: 'estado', name: 'product_custom_field4' },
                { data: 'warranty', name: 'warranty_id' },
                { data: 'ubicacion', name: 'product_custom_field3' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // Filter button
        $('#filter_cellphones').click(function() {
            cellphone_table.ajax.reload();
        });

        // Clear filters button
        $('#clear_filters').click(function() {
            $('#filter_marca').val('');
            $('#filter_modelo').val('');
            $('#filter_imei').val('');
            $('#filter_estado').val('').trigger('change');
            $('#filter_warranty').val('').trigger('change');
            $('#active_state').val('active').trigger('change');
            cellphone_table.ajax.reload();
        });

        // Auto-reload when active_state dropdown changes
        $('#active_state').on('change', function() {
            cellphone_table.ajax.reload();
        });

        // Select all checkboxes
        $('#select-all-cellphones').on('click', function() {
            var rows = cellphone_table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
        });

        // Mass deactivate form submission
        $('#mass_deactivate_form').on('submit', function(e) {
            e.preventDefault();
            var selected = [];
            $('.row-select:checked').each(function() {
                selected.push($(this).val());
            });

            if (selected.length === 0) {
                toastr.error("@lang('cellphone::lang.please_select_product')");
                return false;
            }

            $('#selected_products').val(selected.join(','));
            this.submit();
        });

        // Delete cellphone
        $(document).on('click', '.delete_cellphone_button', function(e) {
            e.preventDefault();
            var url = $(this).data('href');

            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    $.ajax({
                        method: "DELETE",
                        url: url,
                        dataType: "json",
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                cellphone_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        // Activate cellphone
        $(document).on('click', '.activate_cellphone_button', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');

            swal({
                title: "@lang('cellphone::lang.confirm_activate')",
                text: "@lang('cellphone::lang.confirm_activate_product')",
                icon: "info",
                buttons: true,
            }).then((confirmed) => {
                if (confirmed) {
                    $.ajax({
                        method: "GET",
                        url: url,
                        dataType: "json",
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                cellphone_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection
