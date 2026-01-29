@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<style>
.dt-buttons { margin-bottom: 10px; }
.dt-button { border: none; padding: 6px 12px; border-radius: 4px; margin-right: 5px; }
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 6px 12px; margin-left: 2px; border: 1px solid #dee2e6; background-color: white;
    color: #0d6efd !important; border-radius: 0.25rem; font-weight: 500;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #0d6efd !important; color: white !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #e2e6ea; color: #0d6efd !important;
}
.dataTables_filter { float: right !important; margin-bottom: 10px; }

/* 🔥 FIX: Make SweetAlert Icon Smaller */
.swal2-icon {
    transform: scale(0.6) !important; /* Scale down to 60% */
    margin-top: 15px !important;      /* Adjust top margin */
    margin-bottom: 0px !important;    /* Adjust bottom margin */
}
</style>

<script>
$(document).ready(function () {
    // ⚡ PERFORMANCE FIX 1: Add 'deferRender' to speed up initial loading
    var table = $('#datatable-items').DataTable({
        dom: '<"d-flex justify-content-between mb-2"<"dt-buttons"B><"dataTables_filter"f>>rt<"d-flex justify-content-between mt-3"<"dataTables_info"i><"dataTables_paginate"p>>',
        deferRender: true, // ⚡ Key fix for large lists
        processing: true,  // Show 'Processing...' indicator
        buttons: [
            { extend: 'excelHtml5', text: 'Export Excel', className: 'buttons-excel', title: 'Consolidated_Items' },
            { extend: 'csvHtml5', text: 'Export CSV', className: 'buttons-csv', title: 'Consolidated_Items' },
            { extend: 'print', text: 'Print', className: 'buttons-print' }
        ],
        paging: true,
        searching: true,
        ordering: true,
        pageLength: 30
    });

    // 2. Check All Logic
    $('#checkAll').on('click', function () {
        // Use DataTables API to select inputs across all pages if needed, 
        // or just visible ones. Standard jQuery here works for visible rows.
        $('input[name="selected_items[]"]').prop('checked', this.checked);
    });

    // 3. Bulk Submit Logic
    $('#openConfirmModal').on('click', function () {
        let selected = $('input[name="selected_items[]"]:checked');

        if (selected.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning!',
                text: 'Please select at least one item.',
                confirmButtonColor: '#f59e0b',
            });
            return;
        }

        let totalAmount = 0;
        selected.each(function () {
            let amount = parseFloat($(this).closest('tr').find('td').eq(4).text().replace(/,/g, '')) || 0;
            totalAmount += amount;
        });

        Swal.fire({
            icon: 'question',
            title: 'Confirm Submission',
            html: `You are about to submit <strong>${selected.length}</strong> items.<br>Total: <strong>RM ${totalAmount.toFixed(2)}</strong>`,
            showCancelButton: true,
            confirmButtonText: 'Yes, Submit',
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#6b7280',
        }).then((result) => {
            if (result.isConfirmed) {
                submitConsolidatedItems();
            }
        });
    });

    function submitConsolidatedItems() {
        let selected = $('input[name="selected_items[]"]:checked');
        let ids = selected.map(function () { return $(this).val(); }).get();

        Swal.fire({ title: 'Submitting...', didOpen: () => Swal.showLoading() });

        $.ajax({
            url: "{{ route('consolidate.submit') }}",
            method: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                selected_items: ids,
                connection: $('#selected_connection').val()
            },
            success: function (response) {
                Swal.fire('Success!', response.message ?? 'Submitted.', 'success').then(() => location.reload());
            },
            error: function () {
                Swal.fire('Error!', 'Failed to submit.', 'error');
            }
        });
    }

    // ⚡ PERFORMANCE FIX 2: Specific Event Delegation
    // Bind to tbody instead of document to limit scope. This makes clicks instant.
    $('#datatable-items tbody').on('click', '.delete-item', function (e) {
        e.preventDefault();
        
        // ⚡ PERFORMANCE FIX 3: Immediate visual feedback
        let btn = $(this);
        let originalText = btn.html();
        btn.prop('disabled', true).html('⏳'); // Disable button immediately

        let id = btn.data('id');
        let row = btn.closest('tr');

        Swal.fire({
            title: 'Delete Item?',
            text: "This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/consolidate/item/delete') }}/" + id,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function (response) {
                        Swal.fire('Deleted!', 'Item removed.', 'success');
                        // Remove row via DataTables API so pagination updates correctly
                        table.row(row).remove().draw(false);
                    },
                    error: function (xhr) {
                        // Re-enable button if error occurs
                        btn.prop('disabled', false).html(originalText);
                        
                        let msg = xhr.responseJSON?.message || 'Something went wrong.';
                        Swal.fire('Error!', msg, 'error');
                    }
                });
            } else {
                // Re-enable button if cancelled
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

});
</script>

<div class="container-fluid py-4">
    <h2>✅ Select Consolidated Items to Convert into Invoice</h2>

    <form method="POST" action="{{ route('consolidate.select') }}" class="mb-3">
        @csrf
        <div class="row">
            <div class="col-md-3">
                <label>Start Date:</label>
                <input type="date" name="start_date" value="{{ $start }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label>End Date:</label>
                <input type="date" name="end_date" value="{{ $end }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label>Connection:</label>
                <select name="connection" class="form-select" id="selected_connection">
                    <option value="">-- All Connections --</option>
                    @foreach($availableConnections as $conn)
                        <option value="{{ $conn }}" {{ request('connection') == $conn ? 'selected' : '' }}>
                            {{ strtoupper($conn) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button class="btn btn-secondary">🔍 Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <form id="consolidateForm">
                    @csrf
                    <input type="hidden" name="connection" value="{{ request('connection') }}">
                    <table id="datatable-items" class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="checkAll"> ✔</th>
                                <th>Sale ID</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Total (RM)</th>
                                <th>Connection</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td><input type="checkbox" name="selected_items[]" value="{{ $item->id_invoice_item }}"></td>
                                    <td>{{ $item->sale_id_integrate }}</td>
                                    <td>{{ $item->item_description }}</td>
                                    <td>{{ $item->invoiced_quantity }}</td>
                                    <td>{{ number_format($item->line_extension_amount, 2) }}</td>
                                    <td>{{ $item->connection_integrate }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->issue_date)->format('d/m/Y') }}</td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm delete-item" data-id="{{ $item->id_invoice_item }}" title="Delete">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary mt-3" id="openConfirmModal">🚀 Save to Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Confirm Submission</h5></div>
      <div class="modal-body"><p>Processing...</p></div>
    </div>
  </div>
</div>
@endsection