@extends('layouts.app')

@section('title', __('layaway::lang.layaway_management'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('layaway::lang.layaway_management')
        <small>@lang('layaway::lang.all_layaways')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('layaway::lang.all_layaways')])
        @can('layaway.create')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{ action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@create') }}">
                        <i class="fa fa-plus"></i> @lang('layaway::lang.new_layaway')</a>
                </div>
            @endslot
        @endcan

        @can('layaway.view')
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3">
                                <label>@lang('layaway::lang.filter_by_location'):</label>
                                {!! Form::select('layaway_location_filter', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]) !!}
                            </div>
                            <div class="col-md-3">
                                <label>@lang('layaway::lang.filter_by_customer'):</label>
                                {!! Form::select('layaway_customer_filter', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]) !!}
                            </div>
                            <div class="col-md-3">
                                <label>@lang('layaway::lang.filter_by_status'):</label>
                                {!! Form::select('layaway_status_filter', [
                                    'pending' => __('layaway::lang.status_pending'),
                                    'active' => __('layaway::lang.status_active'),
                                    'completed' => __('layaway::lang.status_completed'),
                                    'cancelled' => __('layaway::lang.status_cancelled'),
                                    'overdue' => __('layaway::lang.overdue_layaways')
                                ], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]) !!}
                            </div>
                            <div class="col-md-3">
                                <label>@lang('layaway::lang.filter_by_date'):</label>
                                {!! Form::text('layaway_date_filter', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'layaway_date_filter', 'readonly']) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="layaway_table">
                    <thead>
                        <tr>
                            <th>@lang('layaway::lang.layaway_number')</th>
                            <th>@lang('layaway::lang.customer')</th>
                            <th>@lang('layaway::lang.total_amount')</th>
                            <th>@lang('layaway::lang.balance_due')</th>
                            <th>@lang('layaway::lang.status')</th>
                            <th>@lang('layaway::lang.payment_deadline')</th>
                            <th>@lang('layaway::lang.created_at')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade layaway_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->
@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function(){
    //Date range picker
    $('#layaway_date_filter').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#layaway_date_filter').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            layaway_table.ajax.reload();
        }
    );
    $('#layaway_date_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#layaway_date_filter').val('');
        layaway_table.ajax.reload();
    });

    layaway_table = $('#layaway_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@index')}}",
            data: function (d) {
                if($('#layaway_location_filter').length) {
                    d.location_id = $('#layaway_location_filter').val();
                }
                if($('#layaway_customer_filter').length) {
                    d.contact_id = $('#layaway_customer_filter').val();
                }
                if($('#layaway_status_filter').length) {
                    d.status = $('#layaway_status_filter').val();
                }

                var start = '';
                var end = '';
                if($('#layaway_date_filter').val()){
                    start = $('input#layaway_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    end = $('input#layaway_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;
            }
        },
        columnDefs: [ {
            "targets": [2, 3, 7],
            "orderable": false,
            "searchable": false
        } ],
        columns: [
            { data: 'layaway_number', name: 'layaway_number'},
            { data: 'contact', name: 'contact.name'},
            { data: 'total_amount', name: 'total_amount'},
            { data: 'balance_due', name: 'balance_due'},
            { data: 'status', name: 'status'},
            { data: 'payment_deadline', name: 'payment_deadline'},
            { data: 'created_at', name: 'created_at'},
            { data: 'action', name: 'action'}
        ],
        "fnDrawCallback": function (oSettings) {
            __currency_convert_recursively($('#layaway_table'));
        }
    });

    // Filter change events
    $('#layaway_location_filter, #layaway_customer_filter, #layaway_status_filter').change( function(){
        layaway_table.ajax.reload();
    });

    // Delete layaway
    $(document).on('click', '.delete-layaway', function(e){
        e.preventDefault();
        swal({
          title: LANG.sure,
          icon: "warning",
          buttons: true,
          dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    method: "DELETE",
                    url: href,
                    dataType: "json",
                    data: { "_token": "{{ csrf_token() }}" },
                    success: function(result){
                        if(result.success == true){
                            toastr.success(result.msg);
                            layaway_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            }
        });
    });

    // Make payment modal
    $(document).on('click', '.make-payment', function(e){
        e.preventDefault();
        var url = $(this).data('href');

        $('.layaway_modal').load(url, function(){
            $(this).modal('show');

            __currency_convert_recursively($('.layaway_modal'));
        });
    });

});
</script>
@endsection