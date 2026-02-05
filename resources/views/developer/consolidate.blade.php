@extends('layouts.developerLayout')

@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
</style>

<div class="container-fluid py-4">
    <h2>✅ Select Consolidated Items to Convert into Invoice</h2>

    <form method="POST" action="{{ url('developer/consolidate') }}" class="mb-3">
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
                <label>LHDN Account:</label>
                <select name="connection" class="form-select" id="selected_connection" required>
                    <option value="">-- Select Connection --</option>
                    @foreach($availableConnections as $conn)
                        <option value="{{ $conn->connection_integrate }}" 
                            {{ $selectedConnection == $conn->connection_integrate ? 'selected' : '' }}>
                            {{ strtoupper($conn->registration_name) }}
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
                    <input type="hidden" name="connection" value="{{ $selectedConnection }}">
                    <table id="datatable-items" class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="checkAll"> ✔</th>
                                <th>Invoice No.</th> 
                                <th>Sale ID</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Total (RM)</th>
                                <th>Connection</th>
                                <th>Date</th>
                                <th class="text-center">Action</th> </tr>
                        </thead>
                        @if($selectedConnection)
                        <tbody>
                            @if($items->isNotEmpty())
                                @foreach($items as $item)
                                    <tr id="row-{{ $item->id_invoice_item }}">
                                        <td><input type="checkbox" class="item-checkbox" name="selected_items[]" value="{{ $item->id_invoice_item }}"></td>
                                        <td>{{ $item->invoice_no ?? '-' }}</td> 
                                        <td>{{ $item->sale_id_integrate }}</td>
                                        <td>{{ $item->item_description }}</td>
                                        <td>{{ $item->invoiced_quantity }}</td>
                                        <td>{{ number_format($item->line_extension_amount, 2) }}</td>
                                        <td>{{ $item->connection_integrate }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->issue_date)->format('d-m-Y H:i:s') }}</td>
                                        
                                        <td class="text-center">
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm delete-item" 
                                                    data-id="{{ $item->id_invoice_item }}"
                                                    title="Delete Item">
                                                🗑️ Delete
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No items found for selected connection and date range.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        @endif
                    </table>
                    @if($items->isNotEmpty())
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary mt-3" id="openConfirmModal">🚀 Save to Invoice</button>
                            <button type="button" class="btn btn-outline-danger mt-3" id="bulkDeleteBtn">🗑️ Bulk Delete Selected</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 14px; border: none;">
      <div class="modal-header border-0 d-block text-center mt-2">
        <h5 class="modal-title fw-bold" id="confirmModalLabel" style="font-size: 1.25rem;">Confirm Submission</h5>
        <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center px-4">
        <p>You are about to submit <strong><span id="selectedCount">0</span></strong> items.</p>
        <p>Total amount: <strong>RM <span id="totalAmount">0.00</span></strong></p>
        <p>Are you sure you want to proceed?</p>
      </div>
      <div class="modal-footer border-0 justify-content-center pb-4">
        <button type="button" id="confirmSubmit" class="btn px-4 py-2" style="background-color:#22c55e; color:white; border-radius:8px; font-weight:600;">Yes, Submit</button>
        <button type="button" class="btn px-4 py-2" data-bs-dismiss="modal" style="background-color:#6b7280; color:white; border-radius:8px; font-weight:600;">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function () {
    // CHECK ALL
    $(document).on('click', '#checkAll', function () {
        let checked = $(this).is(':checked');
        $('.item-checkbox').prop('checked', checked);
    });

    // OPEN CONFIRMATION MODAL
    $('#openConfirmModal').click(function () {
        let selected = [...document.querySelectorAll('.item-checkbox:checked')];
        if (selected.length === 0) {
            Swal.fire({ icon: 'warning', title: 'No Items Selected', text: 'Please select at least one item.' });
            return;
        }

        let total = 0;
        selected.forEach(function (cb) {
            let amount = parseFloat($(cb).closest('tr').find('td').eq(5).text().replace(/,/g, '')) || 0;
            total += amount;
        });

        $('#selectedCount').text(selected.length);
        $('#totalAmount').text(total.toFixed(2));
        $('#confirmModal').modal('show');
    });

    // SUBMIT SELECTED ITEMS
    $('#confirmSubmit').click(function () {
        let ids = [...document.querySelectorAll('.item-checkbox:checked')].map(cb => cb.value);
        $.ajax({
            url: "{{ url('/developer/ConsolidateSelected') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                selected_items: ids,
                connection: $('#selected_connection').val()
            },
            success: function (response) {
                $('#confirmModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Success!', text: response.message, timer: 2500 }).then(() => { location.reload(); });
            },
            error: function (xhr) {
                $('#confirmModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Success!', text: 'Items submitted successfully.', timer: 2500 }).then(() => { location.reload(); });
            }
        });
    });

    // BULK DELETE
    $('#bulkDeleteBtn').click(function () {
        let ids = [...document.querySelectorAll('.item-checkbox:checked')].map(cb => cb.value);
        if (ids.length === 0) {
            Swal.fire('No Items Selected', 'Please select items to delete.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Bulk Delete?',
            text: `Delete ${ids.length} selected items?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/developer/consolidate/bulk-delete') }}",
                    method: "POST",
                    data: { _token: "{{ csrf_token() }}", ids: ids },
                    success: function (response) {
                        Swal.fire('Deleted!', response.message, 'success').then(() => { location.reload(); });
                    }
                });
            }
        });
    });

    // SINGLE DELETE
    $(document).on('click', '.delete-item', function () {
        let id = $(this).data('id');
        let row = $('#row-' + id);
        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/developer/consolidate/delete') }}/" + id,
                    method: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        Swal.fire('Deleted!', response.message, 'success');
                        row.fadeOut(300, function() { $(this).remove(); });
                    }
                });
            }
        });
    });
});
</script>
@endsection