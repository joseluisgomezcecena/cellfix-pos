{{-- Modal del origen del cliente —
     Monkey-patch de la función global `pos_print(receipt)` del POS.
     Después de cada venta cobrada, antes de imprimir el ticket, llama a
     /sale-sources/should-prompt/{tx_id}. Si el server dice show=true
     (switch ON + cliente nuevo + hay sources activos) pinta este modal.
     Al elegir una opción o cerrar sin elegir → ejecuta el pos_print original. --}}

<div class="modal fade" id="sale_source_modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#f0ad4e; color:#fff;">
                <h4 class="modal-title">
                    <i class="fa fa-question-circle"></i> ¿Cómo se enteró el cliente de nosotros?
                </h4>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="margin-bottom:15px;">
                    Es la primera compra de este cliente. Toca la opción que mencionó,
                    o cierra la ventana si no te lo dijo.
                </p>
                <div id="sale_source_options" class="row"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="sale_source_skip">
                    <i class="fa fa-times"></i> Omitir e imprimir ticket
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    if (typeof window.pos_print !== 'function') return;

    var _originalPosPrint = window.pos_print;
    var _currentReceipt = null;
    var _currentTxId = null;

    window.pos_print = function (receipt) {
        var tx_id = (receipt && receipt.transaction_id) ? receipt.transaction_id : null;
        if (!tx_id) {
            return _originalPosPrint(receipt);
        }
        $.getJSON("{{ url('/sale-sources/should-prompt') }}" + '/' + tx_id)
            .done(function (r) {
                if (!r || !r.show) {
                    _originalPosPrint(receipt);
                    return;
                }
                _currentReceipt = receipt;
                _currentTxId = tx_id;
                renderOptions(r.sources || []);
                $('#sale_source_modal').modal('show');
            })
            .fail(function () {
                // Si falla el chequeo, mejor imprimir el ticket que bloquear al cajero.
                _originalPosPrint(receipt);
            });
    };

    function renderOptions(sources) {
        var $wrap = $('#sale_source_options').empty();
        if (sources.length === 0) {
            _originalPosPrint(_currentReceipt);
            $('#sale_source_modal').modal('hide');
            return;
        }
        sources.forEach(function (s) {
            var $btn = $('<button type="button" class="btn btn-primary btn-lg sale-source-opt">')
                .css({'margin-bottom': '10px', 'width': '100%', 'padding': '14px', 'font-size': '16px'})
                .data('source-id', s.id)
                .text(s.name);
            $wrap.append($('<div class="col-sm-6">').append($btn));
        });
    }

    $(document).on('click', '.sale-source-opt', function () {
        var $btn = $(this);
        var sourceId = $btn.data('source-id');
        $('.sale-source-opt').prop('disabled', true);
        $.ajax({
            url: "{{ url('/sale-sources/attach') }}" + '/' + _currentTxId,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                source_id: sourceId
            },
            complete: function () {
                $('#sale_source_modal').modal('hide');
                // Imprimir ticket después de cerrar el modal.
                setTimeout(function () {
                    _originalPosPrint(_currentReceipt);
                }, 150);
            }
        });
    });

    $('#sale_source_skip').on('click', function () {
        $('#sale_source_modal').modal('hide');
        setTimeout(function () {
            _originalPosPrint(_currentReceipt);
        }, 150);
    });
})();
</script>
