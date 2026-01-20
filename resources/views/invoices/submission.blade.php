@extends('layouts.app')

@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
    .badge { font-size: 0.85rem; padding: 0.5em 0.8em; }
    .table-responsive { overflow-x: auto; }
    #invoice-table td { vertical-align: middle; }
    .error-text { color: #dc3545; font-size: 0.8rem; display: block; margin-top: 2px; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Invoice Submissions</h3>
        <div>
            <button class="btn btn-success shadow-sm" id="submitSelectedBtn">
                <i class="ph-paper-plane-tilt me-2"></i> Submit Selected to LHDN
            </button>
        </div>
    </div>

    <form method="GET" action="{{ url('/listing_submission') }}">
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
                            <th>Customer</th>
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
                                @php $status = strtolower($invoice->submission_status); @endphp
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
                                    <a target="_blank" href="{{url('/show_invoice/'.$invoice->id_supplier.'/'.($invoice->id_customer ?: '6').'/'.$invoice->id_invoice)}}" class="btn btn-sm btn-outline-primary">View</a>
                                    @if ($invoice->uuid)
                                        <a href="{{ url('/cancelDocument/'.$invoice->uuid) }}" class="cancel-link btn btn-sm btn-outline-danger ms-1">Cancel</a>
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

    // 1. Select All Logic (DataTable aware)
    $('#selectAll').on('click', function() {
        var rows = table.rows({ 'search': 'applied' }).nodes();
        $('input.select-item', rows).prop('checked', this.checked);
    });

    // 2. Bulk Submit AJAX with Detailed Error Popups
    $('#submitSelectedBtn').on('click', function() {
        let selected = [];
        table.$('input.select-item:checked').each(function() {
            selected.push($(this).val());
        });

        if (selected.length === 0) {
            return Swal.fire("Selection Empty", "Please select at least one invoice to proceed.", "warning");
        }

        Swal.fire({
            title: 'Submit to LHDN?',
            text: `You are about to process ${selected.length} invoice(s).`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirm Submission',
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6e7881'
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
                            title: 'Connecting to LHDN...', 
                            text: 'Processing API submission, please wait.',
                            allowOutsideClick: false, 
                            didOpen: () => { Swal.showLoading(); }
                        });
                    },
                    success: function(response) {
                        // Check if there are error details returned from the catch block
                        if (response.errors && response.errors.length > 0) {
                            let errorHtml = '<div style="text-align: left; max-height: 300px; overflow-y: auto;">';
                            errorHtml += '<b>Results:</b> ' + response.message + '<br><br>';
                            errorHtml += '<b>Failure Details:</b><ul class="text-danger">';
                            response.errors.forEach(err => {
                                errorHtml += `<li>${err}</li>`;
                            });
                            errorHtml += '</ul></div>';

                            Swal.fire({
                                icon: 'warning',
                                title: 'Submission Completed with Errors',
                                html: errorHtml,
                                confirmButtonText: 'Close & Refresh'
                            }).then(() => { location.reload(); });
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => { location.reload(); });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire("System Error", "Could not reach the submission server.", "error");
                    }
                });
            }
        });
    });

    // 3. Cancel Document Alert
    $(document).on('click', '.cancel-link', function (e) {
        e.preventDefault();
        const href = this.href;
        Swal.fire({
            title: 'Cancel Document?',
            text: 'This will void the document on LHDN. This action is permanent.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel It',
            confirmButtonColor: '#ef4444'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = href;
        });
    });
});
</script>

@endsection