@extends('layouts.app')

@section('content')
@php
    $noteType = $noteType ?? 'credit';

    $title = match($noteType) {
        'credit' => 'Credit Notes',
        'debit'  => 'Debit Notes',
        'refund' => 'Refund Notes',
        default  => 'Notes'
    };

    // ✅ UPDATED: Use custom route if provided (for Self-Bill), otherwise use default
    $routeCreate = $customCreateRoute ?? route('note.create', ['note_type' => $noteType . '_note']);
    
    $labelNew = 'New ' . rtrim($title, 's');
@endphp

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<div class="container">
    <h2 class="mb-4 fw-bold">{{ $title }}</h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="alert alert-success alert-dismissible shadow-sm border-0">
        <div class="alert-heading fw-semibold">Note</div>
        <p class="text-muted mb-0">Manage and track all {{ strtolower($title) }} in the system</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <section class="mb-1">
                <br>
                <div class="row justify-content-center g-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white rounded-xl shadow-lg overflow-hidden text-center border-0">
                            <div class="card-body p-3">
                                <h3 class="fw-bold mb-2">{{ $total }}</h3>
                                <div class="fw-semibold">Total {{ $title }}</div>
                                <div class="text-sm opacity-75">Created in the system</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-success text-white rounded-xl shadow-lg overflow-hidden text-center border-0">
                            <div class="card-body p-3">
                                <h3 class="fw-bold mb-2">{{ $submitted }}</h3>
                                <div class="fw-semibold">Submitted</div>
                                <div class="text-sm opacity-75">Sent to LHDN</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <hr class="my-4">

    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold">Records</h5>
            <a href="{{ $routeCreate }}" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> {{ $labelNew }}
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle" id="datatable-items">
                        <thead class="table-light">
                            <tr>
                                {{-- Added Checkbox Header --}}
                                <th width="40" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>{{ ucfirst($noteType) }} Note #</th>
                                <th>Company</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($notes as $note)
                            <tr>
                                {{-- Added Checkbox Column --}}
                                <td class="text-center">
                                    <input type="checkbox" class="select-item form-check-input" value="{{ $note->id_invoice }}">
                                </td>
                                <td>
                                    <span class="text-primary fw-bold">{{ $note->invoice_no }}</span><br>
                                    <small class="text-muted">UUID: {{ $note->uuid ?: 'Not Generated' }}</small>
                                </td>
                                <td>
                                    {{ $note->supplier_name }}<br>
                                    <small class="text-muted">TIN: {{ $note->supplier_tin }}</small>
                                </td>
                                <td>
                                    {{ $note->customer_name }}<br>
                                    <small class="text-muted">{{ $note->customer_email }}</small>
                                </td>
                                <td class="fw-semibold">RM {{ number_format($note->price, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($note->issue_date)->format('d-m-Y') }}</td>
                                <td>
                                    @if (strtolower($note->submission_status) == 'submitted' || !empty($note->uuid))
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">Submitted</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2">Failed</span>
                                    @endif
                                </td>
                                <td> 
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a target="_blank" href="{{ route('invoice.view.public', ['unique_id' => $note->unique_id]) }}" class="btn btn-sm btn-outline-primary px-3">View</a>

                                        @if (empty($note->uuid))
                                            <form action="{{ route('self_bill_note.destroy', ['note_type' => $noteTypeSlug, 'id' => $note->id_invoice]) }}" method="POST" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-3">Delete</button>
                                            </form>
                                        @endif

                                        @if ($note->uuid != '')
                                            {{-- ✅ UPDATED: Use unique_id in URL and AJAX Logic --}}
                                            <button type="button" 
                                                    data-url="{{ url('/api/myinvois/cancelDocument/'.$note->unique_id) }}" 
                                                    class="btn btn-sm btn-danger cancel-link px-3">
                                                Cancel
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $notes->links() }}
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function () {
    // Build Dynamic Export Dropdown
    let exportDropdownButtons = [
        {
            text: '<span class="text-muted small fw-bold mt-2 d-block px-3">EXPORT SELECTED</span>',
            className: 'dropdown-item disabled',
            action: function(e, dt, node, config) { return false; }
        },
        {
            extend: 'csvHtml5',
            text: '<i class="bi bi-filetype-csv me-2 text-primary"></i> Selected Data (CSV)',
            className: 'dropdown-item py-2 fw-semibold',
            exportOptions: {
                columns: [1, 2, 3, 4, 5, 6], // Excludes Checkbox (0) and Actions (7)
                // This custom function tells DataTables to only export rows where the checkbox is ticked
                rows: function (idx, data, node) {
                    return $(node).find('input.select-item').prop('checked');
                }
            },
            action: function(e, dt, node, config) {
                // Check if any row is actually selected before running export
                let count = 0;
                dt.rows().every(function() {
                    if ($(this.node()).find('input.select-item').prop('checked')) count++;
                });
                if (count === 0) return Swal.fire("Oops", "Please select at least one document.", "warning");
                $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, node, config);
            }
        },
        {
            extend: 'excelHtml5',
            text: '<i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i> Selected Data (Excel)',
            className: 'dropdown-item py-2 fw-semibold',
            exportOptions: {
                columns: [1, 2, 3, 4, 5, 6],
                rows: function (idx, data, node) {
                    return $(node).find('input.select-item').prop('checked');
                }
            },
            action: function(e, dt, node, config) {
                let count = 0;
                dt.rows().every(function() {
                    if ($(this.node()).find('input.select-item').prop('checked')) count++;
                });
                if (count === 0) return Swal.fire("Oops", "Please select at least one document.", "warning");
                $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, node, config);
            }
        },
        {
            text: '<span class="text-muted small fw-bold mt-2 d-block px-3">EXPORT CURRENT SEARCH (ALL PAGES)</span>',
            className: 'dropdown-item disabled',
            action: function(e, dt, node, config) { return false; }
        },
        {
            extend: 'csvHtml5',
            text: '<i class="bi bi-filetype-csv me-2 text-info"></i> All Filtered Data (CSV)',
            className: 'dropdown-item py-2',
            exportOptions: {
                columns: [1, 2, 3, 4, 5, 6],
                modifier: { search: 'applied' } // Exports current search across all data
            }
        },
        {
            extend: 'excelHtml5',
            text: '<i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i> All Filtered Data (Excel)',
            className: 'dropdown-item py-2',
            exportOptions: {
                columns: [1, 2, 3, 4, 5, 6],
                modifier: { search: 'applied' }
            }
        }
    ];

    const table = $('#datatable-items').DataTable({
        paging: false, 
        searching: true,
        ordering: true,
        info: false,
        columnDefs: [{ orderable: false, targets: 0 }], // Disable sorting on checkbox column
        dom: '<"d-flex justify-content-between align-items-center mb-3"<"dt-buttons-container"B><"dt-search-container"f>>rt<"d-flex justify-content-between mt-3"ip>',
        buttons: [
            {
                extend: 'collection',
                text: '<button class="btn btn-light border shadow-sm dropdown-toggle"><i class="bi bi-file-arrow-down me-2"></i> Export Data</button>',
                buttons: exportDropdownButtons
            }
        ],
        initComplete: function () {
            $('#datatable-items_length select').addClass('form-select form-select-sm');
        }
    });

    // Select All Logic (Loops through all pages of filtered view)
    $('#selectAll').on('click', function() {
        var isChecked = this.checked;
        table.rows({ 'search': 'applied' }).every(function() {
            let rowNode = this.node();
            $(rowNode).find('input.select-item').prop('checked', isChecked);
        });
    });

    // Custom SweetAlert for Delete
    $('.delete-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Delete Note?',
            text: "This action cannot be undone. Original items will be released.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // ✅ UPDATED: Cancel Function matching submission.blade.php logic
    $(document).on('click', '.cancel-link', function (e) {
        e.preventDefault();
        const cancelUrl = $(this).data('url');
        
        Swal.fire({
            title: 'Cancel Document?',
            text: 'This will void the document on LHDN. This action is permanent.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel It',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'No',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: cancelUrl,
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}", 
                        reason: "Wrong invoice details"
                    },
                    beforeSend: function() {
                        Swal.fire({ title: 'Cancelling...', didOpen: () => { Swal.showLoading(); } });
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    },
                    error: function(xhr) {
                        let msg = 'Failed to cancel document.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    });
});
</script>

<style>
.card { border-radius: 12px; }
.rounded-xl { border-radius: 1rem; }
.badge { font-weight: 500; font-size: 0.85rem; }
.table thead th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
.dataTables_filter { float: right !important; margin-bottom: 1rem; }
.dataTables_filter input { border-radius: 6px; border: 1px solid #dee2e6; padding: 5px 10px; }

/* Clean up default datatable buttons padding to match bootstrap */
div.dt-buttons .dt-button {
    padding: 0;
    border: none;
    background: none;
}

/* Adjust datatables flex layout */
.dt-buttons-container { flex: 1; display: flex; gap: 0.5rem; }

/* ✅ FIX: Prevent SweetAlert Icon from being too big */
.swal2-icon {
    font-size: 1rem !important; 
    width: 5em !important;
    height: 5em !important;
    margin: 2.5em auto .6em !important;
}
</style>

@endsection