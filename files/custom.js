
$(document).ready(function() {
    $('#bugTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copyHtml5',
            'csvHtml5',
            'excelHtml5',
            'pdfHtml5',
            'print'
        ],
        scrollX: true,
        pageLength: 40
    });
});
