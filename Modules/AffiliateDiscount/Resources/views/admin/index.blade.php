@extends('layouts.app')
@section('title', 'Affiliate Discounts')

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>Affiliated Business Discounts
        <small>Manage discount programs for affiliated businesses</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => 'All Affiliated Businesses'])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{ route('affiliate-discounts.create') }}">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="affiliate_discounts_table">
                <thead>
                    <tr>
                        <th>Business Name</th>
                        <th>Active Discounts</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        var affiliate_discounts_table = $('#affiliate_discounts_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("affiliate-discounts.index") }}',
            columnDefs: [{
                "targets": [4],
                "orderable": false,
                "searchable": false
            }],
            columns: [
                { data: 'contact_name', name: 'contacts.name' },
                { data: 'discount_count', name: 'discount_count', searchable: false, orderable: false },
                { data: 'is_active', name: 'affiliated_businesses.is_active' },
                { data: 'creator_username', name: 'users.username' },
                { data: 'action', name: 'action' }
            ]
        });

        // Delete confirmation
        $(document).on('click', '.delete-affiliate', function(e){
            e.preventDefault();
            var url = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: "DELETE",
                        url: url,
                        dataType: "json",
                        data: { "_token": $('meta[name="csrf-token"]').attr('content') },
                        success: function(result) {
                            if(result.success == true){
                                toastr.success(result.msg);
                                affiliate_discounts_table.ajax.reload();
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
