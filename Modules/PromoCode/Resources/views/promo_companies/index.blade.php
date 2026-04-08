@extends('layouts.app')
@section('title', __('promocode::lang.promo_companies'))

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>{{ __('promocode::lang.promo_companies') }}
        <small>{{ __('promocode::lang.manage_promo_companies') }}</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('promocode::lang.list_promo_companies')])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{ action([\Modules\PromoCode\Http\Controllers\PromoCompanyController::class, 'create']) }}">
                    <i class="fa fa-plus"></i> {{ __('promocode::lang.add_promo_company') }}
                </a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="promo_companies_table">
                <thead>
                    <tr>
                        <th>{{ __('promocode::lang.company_name') }}</th>
                        <th>{{ __('promocode::lang.description') }}</th>
                        <th>{{ __('promocode::lang.category_discounts') }}</th>
                        <th>{{ __('promocode::lang.is_active') }}</th>
                        <th>{{ __('messages.created_at') }}</th>
                        <th>{{ __('messages.action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade promo_company_modal" tabindex="-1" role="dialog"
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        var promo_companies_table = $('#promo_companies_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ action([\Modules\PromoCode\Http\Controllers\PromoCompanyController::class, 'index']) }}',
            columnDefs: [{
                "targets": [5],
                "orderable": false,
                "searchable": false
            }],
            columns: [
                { data: 'company_name', name: 'company_name' },
                { data: 'description', name: 'description' },
                {
                    data: 'category_discounts',
                    name: 'category_discounts',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        if (data && data.length > 0) {
                            return '<span class="label label-info">' + data.length + ' {{ __("promocode::lang.categories") }}</span>';
                        }
                        return '<span class="label label-default">0 {{ __("promocode::lang.categories") }}</span>';
                    }
                },
                { data: 'is_active', name: 'is_active' },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', name: 'action' }
            ]
        });

        // Delete promo company
        $(document).on('click', 'a.delete_promo_company_button', function(e){
            e.preventDefault();
            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_promo_company,
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
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                                promo_companies_table.ajax.reload();
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
