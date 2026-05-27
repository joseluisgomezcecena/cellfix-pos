@extends('layouts.app')
@section('title', __('lang_v1.card_terminals'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.card_terminals')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.manage_card_terminals')
        </small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_card_terminals')])
        @slot('tool')
            <div class="box-tools">
                <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal pull-right"
                    data-href="{{ route('card-terminals.create') }}"
                    data-container=".terminal_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="card_terminals_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.terminal_name')</th>
                        <th>@lang('lang_v1.bank')</th>
                        <th>@lang('lang_v1.account_number')</th>
                        <th>@lang('sale.status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade terminal_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        var card_terminals_table = $('#card_terminals_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('card-terminals.index') }}',
            columns: [
                { data: 'name', name: 'name' },
                { data: 'bank', name: 'bank' },
                { data: 'account_number', name: 'account_number' },
                { data: 'is_active', name: 'is_active' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // Delete handler
        $(document).on('click', '.delete_card_terminal_button', function(e) {
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
                                card_terminals_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        // Create / edit submit (delegated)
        $(document).on('submit', 'form#card_terminal_form', function(e) {
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
                        $('div.terminal_modal').modal('hide');
                        card_terminals_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });
    });
</script>
@endsection
