@extends('layouts.app')
@section('title', __('lang_v1.technicians'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.technicians')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.manage_technicians')
        </small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_technicians')])
        @slot('tool')
            <div class="box-tools">
                <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal pull-right"
                    data-href="{{ route('technicians.create') }}"
                    data-container=".technician_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
                <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-amber-500 tw-to-orange-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal pull-right"
                    style="margin-right:8px;"
                    data-href="{{ route('technicians.repair-commissions') }}"
                    data-container=".repair_commissions_modal">
                    <i class="fa fa-wrench"></i> @lang('lang_v1.repair_commissions')
                </a>
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="technicians_table">
                <thead>
                    <tr>
                        <th>@lang('user.name')</th>
                        <th>@lang('contact.mobile')</th>
                        <th>@lang('business.email')</th>
                        <th>@lang('lang_v1.locations_assigned')</th>
                        <th>@lang('sale.status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade technician_modal" tabindex="-1" role="dialog"></div>
    <div class="modal fade repair_commissions_modal" tabindex="-1" role="dialog"></div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var technicians_table = $('#technicians_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('technicians.index') }}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'phone', name: 'phone' },
                { data: 'email', name: 'email' },
                { data: 'locations_list', name: 'locations_list', orderable: false, searchable: false },
                { data: 'is_active', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $(document).on('click', '.delete_technician_button', function(e) {
            e.preventDefault();
            var href = $(this).data('href');
            swal({
                title: LANG.sure,
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then(willDelete => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: href,
                        dataType: 'json',
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                technicians_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        $(document).on('submit', 'form#technician_form', function(e) {
            e.preventDefault();
            var form = $(this);
            $.ajax({
                method: form.attr('method'),
                url: form.attr('action'),
                dataType: 'json',
                data: form.serialize(),
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        $('div.technician_modal').modal('hide');
                        technicians_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        $(document).on('shown.bs.modal', '.technician_modal', function() {
            $(this).find('.select2').select2();
        });

        // Buscador dentro del modal de comisiones por reparación
        $(document).on('keyup', '#repair_comm_search', function() {
            var q = $(this).val().toLowerCase();
            $('#repair_comm_table tbody tr.rc-row').each(function() {
                var t = $(this).data('search') || '';
                $(this).toggle(('' + t).indexOf(q) !== -1);
            });
        });

        // Guardar comisiones por reparación
        $(document).on('click', '.save_repair_commissions', function() {
            var btn = $(this);
            var data = {};
            $('.repair_commissions_modal .rc-input').each(function() {
                var pid = $(this).data('product_id');
                var val = $(this).val();
                data[pid] = (val === '' || val === null) ? 0 : (parseFloat(val) || 0);
            });
            btn.prop('disabled', true);
            $.ajax({
                method: 'POST',
                url: '{{ route('technicians.save-repair-commissions') }}',
                data: { commissions: JSON.stringify(data), _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function(result) {
                    btn.prop('disabled', false);
                    if (result.success) {
                        toastr.success(result.msg);
                        $('div.repair_commissions_modal').modal('hide');
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function() {
                    btn.prop('disabled', false);
                    toastr.error('Error');
                }
            });
        });
    });
</script>
@endsection
