@extends('layouts.app')
@section('title', 'Affiliated Business Sales Report')

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>Affiliated Business Sales Report
        <small>View discount usage and sales data</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('start_date', 'Start Date:') !!}
                            {!! Form::text('start_date', @format_date('now'), ['class' => 'form-control', 'id' => 'start_date']) !!}
                        </div>
                    </div>

                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('end_date', 'End Date:') !!}
                            {!! Form::text('end_date', @format_date('now'), ['class' => 'form-control', 'id' => 'end_date']) !!}
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('affiliate_filter', 'Affiliated Business:') !!}
                            <select class="form-control" id="affiliate_filter">
                                <option value="">All Businesses</option>
                                @foreach($affiliates as $affiliate)
                                    <option value="{{ $affiliate->id }}">{{ $affiliate->contact->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-sm-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-primary btn-block" id="filterReportBtn">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="affiliate_sales_table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Business Name</th>
                                <th>Category</th>
                                <th>Items</th>
                                <th>Discount Applied</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray">
                                <th colspan="5" class="text-right">Total:</th>
                                <th id="total_discount">$0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        // Date pickers
        $('#start_date, #end_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd'
        });

        var affiliate_sales_table = $('#affiliate_sales_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("affiliate-discounts.reports-data") }}',
                data: function(d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.affiliate_id = $('#affiliate_filter').val();
                }
            },
            columns: [
                { data: 'created_at', name: 'created_at' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'business_name', name: 'business_name' },
                { data: 'category_name', name: 'category_name' },
                { data: 'items_affected', name: 'items_affected' },
                { data: 'total_discount_applied', name: 'total_discount_applied', render: $.fn.dataTable.render.number(',', '.', 2, '$') }
            ],
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();
                var totalDiscount = api.column(5, {page: 'current'}).data().reduce(function(a, b){
                    return parseFloat(a) + parseFloat(b);
                }, 0);
                $('#total_discount').html('$' + totalDiscount.toFixed(2));
            }
        });

        $('#filterReportBtn').click(function(){
            affiliate_sales_table.ajax.reload();
        });
    });
</script>
@endsection
