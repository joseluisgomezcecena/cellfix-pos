@extends('layouts.app')
@section('title', __('inventorymultilocation::lang.view_inventory'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('inventorymultilocation::lang.view_inventory') }}
        <small>{{ __('inventorymultilocation::lang.inventory_multi_location') }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ url('/home') }}"><i class="fa fa-dashboard"></i> {{ __('inventorymultilocation::lang.dashboard') }}</a></li>
        <li><a href="{{ route('inventory-multi.dashboard') }}">{{ __('inventorymultilocation::lang.dashboard') }}</a></li>
        <li class="active">{{ __('inventorymultilocation::lang.view_inventory') }}</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])

        <!-- Quick Tabs Navigation -->
        <div class="box-header with-border">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="box-title">
                        <i class="fa fa-list"></i> {{ __('inventorymultilocation::lang.view_inventory') }}
                    </h3>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group" role="group">
                        <a href="{{ route('inventory-multi.dashboard') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-dashboard"></i> {{ __('inventorymultilocation::lang.dashboard') }}
                        </a>
                        <a href="{{ route('inventory-multi.transfers.index') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-exchange"></i> {{ __('inventorymultilocation::lang.stock_transfers') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Selector and Filters -->
        <div class="box-header">
            <form id="inventory-filters" method="GET">
                <div class="row">
                    <!-- Location Selector -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('inventorymultilocation::lang.select_location') }}</label>
                            <select name="location_id" id="location_id" class="form-control select2" style="width: 100%;">
                                <option value="all" {{ $location_id == 'all' ? 'selected' : '' }}>
                                    {{ __('inventorymultilocation::lang.all_locations') }}
                                </option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ $location_id == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('inventorymultilocation::lang.search_products') }}</label>
                            <input type="text" name="search" class="form-control" placeholder="{{ __('inventorymultilocation::lang.search_products') }}" value="{{ request()->get('search') }}">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('inventorymultilocation::lang.filter_by_category') }}</label>
                            <select name="category_id" class="form-control select2" style="width: 100%;">
                                <option value="">{{ __('messages.all') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request()->get('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Brand Filter -->
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('inventorymultilocation::lang.filter_by_brand') }}</label>
                            <select name="brand_id" class="form-control select2" style="width: 100%;">
                                <option value="">{{ __('messages.all') }}</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" {{ request()->get('brand_id') == $brand->id ? 'selected' : '' }}>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Stock Status Filter -->
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('inventorymultilocation::lang.filter_by_stock_status') }}</label>
                            <select name="stock_status" class="form-control select2" style="width: 100%;">
                                <option value="">{{ __('messages.all') }}</option>
                                <option value="in_stock" {{ request()->get('stock_status') == 'in_stock' ? 'selected' : '' }}>
                                    {{ __('inventorymultilocation::lang.in_stock') }}
                                </option>
                                <option value="low_stock" {{ request()->get('stock_status') == 'low_stock' ? 'selected' : '' }}>
                                    {{ __('inventorymultilocation::lang.low_stock') }}
                                </option>
                                <option value="out_of_stock" {{ request()->get('stock_status') == 'out_of_stock' ? 'selected' : '' }}>
                                    {{ __('inventorymultilocation::lang.out_of_stock') }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i> {{ __('messages.filter') }}
                        </button>
                        <a href="{{ route('inventory-multi.inventory') }}" class="btn btn-default">
                            <i class="fa fa-refresh"></i> {{ __('messages.reset') }}
                        </a>
                        <div class="btn-group pull-right">
                            <button type="button" class="btn btn-warning" id="btn-bulk-transfer">
                                <i class="fas fa-exchange-alt"></i> {{ __('inventorymultilocation::lang.bulk_transfer') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Inventory Table -->
        <div class="box-body" id="inventory-table-container">
            @include('inventorymultilocation::partials.inventory_table', ['inventory' => $inventory])
        </div>

    @endcomponent
</section>

<!-- Bulk Transfer Modal -->
<div class="modal fade" id="bulk-transfer-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('inventorymultilocation::lang.bulk_transfer') }}</h4>
            </div>
            <div class="modal-body">
                <form id="bulk-transfer-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('inventorymultilocation::lang.transfer_to') }}</label>
                                <select name="to_location_id" class="form-control select2" required>
                                    <option value="">{{ __('messages.please_select') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('inventorymultilocation::lang.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="bulk-transfer-table">
                            <thead>
                                <tr>
                                    <th>{{ __('sale.product') }}</th>
                                    <th>SKU</th>
                                    <th>{{ __('inventorymultilocation::lang.current_stock') }}</th>
                                    <th>{{ __('inventorymultilocation::lang.quantity_to_transfer') }}</th>
                                    <th style="width:60px;" class="text-center">Quitar</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-bulk-transfer">
                    {{ __('inventorymultilocation::lang.transfer') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Transfer Modal -->
<div class="modal fade" id="quick-transfer-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('inventorymultilocation::lang.transfer_stock') }}</h4>
            </div>
            <form id="quick-transfer-form">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <p><strong id="transfer-product-name"></strong></p>
                            <p>{{ __('inventorymultilocation::lang.current_stock') }}: <span id="transfer-current-stock"></span></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('inventorymultilocation::lang.transfer_to') }}*</label>
                                <select name="to_location_id" class="form-control" required>
                                    <option value="">{{ __('messages.please_select') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('inventorymultilocation::lang.quantity_to_transfer') }}*</label>
                                <input type="number" name="quantity" class="form-control" min="0.01" step="0.01" required>
                                <input type="hidden" name="product_id">
                                <input type="hidden" name="variation_id">
                                <input type="hidden" name="from_location_id">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>{{ __('inventorymultilocation::lang.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        {{ __('inventorymultilocation::lang.transfer') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
// Function to initialize stock status indicators
function initStockIndicators() {
    $('.stock-indicator').each(function() {
        const stock = parseFloat($(this).data('stock')) || 0;
        const alertQty = parseFloat($(this).data('alert-qty')) || 0;

        // Clear existing classes and text
        $(this).removeClass('label label-danger label-warning label-success').text('');

        if (stock <= 0) {
            $(this).addClass('label label-danger').text('{{ __('inventorymultilocation::lang.out_of_stock') }}');
        } else if (stock <= alertQty) {
            $(this).addClass('label label-warning').text('{{ __('inventorymultilocation::lang.low_stock') }}');
        } else {
            $(this).addClass('label label-success').text('{{ __('inventorymultilocation::lang.in_stock') }}');
        }
    });
}

// Function to initialize currency display
function initCurrencyDisplay() {
    if (typeof __currency_convert_recursively === 'function') {
        __currency_convert_recursively($('#inventory-table-container'));
    }
}

// Function to initialize all table elements after load/AJAX
function initInventoryTable() {
    initStockIndicators();
    initCurrencyDisplay();
}

$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2();

    // Initialize table on page load
    initInventoryTable();

    // Auto-submit form on filter change
    // Listen to change event which Select2 properly triggers
    $('#location_id').on('change', function(e) {
        console.log('Location changed to:', $(this).val());
        $('#inventory-filters').submit();
    });

    $('select[name="category_id"]').on('change', function(e) {
        console.log('Category changed to:', $(this).val());
        $('#inventory-filters').submit();
    });

    $('select[name="brand_id"]').on('change', function(e) {
        console.log('Brand changed to:', $(this).val());
        $('#inventory-filters').submit();
    });

    $('select[name="stock_status"]').on('change', function(e) {
        console.log('Stock status changed to:', $(this).val());
        $('#inventory-filters').submit();
    });

    // Search with delay
    let searchTimeout;
    $('input[name="search"]').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $('#inventory-filters').submit();
        }, 500);
    });

    // AJAX form submission for filters
    $('#inventory-filters').on('submit', function(e) {
        e.preventDefault();

        const url = $(this).attr('action') || '{{ route('inventory-multi.inventory') }}';

        // Serialize form data and remove empty values for cleaner URLs
        let formData = $(this).serializeArray();
        let cleanData = {};

        $.each(formData, function(i, field) {
            if (field.value !== '' && field.value !== null) {
                cleanData[field.name] = field.value;
            }
        });

        const dataString = $.param(cleanData);

        console.log('Submitting filters:', cleanData);
        console.log('URL:', url + '?' + dataString);

        // Show loading indicator
        $('#inventory-table-container').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><br>{{ __('messages.loading') }}...</div>');

        $.ajax({
            url: url,
            type: 'GET',
            data: cleanData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                $('#inventory-table-container').html(response);

                // Re-initialize table elements (stock indicators, currency display)
                initInventoryTable();

                // Al reemplazar el partial via AJAX se pierden los checks visuales,
                // hay que volver a marcarlos desde selectedItems (que sobrevive porque
                // vive en la variable JS del outer scope).
                restoreCheckboxesFromSelection();

                // Update browser URL without reloading
                if (history.pushState) {
                    const newUrl = url + (dataString ? '?' + dataString : '');
                    history.pushState(null, '', newUrl);
                }
                console.log('Filters applied successfully');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                toastr.error('{{ __('messages.something_went_wrong') }}');
                // Reload page as fallback
                window.location.href = url + (dataString ? '?' + dataString : '');
            }
        });
    });

    // Bulk Transfer functionality
    // Persistimos la selección en sessionStorage para que sobreviva al cambio de
    // página / recarga del filtro. Antes se guardaba solo en memoria, entonces al
    // navegar a ?page=2 se perdía todo lo seleccionado en la página 1.
    const SELECTED_ITEMS_KEY = 'inventory_multi_selected_items';
    let selectedItems = loadSelectedItems();

    function loadSelectedItems() {
        try {
            const raw = sessionStorage.getItem(SELECTED_ITEMS_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function saveSelectedItems() {
        try {
            sessionStorage.setItem(SELECTED_ITEMS_KEY, JSON.stringify(selectedItems));
        } catch (e) {
            // Ignorar errores de quota; peor caso vuelve al comportamiento anterior.
        }
    }

    function clearSelectedItems() {
        selectedItems = [];
        try { sessionStorage.removeItem(SELECTED_ITEMS_KEY); } catch (e) {}
        updateBulkTransferButton();
    }

    // Restaurar visualmente: marca los checkboxes de la página actual cuyo
    // (product_id, variation_id, location_id) está en la lista guardada, y
    // también refresca el header "Select all" según lo visible.
    function restoreCheckboxesFromSelection() {
        $('.inventory-checkbox').each(function () {
            const row = $(this).closest('tr');
            const pid = String(row.data('product-id'));
            const vid = String(row.data('variation-id'));
            const lid = String(row.data('location-id'));
            const isSelected = selectedItems.some(function (item) {
                return String(item.product_id) === pid
                    && String(item.variation_id) === vid
                    && String(item.location_id) === lid;
            });
            $(this).prop('checked', isSelected);
        });
        const allVisible = $('.inventory-checkbox').length;
        const checkedVisible = $('.inventory-checkbox:checked').length;
        $('#select-all-items').prop('checked', allVisible > 0 && allVisible === checkedVisible);
        updateBulkTransferButton();
    }

    // Ejecutar al cargar la página inicial
    restoreCheckboxesFromSelection();

    $(document).on('change', '.inventory-checkbox', function() {
        const row = $(this).closest('tr');
        // El stock viene formateado como "1,045.00" (comas de miles). parseFloat
        // se corta en la primera coma → devuelve 1 en vez de 1045, y el modal de
        // transferencia masiva mostraba "Stock actual: 1" causando confusión.
        // Removemos comas antes de parsear.
        const stockText = (row.find('.current-stock').text() || '0').replace(/,/g, '');
        const productData = {
            product_id: row.data('product-id'),
            variation_id: row.data('variation-id'),
            product_name: (row.find('.product-name').text() || '').trim(),
            variation_name: (row.find('.variation-name').text() || '').trim(),
            sku: (row.find('.product-sku').text() || '').trim(),
            current_stock: parseFloat(stockText) || 0,
            location_id: row.data('location-id')
        };

        if ($(this).is(':checked')) {
            // Dedup por (product_id, variation_id, location_id). Antes al re-buscar
            // el mismo producto y marcar el checkbox otra vez se duplicaba la
            // entrada en el modal.
            const dup = selectedItems.some(function (item) {
                return String(item.product_id) === String(productData.product_id)
                    && String(item.variation_id) === String(productData.variation_id)
                    && String(item.location_id) === String(productData.location_id);
            });
            if (!dup) {
                selectedItems.push(productData);
            } else if (typeof toastr !== 'undefined') {
                toastr.info('Este producto ya está en la selección');
            }
        } else {
            selectedItems = selectedItems.filter(item =>
                !(String(item.product_id) === String(productData.product_id) &&
                  String(item.variation_id) === String(productData.variation_id) &&
                  String(item.location_id) === String(productData.location_id))
            );
        }

        saveSelectedItems();
        updateBulkTransferButton();
    });

    function updateBulkTransferButton() {
        const btn = $('#btn-bulk-transfer');
        if (selectedItems.length > 0) {
            btn.removeClass('btn-warning').addClass('btn-success')
               .html('<i class="fas fa-exchange-alt"></i> {{ __('inventorymultilocation::lang.bulk_transfer') }} (' + selectedItems.length + ')');
        } else {
            btn.removeClass('btn-success').addClass('btn-warning')
               .html('<i class="fas fa-exchange-alt"></i> {{ __('inventorymultilocation::lang.bulk_transfer') }}');
        }
    }

    // Helper para escapar HTML en valores que vienen de datos (nombres de producto,
    // SKUs, etc.) y evitar XSS accidental si algún nombre tiene < o >.
    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Render de la tabla del modal a partir del estado actual de selectedItems.
    // Se llama al abrir el modal y cada vez que el usuario quita algún renglón
    // con el botón basura, para que los índices y los inputs queden consistentes.
    function renderBulkTransferTable() {
        const tbody = $('#bulk-transfer-table tbody');
        tbody.empty();
        selectedItems.forEach(function (item, index) {
            const name = escapeHtml(item.product_name)
                + (item.variation_name ? ' - ' + escapeHtml(item.variation_name) : '');
            const sku = escapeHtml(item.sku || '');
            const row = `
                <tr data-item-index="${index}">
                    <td>
                        ${name}
                        <input type="hidden" name="items[${index}][product_id]" value="${escapeHtml(item.product_id)}">
                        <input type="hidden" name="items[${index}][variation_id]" value="${escapeHtml(item.variation_id)}">
                        <input type="hidden" name="items[${index}][from_location_id]" value="${escapeHtml(item.location_id)}">
                    </td>
                    <td><code>${sku || 'N/A'}</code></td>
                    <td>${item.current_stock}</td>
                    <td>
                        <input type="number" name="items[${index}][quantity]" class="form-control bulk-qty"
                               min="0.01" max="${item.current_stock}" step="0.01"
                               value="${item.quantity != null ? escapeHtml(String(item.quantity)) : ''}" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-bulk-item"
                                data-item-index="${index}" title="Quitar de la selección">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    $('#btn-bulk-transfer').on('click', function() {
        if (selectedItems.length === 0) {
            toastr.warning('{{ __('messages.select_items') }}');
            return;
        }
        renderBulkTransferTable();
        $('#bulk-transfer-modal').modal('show');
    });

    // Antes de cualquier re-render o splice, guarda lo que la cajera ya tecleó en
    // el campo Cantidad en su item correspondiente. Sin esto, al quitar una fila
    // se limpiaba la cantidad de TODAS las demás filas (bug reportado).
    function snapshotBulkQuantities() {
        $('#bulk-transfer-table tbody tr').each(function () {
            const idx = parseInt($(this).data('item-index'), 10);
            if (!isNaN(idx) && selectedItems[idx]) {
                const v = $(this).find('.bulk-qty').val();
                selectedItems[idx].quantity = (v === '' || v == null) ? null : v;
            }
        });
    }
    // Si la usuaria edita el campo, también actualizamos el item en memoria.
    $(document).on('input', '#bulk-transfer-table .bulk-qty', function () {
        const idx = parseInt($(this).closest('tr').data('item-index'), 10);
        if (!isNaN(idx) && selectedItems[idx]) {
            const v = $(this).val();
            selectedItems[idx].quantity = (v === '' || v == null) ? null : v;
        }
    });

    // Quitar un renglón desde el modal (icono basura). Elimina del selectedItems,
    // guarda en sessionStorage, desmarca el checkbox correspondiente si está visible
    // en la página, y re-renderiza la tabla para que los índices se re-numeren.
    $(document).on('click', '.remove-bulk-item', function () {
        const index = parseInt($(this).data('item-index'), 10);
        if (isNaN(index) || !selectedItems[index]) return;
        snapshotBulkQuantities();
        const item = selectedItems[index];
        selectedItems.splice(index, 1);
        saveSelectedItems();

        // Desmarcar el checkbox del producto si está en la página actual
        $('.inventory-checkbox').each(function () {
            const row = $(this).closest('tr');
            if (String(row.data('product-id')) === String(item.product_id)
                && String(row.data('variation-id')) === String(item.variation_id)
                && String(row.data('location-id')) === String(item.location_id)) {
                $(this).prop('checked', false);
                $('#select-all-items').prop('checked', false);
            }
        });

        updateBulkTransferButton();

        if (selectedItems.length === 0) {
            $('#bulk-transfer-modal').modal('hide');
        } else {
            renderBulkTransferTable();
        }
    });

    $('#btn-confirm-bulk-transfer').on('click', function() {
        const formData = $('#bulk-transfer-form').serialize();

        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __('inventorymultilocation::lang.processing') }}');

        $.post('{{ route('inventory-multi.transfers.store') }}', formData + '&bulk_transfer=1')
            .done(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#bulk-transfer-modal').modal('hide');
                    // Limpiar selección persistida (BD ya reflejó el movimiento).
                    clearSelectedItems();
                    $('#inventory-filters').submit(); // Refresh table
                } else {
                    toastr.error(response.message);
                }
            })
            .fail(function() {
                toastr.error('{{ __('messages.something_went_wrong') }}');
            })
            .always(function() {
                $('#btn-confirm-bulk-transfer').prop('disabled', false)
                    .html('{{ __('inventorymultilocation::lang.transfer') }}');
            });
    });

    // Quick transfer - click handler
    $(document).on('click', '.quick-transfer', function(e) {
        e.preventDefault();

        const productName = $(this).data('product-name');
        const currentStock = $(this).data('current-stock');
        const productId = $(this).data('product-id');
        const variationId = $(this).data('variation-id');
        const locationId = $(this).data('location-id');

        $('#transfer-product-name').text(productName);
        $('#transfer-current-stock').text(currentStock);

        const form = $('#quick-transfer-form');
        form.find('input[name="product_id"]').val(productId);
        form.find('input[name="variation_id"]').val(variationId);
        form.find('input[name="from_location_id"]').val(locationId);
        form.find('input[name="quantity"]').attr('max', currentStock);

        $('#quick-transfer-modal').modal('show');
    });

    // Quick transfer form submission
    $('#quick-transfer-form').on('submit', function(e) {
        e.preventDefault();

        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> {{ __('inventorymultilocation::lang.processing') }}');

        $.post('{{ route('inventory-multi.transfers.store') }}', $(this).serialize())
            .done(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#quick-transfer-modal').modal('hide');
                    $('#inventory-filters').submit(); // Refresh table
                } else {
                    toastr.error(response.message);
                }
            })
            .fail(function() {
                toastr.error('{{ __('messages.something_went_wrong') }}');
            })
            .always(function() {
                submitBtn.prop('disabled', false).html('{{ __('inventorymultilocation::lang.transfer') }}');
            });
    });
});
</script>
@endsection

@section('css')
<style>
/* Fix icon alignment in navigation buttons */
.btn-group .btn i {
    margin-right: 6px;
    vertical-align: middle;
    line-height: 1.4;
    display: inline-block;
    font-size: 14px !important;
    min-width: 14px;
    text-align: center;
}

.btn-group .btn {
    display: inline-flex;
    align-items: center;
    justify-content: flex-start;
    padding: 6px 12px;
    line-height: 1.4;
    vertical-align: middle;
}

.btn-group .btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    line-height: 1.5;
}

.btn-group .btn-sm i {
    font-size: 13px !important;
    margin-right: 6px;
    min-width: 13px;
    line-height: 1.5;
}

/* Ensure proper vertical centering for all button content */
.box-header .btn-group {
    vertical-align: middle;
}

.box-header .btn-group .btn {
    vertical-align: middle;
    display: inline-flex;
    align-items: center;
}

.box-header .btn-group .btn i {
    vertical-align: middle;
    line-height: inherit;
}

/* Fix any potential CSS conflicts with AdminLTE */
.btn-group .btn i.fa {
    font-family: 'FontAwesome' !important;
    font-style: normal !important;
    font-weight: normal !important;
    text-decoration: none !important;
    vertical-align: middle;
}

/* Fix Select2 dark background - force white background */
.select2-container--default .select2-selection--single {
    background-color: #fff !important;
    border: 1px solid #d2d6de !important;
    border-radius: 3px !important;
    color: #555 !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #555 !important;
    line-height: 34px !important;
    padding-left: 12px !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 34px !important;
}

.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #3c8dbc !important;
    background-color: #fff !important;
}

.select2-container--default .select2-selection--single:hover {
    background-color: #fff !important;
}

/* Ensure select2 dropdown also has proper styling */
.select2-dropdown {
    background-color: #fff !important;
    border: 1px solid #d2d6de !important;
    border-radius: 3px !important;
}

.select2-container--default .select2-results__option {
    color: #555 !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #3c8dbc !important;
    color: #fff !important;
}

/* Fix export button icon spacing */
#btn-export i.fa {
    margin-right: 6px !important;
}
</style>
@endsection