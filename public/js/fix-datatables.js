$(document).ready(function () {
    console.log("✅ fix-datatables.js cargado correctamente");
    $.extend(true, $.fn.dataTable.defaults, {
        pageLength: 25,
        processing: true,
        serverSide: true,
        searching: true,
        ordering: true,
        lengthMenu: [25, 50, 100],
        language: {
           // url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
           url: "/vendor/datatables/i18n/Spanish.json"
        }
    });
});
 