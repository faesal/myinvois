@extends('layouts.app')

@section('content')
<!-- ✅ DataTables & Buttons CSS/JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<!-- ✅ Custom Style -->
<style>
.dt-buttons {
    margin-bottom: 10px;
}
.dt-button.buttons-excel {

    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    margin-right: 5px;
}
.dt-button.buttons-csv {

    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    margin-right: 5px;
}
.dt-button.buttons-print {
   
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
}

/* Pagination style */
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 6px 12px;
    margin-left: 2px;
    border: 1px solid #dee2e6;
    background-color: white;
    color: #0d6efd !important;
    border-radius: 0.25rem;
    font-weight: 500;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #0d6efd !important;
    color: white !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #e2e6ea;
    color: #0d6efd !important;
}

/* Right-align search */
.dataTables_filter {
    float: right !important;
    margin-bottom: 10px;
}
</style>

<!-- ✅ DataTable Init -->
<script>
$(document).ready(function () {
    $('#datatable-items').DataTable({
        dom: '<"d-flex justify-content-between mb-2"<"dt-buttons"B><"dataTables_filter"f>>rt<"d-flex justify-content-between mt-3"<"dataTables_info"i><"dataTables_paginate"p>>',
        buttons: [
            {
                extend: 'excelHtml5',
                text: 'Export Excel',
                className: 'buttons-excel',
                title: 'Consolidated_Items'
            },
            {
                extend: 'csvHtml5',
                text: 'Export CSV',
                className: 'buttons-csv',
                title: 'Consolidated_Items'
            },
            {
                extend: 'print',
                text: 'Print',
                className: 'buttons-print'
            }
        ],
        paging: true,
        searching: true,
        ordering: true,
        pageLength: 30
    });

    $('#checkAll').on('click', function () {
        $('input[name="selected_items[]"]').prop('checked', this.checked);
    });

    let modal = new bootstrap.Modal(document.getElementById('confirmModal'));

$('#openConfirmModal').on('click', function () {
    let selected = $('input[name="selected_items[]"]:checked');

    if (selected.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Warning!',
            text: 'Please select at least one item.',
            confirmButtonText: 'OK',
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
        icon: 'warning',
        title: 'Confirm Submission',
        html: `
            You are about to submit <strong>${selected.length}</strong> items.<br><br>
            Total amount: <strong>RM ${totalAmount.toFixed(2)}</strong><br><br>
            Are you sure you want to proceed?
        `,
        showCancelButton: true,
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#22c55e',
        cancelButtonColor: '#6b7280',
    }).then((result) => {
        if (result.isConfirmed) {
            submitConsolidatedItems();
        }
    });
});

// 🔥 The function that performs the AJAX submit
function submitConsolidatedItems() {
    let selected = $('input[name="selected_items[]"]:checked');
    let ids = selected.map(function () {
        return $(this).val();
    }).get();

    Swal.fire({
        title: 'Submitting...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: "{{ route('consolidate.submit') }}",
        method: "POST",
        data: {
            _token: '{{ csrf_token() }}',
            selected_items: ids,
            connection: $('#selected_connection').val()
        },
        success: function (response) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message ?? 'Successfully submitted.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#22c55e',
                timer: 3000,
                timerProgressBar: true,
            }).then(() => {
                location.reload();
            });
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to submit.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444',
            });
        }
    });
}

});
</script>

<!-- ✅ Content -->
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

<!-- ✅ Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Submission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>You are about to submit <strong><span id="selectedCount">0</span></strong> items.</p>
        <p>Total amount: <strong>RM <span id="totalAmount">0.00</span></strong></p>
        <p>Are you sure you want to proceed?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Cancel</button>
        <button type="button" class="btn btn-success" id="confirmSubmit">✅ Yes, Submit</button>
      </div>
    </div>
  </div>
</div>
@endsection
