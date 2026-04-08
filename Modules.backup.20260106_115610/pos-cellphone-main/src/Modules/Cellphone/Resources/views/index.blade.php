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
                            <div class="col-md-3">
                                <label>@lang('cellphone::lang.marca'):</label>
                                {!! Form::text('filter_marca', null, ['class' => 'form-control', 'placeholder' => __('cellphone::lang.select_marca')]) !!}
                            </div>
                            <div class="col-md-3">
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
                        </div>
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary" id="filter_cellphones">
                                    <i class="fa fa-filter"></i> @lang('cellphone::lang.filter')
                                </button>
                                <button type="button" class="btn btn-default" id="clear_filters">
                                    <i class="fa fa-times"></i> @lang('cellphone::lang.clear_filters')
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
                            <th>@lang('cellphone::lang.th_imei')</th>
                            <th>@lang('cellphone::lang.th_marca')</th>
                            <th>@lang('cellphone::lang.th_modelo')</th>
                            <th>@lang('cellphone::lang.th_estado')</th>
                            <th>@lang('cellphone::lang.th_warranty')</th>
                            <th>@lang('cellphone::lang.th_ubicacion')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
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
                }
            },
            columns: [
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
            cellphone_table.ajax.reload();
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
    });
</script>
@endsection
