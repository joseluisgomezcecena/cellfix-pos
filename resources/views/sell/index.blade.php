@extends('layouts.app')
@section('title', __('lang_v1.all_sales'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1  class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('sale.sells')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            @include('sell.partials.sell_list_filters')
            @if ($payment_types)
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('payment_method', __('lang_v1.payment_method') . ':') !!}
                        {!! Form::select('payment_method', $payment_types, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
            @endif

            @if (!empty($sources))
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('sell_list_filter_source', __('lang_v1.sources') . ':') !!}

                        {!! Form::select('sell_list_filter_source', $sources, null, [
                            'class' => 'form-control select2',
                            'style' => 'width:100%',
                            'placeholder' => __('lang_v1.all'),
                        ]) !!}
                    </div>
                </div>
            @endif
        @endcomponent
        @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.all_sales')])
            @can('direct_sell.access')
                @slot('tool')
                    <div class="box-tools">
                        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
                            href="{{ action([\App\Http\Controllers\SellController::class, 'create']) }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang('messages.add')
                        </a>
                    </div>
                @endslot
            @endcan
            @if (auth()->user()->can('direct_sell.view') ||
                    auth()->user()->can('view_own_sell_only') ||
                    auth()->user()->can('view_commission_agent_sell'))
                @php
                    $custom_labels = json_decode(session('business.custom_labels'), true);
                @endphp
                
                <!-- Loading overlay -->
                <div id="sell_table_loading" style="display: none; position: relative; padding: 20px; text-align: center;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p>@lang('messages.loading')...</p>
                </div>
                
                <table class="table table-bordered table-striped ajax_view" id="sell_table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('lang_v1.contact_no')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('lang_v1.payment_method')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('sale.total_paid')</th>
                            <th>@lang('lang_v1.sell_due')</th>
                            <th>@lang('lang_v1.sell_return_due')</th>
                            <th>@lang('lang_v1.shipping_status')</th>
                            <th>@lang('lang_v1.total_items')</th>
                            <th>@lang('lang_v1.types_of_service')</th>
                            <th>{{ $custom_labels['types_of_service']['custom_field_1'] ?? __('lang_v1.service_custom_field_1') }}
                            </th>
                            <th>{{ $custom_labels['sell']['custom_field_1'] ?? '' }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_2'] ?? '' }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_3'] ?? '' }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_4'] ?? '' }}</th>
                            <th>@lang('lang_v1.added_by')</th>
                            <th>@lang('sale.sell_note')</th>
                            <th>@lang('sale.staff_note')</th>
                            <th>@lang('sale.shipping_details')</th>
                            <th>@lang('restaurant.table')</th>
                            <th>@lang('restaurant.service_staff')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_payment_status_count"></td>
                            <td class="payment_method_count"></td>
                            <td class="footer_sale_total"></td>
                            <td class="footer_total_paid"></td>
                            <td class="footer_total_remaining"></td>
                            <td class="footer_total_sell_return_due"></td>
                            <td colspan="2"></td>
                            <td class="service_type_count"></td>
                            <td colspan="7"></td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        @endcomponent
    </section>
    <!-- /.content -->
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section">
        </section> 

@stop

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // OPTIMIZACIÓN: Establecer fecha por defecto al día actual
            var today = moment().startOf('day');
            var todayEnd = moment().endOf('day');
            
            // Establecer el valor inicial del campo de fecha
            $('#sell_list_filter_date_range').val(
                today.format(moment_date_format) + ' ~ ' + todayEnd.format(moment_date_format)
            );
            
            // Configurar el daterangepicker con la fecha de hoy como inicial
            $('#sell_list_filter_date_range').daterangepicker(
                $.extend(dateRangeSettings, {
                    startDate: today,
                    endDate: todayEnd,
                    // Agregar rangos predefinidos para mejor UX
                    ranges: {
                        'Hoy': [moment(), moment()],
                        'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
                        'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
                        'Este mes': [moment().startOf('month'), moment().endOf('month')],
                        'Mes pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'Este año': [moment().startOf('year'), moment().endOf('year')]
                    }
                }),
                function(start, end) {
                    $('#sell_list_filter_date_range').val(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    // Mostrar loading antes de recargar
                    $('#sell_table_loading').show();
                    $('#sell_table_wrapper').hide();
                    
                    sell_table.ajax.reload(function() {
                        // Ocultar loading cuando termine
                        $('#sell_table_loading').hide();
                        $('#sell_table_wrapper').show();
                    });
                }
            );
            
            // Manejar cuando se cancela la selección de fecha
            $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                // Si se cancela, volver a la fecha de hoy
                var today = moment().startOf('day');
                var todayEnd = moment().endOf('day');
                
                $('#sell_list_filter_date_range').val(
                    today.format(moment_date_format) + ' ~ ' + todayEnd.format(moment_date_format)
                );
                
                $('#sell_table_loading').show();
                $('#sell_table_wrapper').hide();
                
                sell_table.ajax.reload(function() {
                    $('#sell_table_loading').hide();
                    $('#sell_table_wrapper').show();
                });
            });

            // Inicializar DataTable con optimizaciones
            sell_table = $('#sell_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader: false,
                // OPTIMIZACIÓN: Ordenar por fecha descendente
                order: [[1, 'desc']],  // FIX: Cambiar aaSorting por order
                // OPTIMIZACIÓN: Configurar paginación
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                // OPTIMIZACIÓN: Deshabilitar el auto-width para mejorar rendimiento
                autoWidth: false,
                // OPTIMIZACIÓN: Defer render para mejorar carga inicial
                deferRender: true,
                "ajax": {
                    "url": "/sells",
                    "data": function(d) {
                        // OPTIMIZACIÓN: Siempre enviar fechas
                        if ($('#sell_list_filter_date_range').val()) {
                            var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                            var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        } else {
                            // Si por alguna razón no hay fecha, usar hoy como default
                            d.start_date = moment().format('YYYY-MM-DD');
                            d.end_date = moment().format('YYYY-MM-DD');
                        }
                        
                        d.is_direct_sale = 1;
                        d.location_id = $('#sell_list_filter_location_id').val();
                        d.customer_id = $('#sell_list_filter_customer_id').val();
                        d.payment_status = $('#sell_list_filter_payment_status').val();
                        d.created_by = $('#created_by').val();
                        d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                        d.service_staffs = $('#service_staffs').val();

                        if ($('#shipping_status').length) {
                            d.shipping_status = $('#shipping_status').val();
                        }

                        if ($('#sell_list_filter_source').length) {
                            d.source = $('#sell_list_filter_source').val();
                        }

                        if ($('#only_subscriptions').is(':checked')) {
                            d.only_subscriptions = 1;
                        }

                        if ($('#payment_method').length) {
                            d.payment_method = $('#payment_method').val();
                        }

                        d = __datatable_ajax_callback(d);
                    },
                    "error": function(xhr, error, thrown) {
                        $('#sell_table_loading').hide();
                        $('#sell_table_wrapper').show();
                        
                        console.error('DataTables error:', error);
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            alert('Error: ' + xhr.responseJSON.message);
                        } else {
                            alert('Error al cargar los datos. Por favor, intente con un rango de fechas más pequeño.');
                        }
                    }
                },
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                // OPTIMIZACIÓN: Configurar columnas con render functions más eficientes
                columns: [{
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'transaction_date',
                        name: 'transactions.transaction_date'  // Especificar tabla
                    },
                    {
                        data: 'invoice_no',
                        name: 'transactions.invoice_no'  // Especificar tabla
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name'
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile'
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name'
                    },
                    {
                        data: 'payment_status',
                        name: 'transactions.payment_status'  // Especificar tabla
                    },
                    {
                        data: 'payment_methods',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'final_total',
                        name: 'transactions.final_total'  // Especificar tabla
                    },
                    {
                        data: 'total_paid',
                        name: 'total_paid',  // Esta es calculada, no necesita prefijo
                        searchable: false
                    },
                    {
                        data: 'total_remaining',
                        name: 'total_remaining'
                    },
                    {
                        data: 'return_due',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'shipping_status',
                        name: 'transactions.shipping_status'  // FIX: Especificar tabla
                    },
                    {
                        data: 'total_items',
                        name: 'total_items',
                        searchable: false
                    },
                    {
                        data: 'types_of_service_name',
                        name: 'tos.name',
                        @if (empty($is_types_service_enabled))
                            visible: false
                        @endif
                    },
                    {
                        data: 'service_custom_field_1',
                        name: 'service_custom_field_1',
                        @if (empty($is_types_service_enabled))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_1',
                        name: 'transactions.custom_field_1',
                        @if (empty($custom_labels['sell']['custom_field_1']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_2',
                        name: 'transactions.custom_field_2',
                        @if (empty($custom_labels['sell']['custom_field_2']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_3',
                        name: 'transactions.custom_field_3',
                        @if (empty($custom_labels['sell']['custom_field_3']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'custom_field_4',
                        name: 'transactions.custom_field_4',
                        @if (empty($custom_labels['sell']['custom_field_4']))
                            visible: false
                        @endif
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name'
                    },
                    {
                        data: 'additional_notes',
                        name: 'additional_notes'
                    },
                    {
                        data: 'staff_note',
                        name: 'staff_note'
                    },
                    {
                        data: 'shipping_details',
                        name: 'shipping_details'
                    },
                    {
                        data: 'table_name',
                        name: 'tables.name',
                        @if (empty($is_tables_enabled))
                            visible: false
                        @endif
                    },
                    {
                        data: 'waiter',
                        name: 'ss.first_name',
                        @if (empty($is_service_staff_enabled))
                            visible: false
                        @endif
                    },
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#sell_table'));
                },
                // OPTIMIZACIÓN: No calcular totales de TODOS los registros
                // Solo mostrar totales de la página actual o usar totales del servidor
                "footerCallback": function(row, data, start, end, display) {
                    // Solo calcular si hay datos en la página actual
                    if (data && data.length > 0) {
                        var api = this.api();
                        var json = api.ajax.json();
                        
                        // Usar totales de la página actual (más rápido)
                        if (json && json.pageTotals) {
                            $('.footer_sale_total').html(__currency_trans_from_en(json.pageTotals.sale_total || 0));
                            $('.footer_total_paid').html(__currency_trans_from_en(json.pageTotals.total_paid || 0));
                            $('.footer_total_remaining').html(__currency_trans_from_en(json.pageTotals.total_remaining || 0));
                        }
                        
                        // Opcional: Si tienes totales globales del servidor
                        // if (json && json.globalTotals) {
                        //     $('.footer_sale_total').html(__currency_trans_from_en(json.globalTotals.total_sales));
                        //     $('.footer_total_paid').html(__currency_trans_from_en(json.globalTotals.total_paid));
                        //     $('.footer_total_remaining').html(__currency_trans_from_en(json.globalTotals.total_remaining));
                        // }
                    }
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(6)').attr('class', 'clickable_td');
                },
                // OPTIMIZACIÓN: Agregar estado de procesamiento
                "language": {
                    "processing": '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Cargando...</span>'
                }
            });

            // Manejar cambios en filtros con debounce para evitar múltiples llamadas
            var filterTimeout;
            $(document).on('change',
                '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #shipping_status, #sell_list_filter_source, #payment_method',
                function() {
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(function() {
                        sell_table.ajax.reload();
                    }, 500); // Esperar 500ms después del último cambio
                });

            $('#only_subscriptions').on('ifChanged', function(event) {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(function() {
                    sell_table.ajax.reload();
                }, 500);
            });
            
            // Mensaje informativo para el usuario
            if ($('#sell_table').length) {
                toastr.info('Mostrando ventas del día de hoy. Use el selector de fechas para ver otros períodos.', 'Información', {
                    "timeOut": "5000",
                    "positionClass": "toast-top-right"
                });
            }
        });
    </script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection