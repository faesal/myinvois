@extends('layouts.app')

@section('content')
<style>
    /* Ensure checkboxes are easy to click and centered */
    .select-item, #selectAll {
        transform: scale(1.2);
        cursor: pointer;
    }
    .table td { vertical-align: middle; }
    /* Force checkboxes to be visible and enabled */
    input[type="checkbox"] {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Invoice Submissions</h3>
        <div>
            <button class="btn btn-success shadow-sm" id="submitSelectedBtn">
                <i class="ph-paper-plane-tilt me-2"></i>Submit Selected LHDN
            </button>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('invoice.listing_submission') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="Valid" {{ request('status') == 'Valid' ? 'selected' : '' }}>Valid</option>
                            <option value="Submitted" {{ request('status') == 'Submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ph-funnel me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <div class="mb-2 text-danger">{{ isset($invoices) ? count($invoices) . ' invoices found' : 'No $invoices variable' }}</div>
                <table id="invoiceTable" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Invoice No</th>
                            <th>Customer</th>
                            <th>Amount (RM)</th>
                            <th>Issue Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="select-item form-check-input" value="{{ $inv->id_invoice }}">
                            </td>
                            <td class="fw-bold">{{ $inv->invoice_no }}</td>
                            <td>{{ $inv->customer_name }}</td>
                            <td>{{ number_format($inv->price, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('d-m-Y') }}</td>
                            <td class="text-center">
                                @php
                                    $status = strtoupper($inv->submission_status ?: 'PENDING');
                                    $badge = match($status) {
                                        'SUBMITTED' => 'primary',
                                        'VALID'     => 'success',
                                        'FAILED'    => 'danger',
                                        default     => 'warning',
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }} rounded-pill px-3">{{ $status }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ url('/invoice/show/'.$inv->id_supplier.'/'.$inv->id_customer.'/'.$inv->id_invoice) }}" 
                                   target="_blank" class="btn btn-sm btn-outline-info">
                                   <i class="ph-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable and save instance to variable 'table'
    var table = $('#invoiceTable').DataTable({
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Disable sorting on the checkbox column
        ],
        "order": [[1, 'asc']] // Default sort by Invoice No
    });

    // 1. "Select All" Logic - Works across all pages of the DataTable
    $("#selectAll").on("click", function() {
        // Get all rows in the table (including hidden pages)
        var rows = table.rows({ 'search': 'applied' }).nodes();
        // Check/Uncheck boxes
        $('input.select-item', rows).prop('checked', this.checked);
    });

    // Handle individual checkbox clicks to uncheck "Select All" if one is unchecked
    $('#invoiceTable tbody').on('change', 'input.select-item', function() {
        if (!this.checked) {
            var el = $('#selectAll').get(0);
            if (el && el.checked && ('indeterminate' in el)) {
                el.checked = false;
            }
        }
    });

    // 2. Submit Selected AJAX
    $("#submitSelectedBtn").on("click", function() {
        let selected = [];
        
        // Use the DataTable instance to find checked boxes even on other pages
        table.$('input.select-item:checked').each(function() {
            selected.push($(this).val());
        });

        if (selected.length === 0) {
            return Swal.fire({
                icon: 'warning',
                title: 'No Invoices Selected',
                text: 'Please select at least one invoice to submit.'
            });
        }

        Swal.fire({
            title: 'Submit to LHDN?',
            text: `You are about to submit ${selected.length} selected invoice(s). This action cannot be undone.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ph-paper-plane-tilt me-1"></i> Yes, Submit'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('invoice.submit_selected_lhdn') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        invoices: selected
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Submitting to LHDN...',
                            text: 'Please wait while we process your request.',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Submission Successful',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message || "An error occurred during submission.";
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Failed',
                            text: msg
                        });
                    }
                });
            }
        });
    });
});
</script>
@endsection