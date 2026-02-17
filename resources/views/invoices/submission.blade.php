@extends('layouts.app')

@section('content')
@php
    // Detect type and handle the default state
    $type = request()->query('type');
    $isSelfBill = $type === 'self_bill';
    $pageTitle = $isSelfBill ? 'Self-Bill Submissions' : 'Invoice Submissions';
@endphp

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
    .badge { font-size: 0.85rem; padding: 0.5em 0.8em; }
    .table-responsive { overflow-x: auto; }
    #invoice-table td { vertical-align: middle; }
    .error-text { color: #dc3545; font-size: 0.8rem; display: block; margin-top: 2px; }

    /* SweetAlert Icon Fix */
    .swal2-icon {
        font-size: 1rem !important; 
        width: 5em !important;
        height: 5em !important;
        margin: 2.5em auto .6em !important;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">{{ $pageTitle }}</h3>
        <div class="d-flex gap-2">
            @if($isSelfBill)
                {{-- 🚩 Self-Bill Specific Actions --}}
                <button type="button" class="btn btn-light border shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="ph ph-file-arrow-up me-2"></i> Bulk Import
                </button>
                <button class="btn btn-light border shadow-sm" id="btnExportSelected">
                    <i class="ph ph-file-arrow-down me-2"></i> Export Selected
                </button>
            @endif

            <button class="btn btn-success shadow-sm" id="submitSelectedBtn">
                <i class="ph ph-paper-plane-tilt me-2"></i> Submit Selected to LHDN
            </button>
        </div>
    </div>

    {{-- Filter Form --}}
    <form method="GET" action="{{ url('/listing_submission') }}">
        <input type="hidden" name="type" value="{{ $type }}">
        
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
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
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Table Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoice-table" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Invoice ID</th>
                            <th>{{ $isSelfBill ? 'Supplier' : 'Customer' }}</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="select-item form-check-input" value="{{ $invoice->id_invoice }}">
                            </td>
                            <td class="fw-bold">{{ $invoice->invoice_no }}</td>
                            <td>{{ $invoice->customer_name ?? '-' }}</td>
                            <td>RM {{ number_format($invoice->price, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d-m-Y H:i') }}</td>
                            <td>
                                @php $status = strtolower($invoice->submission_status ?? 'pending'); @endphp
                                @if ($status == 'submitted')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">SUBMITTED</span>
                                @elseif ($status == 'failed')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">FAILED</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">PENDING</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a target="_blank" href="{{ route('invoice.view.public', ['unique_id' => $invoice->unique_id]) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    
                                    @if ($invoice->uuid)
                                        <a href="{{ url('/api/myinvois/cancelDocument/'.$invoice->unique_id) }}" class="cancel-link btn btn-sm btn-outline-danger ms-1">Cancel</a>
                                    @endif

                                    @if (in_array($status, ['pending', 'failed', '']))
                                        <button onclick="confirmDelete('{{ $invoice->id_invoice }}')" class="btn btn-sm btn-outline-danger ms-1">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if($isSelfBill)
{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Self-Bill CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('self_invoice.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <i class="ph ph-info me-1"></i> Ensure your CSV matches the template format.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Upload File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <div class="text-center">
                        <a href="{{ route('self_invoice.download_template') }}" class="text-decoration-none small fw-bold">
                            <i class="ph ph-download-simple me-1"></i> Download Template CSV
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Process Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    var table = $('#invoice-table').DataTable({
        pageLength: 30,
        ordering: true,
        columnDefs: [{ orderable: false, targets: 0 }]
    });

    $('#selectAll').on('click', function() {
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('input.select-item', rows).prop('checked', this.checked);
    });

    $('#btnExportSelected').on('click', function() {
        let selected = [];
        table.$('input.select-item:checked').each(function() { selected.push($(this).val()); });
        if (selected.length === 0) return Swal.fire("Oops", "Please select at least one document.", "warning");
        window.location.href = "{{ route('self_invoice.export') }}?ids=" + selected.join(',');
    });

    $('#submitSelectedBtn').on('click', function() {
        let selected = [];
        table.$('input.select-item:checked').each(function() { selected.push($(this).val()); });

        if (selected.length === 0) return Swal.fire("Selection Empty", "Please select at least one invoice.", "warning");

        Swal.fire({
            title: 'Submit to LHDN?',
            text: `Processing ${selected.length} invoice(s).`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirm Submission',
            confirmButtonColor: '#198754'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('invoice.submit_selected_lhdn') }}",
                    method: "POST",
                    data: { _token: "{{ csrf_token() }}", invoices: selected },
                    beforeSend: function() { Swal.fire({ title: 'Connecting...', didOpen: () => { Swal.showLoading(); } }); },
                    success: function(response) {
                        if (response.errors && response.errors.length > 0) {
                            let errorHtml = '<ul class="text-danger text-start">';
                            response.errors.forEach(err => { errorHtml += `<li>${err}</li>`; });
                            errorHtml += '</ul>';
                            Swal.fire({ icon: 'warning', title: 'Completed with Errors', html: errorHtml }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'success', title: 'Success', text: response.message, timer: 2000, showConfirmButton: false }).then(() => location.reload());
                        }
                    },
                    error: function() { Swal.fire("System Error", "Could not reach server.", "error"); }
                });
            }
        });
    });

    // --- UPDATED CANCELLATION LOGIC (Matching Developer Layout) ---
    $(document).on('click', '.cancel-link', function (e) {
        e.preventDefault();
        
        // Extract unique_id from the href URL
        const urlParts = this.href.split('/');
        const uniqueId = urlParts[urlParts.length - 1];

        Swal.fire({
            title: 'Cancel Document?',
            text: "This request will be sent to LHDN. Valid only within 72 hours of submission.",
            icon: 'warning',
            input: 'select',
            inputOptions: {
                'wrong_data': 'Incorrect Data / Error in Content',
                'duplicate': 'Duplicate Document',
                'order_cancelled': 'Transaction Cancelled by Customer',
                'other': 'Other'
            },
            inputPlaceholder: 'Select a reason',
            showCancelButton: true,
            confirmButtonColor: '#f1c40f',
            confirmButtonText: 'Confirm Cancellation',
            inputValidator: (value) => {
                if (!value) return 'A reason is required by LHDN!'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/api/myinvois/cancelDocument') }}/" + uniqueId,
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        reason: result.value
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Connecting to MyInvois...',
                            didOpen: () => Swal.showLoading(),
                            allowOutsideClick: false
                        });
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message || 'Invoice cancelled successfully.',
                        }).then(() => location.reload());
                    },
                    error: function(xhr) {
                        let errorMsg = xhr.responseJSON?.message || "LHDN rejected the cancellation.";
                        Swal.fire('Cancellation Failed', errorMsg, 'error');
                    }
                });
            }
        });
    });

    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Delete Invoice?',
            text: "This will remove the record.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = "{{ url('/delete_invoice') }}/" + id;
        });
    };
});
</script>
@endsection