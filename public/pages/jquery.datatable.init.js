/**
 * Theme: Crovex - Responsive Bootstrap 4 Admin Dashboard
 * Author: Mannatthemes
 * Datatables Js
 */


$(document).ready(function () {
    $('#datatable').DataTable();

    $(document).ready(function () {
        $('#datatable2').DataTable();
    });

//   //Buttons examples
// var table = $('.sifu-datatable').DataTable({
//     lengthChange: true,  // Enables the length change dropdown
//     searching: false,    // Hides the search bar
//                     buttons: ['copy', 'excel', 'pdf', 'csv', 'colvis'],
                order: [
                    [0, 'desc']
                ]
// });

// // Append buttons to the right
// table.buttons().container()
//     .appendTo('.dataTables_wrapper .col-md-6:eq(1)');

// Ensure the length change is on the left
    $('.dataTables_wrapper .col-md-6:eq(0)').addClass('text-left');
    $('.dataTables_wrapper .col-md-6:eq(1)').addClass('text-right');


});

/* Formatting function for row details - modify as you need */
function format(d) {
    // `d` is the original data object for the row
    return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
        '<tr>' +
        '<td>Full name:</td>' +
        '<td>' + d.name + '</td>' +
        '</tr>' +
        '<tr>' +
        '<td>Extension number:</td>' +
        '<td>' + d.extn + '</td>' +
        '</tr>' +
        '<tr>' +
        '<td>Extra info:</td>' +
        '<td>And any further details here (images etc)...</td>' +
        '</tr>' +
        '</table>';
}

$(document).ready(function () {
    var table = $('#child_rows').DataTable({
        // "ajax": "../../plugins/datatables/objects.txt",
        "data": testdata.data,
        select: "single",
        "columns": [
            {
                "className": 'details-control',
                "orderable": false,
                "data": null,
                "defaultContent": ''
            },
            {"data": "name"},
            {"data": "position"},
            {"data": "office"},
            {"data": "salary"}
        ],
        "order": [[1, 'asc']]
    });

    // Add event listener for opening and closing details
    $('#child_rows tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child(format(row.data())).show();
            tr.addClass('shown');
        }
    });
});

var testdata = {
    "data": [
        {
            "name": "Tiger Nixon",
            "position": "System Architect",
            "salary": "$320,800",
            "start_date": "2011/04/25",
            "office": "Edinburgh",
            "extn": "5421"
        },
        {
            "name": "Garrett Winters",
            "position": "Accountant",
            "salary": "$170,750",
            "start_date": "2011/07/25",
            "office": "Tokyo",
            "extn": "8422"
        },
        {
            "name": "Ashton Cox",
            "position": "Junior Technical Author",
            "salary": "$86,000",
            "start_date": "2009/01/12",
            "office": "San Francisco",
            "extn": "1562"
        },]
}
