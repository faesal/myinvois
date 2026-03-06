@extends('layouts.developerLayout')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<style>
    /* Styling for the Export Dropdown to match Submission page */
    .dt-buttons-container { flex: 1; display: flex; gap: 0.5rem; }
    div.dt-buttons .dt-button { padding: 0; border: none; background: none; }
    
    .dataTables_filter { float: right !important; margin-bottom: 15px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 6px 12px; margin-left: 2px; border: 1px solid #dee2e6; background-color: white;
        color: #0d6efd !important; border-radius: 0.25rem; font-weight: 500;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background-color: #0d6efd !important; color: white !important;
    }

    .swal2-icon { transform: scale(0.6) !important; margin-top: 15px !important; margin-bottom: 0px !important; }
    
    /* Center the DataTables Loading Spinner */
    div.dataTables_wrapper div.dataTables_processing {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        transform: translate(-50%, -50%);
        text-align: center;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(2px);
        padding: 40px 0;
        z-index: 10;
        margin: 0;
        border: none;
        box-shadow: none;
    }
</style>

<div class="container-fluid py-4">
    <h2 class="mb-4">✅ Select Consolidated Items to Convert into Invoice</h2>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-muted">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $start ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small text-muted">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $end ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">LHDN Account</label>
                        <select name="connection" class="form-select" id="selected_connection" required>
                            <option value="">-- Select Connection --</option>
                            @foreach($availableConnections as $conn)
                                <option value="{{ $conn->connection_integrate }}" 
                                    {{ (isset($selectedConnection) && $selectedConnection == $conn->connection_integrate) ? 'selected' : '' }}>
                                    {{ strtoupper($conn->registration_name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-secondary w-100">🔍 Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="datatable-items" class="table table-bordered table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">
                                <input type="checkbox" id="checkAll" class="form-check-input">
                            </th>
                            <th>Invoice No.</th> 
                            <th>Sale ID</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Tax (RM)</th>
                            <th>Total (RM)</th>
                            <th>Connection</th>
                            <th>Date</th>
                            <th class="text-center">Action</th> 
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                
                <div class="d-flex justify-content-between align-items-end mt-4 pt-3 border-top">
                    <div id="action-buttons" class="d-flex gap-2" style="display: none !important;">
                        <button type="button" class="btn btn-primary px-4" id="openConfirmModal">🚀 Save to Invoice</button>
                        <button type="button" class="btn btn-outline-danger px-4" id="bulkDeleteBtn">🗑️ Bulk Delete Selected</button>
                    </div>

                    <div id="summary-totals" class="card bg-light border-0 shadow-sm" style="min-width: 250px; display: none;">
                        <div class="card-body p-3 text-end">
                            <h6 class="text-muted mb-2 fw-bold text-uppercase" style="font-size: 0.8rem;">Overall Totals (All Pages)</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold">Total Tax:</span>
                                <span class="fw-bold text-danger">RM <span id="summary-total-tax">0.00</span></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">Total Amount:</span>
                                <span class="fw-bold text-success fs-5">RM <span id="summary-total-amount">0.00</span></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: 14px;">
      <div class="modal-header border-0 d-block text-center mt-3">
        <h5 class="modal-title fw-bold">Confirm Submission</h5>
        <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center px-4">
        <p>You are about to submit <strong class="text-primary"><span id="selectedCount">0</span></strong> items.</p>
        <p class="fs-4 fw-bold text-success">RM <span id="totalAmount">0.00</span></p>
        <p class="text-muted">Are you sure you want to proceed?</p>
      </div>
      <div class="modal-footer border-0 justify-content-center pb-4 gap-2">
        <button type="button" id="confirmSubmit" class="btn btn-success px-4 py-2 fw-bold" style="border-radius:8px;">Yes, Submit</button>
        <button type="button" class="btn btn-secondary px-4 py-2 fw-bold" data-bs-dismiss="modal" style="border-radius:8px;">Cancel</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function () {

    // Helper Function: Safely grab checked IDs
    function getSelectedItems() {
        let ids = [];
        $('#datatable-items tbody input.select-item:checked').each(function() {
            ids.push($(this).val());
        });
        return ids;
    }

    // 1. Setup Export Logic
    let exportRoute = "{{ route('developer.consolidate.export_search') }}";

    function executeBackendExport(idsArray) {
        let url = new URL(exportRoute);
        url.searchParams.append('ids', idsArray.join(','));
        window.location.href = url.toString();
    }

    let dropdownButtons = [
        {
            text: '<i class="fa-solid fa-square-check me-2 text-primary"></i> Export Selected',
            className: 'dropdown-item py-2 fw-semibold',
            action: function (e, dt, node, config) {
                let selected = getSelectedItems();
                if (selected.length === 0) return Swal.fire("Oops", "Please select at least one item.", "warning");
                executeBackendExport(selected);
            }
        },
        {
            text: '<span class="text-muted small fw-bold mt-2 d-block px-3 border-top pt-2">TABLE TOOLS</span>',
            className: 'dropdown-item disabled',
            action: function() { return false; }
        },
        {
            extend: 'csvHtml5',
            text: '<i class="fa-solid fa-file-csv me-2 text-info"></i> Current View (CSV)',
            className: 'dropdown-item py-2 fw-semibold',
            exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8], modifier: { search: 'applied' } }
        },
        {
            extend: 'excelHtml5',
            text: '<i class="fa-solid fa-file-excel me-2 text-success"></i> Current View (Excel)',
            className: 'dropdown-item py-2 fw-semibold',
            exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8], modifier: { search: 'applied' } }
        },
        {
            extend: 'print',
            text: '<i class="fa-solid fa-print me-2 text-secondary"></i> Print Table',
            className: 'dropdown-item py-2 fw-semibold',
            exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8], modifier: { search: 'applied' } }
        }
    ];

    // 2. Initialize DataTable
    var table = $('#datatable-items').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ url('developer/consolidate/data') }}", 
            type: "GET",
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.connection = $('#selected_connection').val();
            }
        },
        columns: [
            { 
                data: 'id_invoice_item', 
                name: 'checkbox', 
                orderable: false, 
                searchable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    return '<input type="checkbox" name="selected_items[]" value="' + row.id_invoice_item + '" class="select-item form-check-input">';
                }
            },
            { data: 'invoice_no', name: 'invoice_no', className: 'fw-bold text-primary', render: function(data) { return data ? data : '-'; } },
            { data: 'sale_id_integrate', name: 'sale_id_integrate' },
            { data: 'item_description', name: 'item_description' },
            { data: 'invoiced_quantity', name: 'invoiced_quantity' },
            { data: 'tax', name: 'tax', render: $.fn.dataTable.render.number(',', '.', 2, '') },
            { data: 'line_extension_amount', name: 'line_extension_amount', className: 'fw-semibold', render: $.fn.dataTable.render.number(',', '.', 2, '') },
            { data: 'connection_integrate', name: 'connection_integrate' },
            { data: 'issue_date', name: 'issue_date' }, 
            { 
                data: 'id_invoice_item', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    return '<button type="button" class="btn btn-danger btn-sm delete-item" data-id="' + row.id_invoice_item + '">🗑️ Delete</button>';
                }
            }
        ],
        pageLength: 30,
        lengthMenu: [ [10, 25, 30, 50, 100, -1], [10, 25, 30, 50, 100, "All"] ],
        autoWidth: false,
        dom: '<"d-flex justify-content-between align-items-center mb-3"<"dt-buttons-container"B><"d-flex align-items-center gap-3"li><"dt-search-container"f>>rt<"d-flex justify-content-between mt-3"ip>',
        language: {
            processing: '<div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"><span class="visually-hidden">Loading...</span></div>',
            lengthMenu: "_MENU_",
            info: "Found _TOTAL_ items",
            infoEmpty: "No items found",
            infoFiltered: "(filtered from _MAX_ total)"
        },
        buttons: [
            {
                extend: 'collection',
                text: '<button class="btn btn-light border shadow-sm dropdown-toggle"><i class="fa-solid fa-file-export me-2"></i> Export Data</button>',
                buttons: dropdownButtons
            }
        ],
        drawCallback: function() {
            $('.dataTables_length select').addClass('form-select form-select-sm').css('width', 'auto');
            if ($('#checkAll').is(':checked')) {
                $('.select-item').prop('checked', true);
            }
        }
    });

    // 👉 NEW: Intercept the DataTables JSON response and update the Totals box
    table.on('xhr', function (e, settings, json) {
        if (json && json.recordsTotal > 0) {
            $('#summary-totals').show();
            // Parse to float and use toLocaleString to get nice formatting with commas
            let formatOptions = { minimumFractionDigits: 2, maximumFractionDigits: 2 };
            $('#summary-total-tax').text(parseFloat(json.totalTaxSum || 0).toLocaleString('en-US', formatOptions));
            $('#summary-total-amount').text(parseFloat(json.totalAmountSum || 0).toLocaleString('en-US', formatOptions));
        } else {
            $('#summary-totals').hide();
        }
    });

    // 2.5 Ajax Form Submission Interception
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        $('#checkAll').prop('checked', false); 
        table.ajax.reload(); 
        
        if ($('#selected_connection').val() !== '') {
            $('#action-buttons').attr('style', 'display: flex !important;');
        } else {
            $('#action-buttons').attr('style', 'display: none !important;');
        }
    });

    if ($('#selected_connection').val() !== '') {
        $('#action-buttons').attr('style', 'display: flex !important;');
    }

    // 3. Select All Logic
    $('#checkAll').on('change', function() {
        const isChecked = this.checked;
        $('#datatable-items tbody input.select-item').prop('checked', isChecked);
    });

    // 4. Modal Logic (Safe Collection)
    $('#openConfirmModal').click(function () {
        let ids = getSelectedItems();
        if (ids.length === 0) return Swal.fire('No Selection', 'Please select at least one item.', 'warning');

        let total = 0;
        $('#datatable-items tbody input.select-item:checked').each(function() {
            let amountText = $(this).closest('tr').find('td').eq(6).text();
            let amount = parseFloat(amountText.replace(/[^0-9.-]+/g,"")) || 0;
            total += amount;
        });

        $('#selectedCount').text(ids.length);
        $('#totalAmount').text(total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#confirmModal').modal('show');
    });

    // 5. Submit AJAX
    $('#confirmSubmit').click(function () {
        let ids = getSelectedItems();

        if (ids.length === 0) {
            $('#confirmModal').modal('hide');
            return Swal.fire('Error', 'No items selected. Please try again.', 'error');
        }

        $.ajax({
            url: "{{ url('/developer/ConsolidateSelected') }}",
            method: "POST",
            data: { _token: "{{ csrf_token() }}", selected_items: ids, connection: $('#selected_connection').val() },
            beforeSend: function() { 
                $('#confirmModal').modal('hide'); 
                Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } }); 
            },
            success: function (res) { 
                if(res.success) {
                    Swal.fire('Success!', res.message, 'success').then(() => {
                        $('#checkAll').prop('checked', false);
                        table.ajax.reload(null, false); 
                    });
                } else {
                    Swal.fire('Warning', res.message, 'warning');
                }
            },
            error: function (xhr) { 
                let msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Submission failed.';
                Swal.fire('Error', msg, 'error'); 
            }
        });
    });

    // 6. Bulk Delete
    $('#bulkDeleteBtn').click(function () {
        let ids = getSelectedItems();

        if (ids.length === 0) return Swal.fire('No Selection', 'Select items to delete.', 'warning');

        Swal.fire({
            title: 'Bulk Delete?',
            text: `Delete ${ids.length} selected item(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/developer/consolidate/bulk-delete') }}",
                    method: "POST",
                    data: { _token: "{{ csrf_token() }}", ids: ids },
                    success: function (res) { 
                        Swal.fire('Deleted!', res.message, 'success').then(() => {
                            $('#checkAll').prop('checked', false);
                            table.ajax.reload(null, false);
                        }); 
                    }
                });
            }
        });
    });

    // 7. Single Delete
    $('#datatable-items tbody').on('click', '.delete-item', function () {
        let id = $(this).data('id');
        Swal.fire({ title: 'Delete Item?', icon: 'warning', showCancelButton: true }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/developer/consolidate/delete') }}/" + id,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                    success: function () { 
                        table.ajax.reload(null, false); 
                        Swal.fire('Deleted!', '', 'success'); 
                    }
                });
            }
        });
    });
});
</script>
@endsection