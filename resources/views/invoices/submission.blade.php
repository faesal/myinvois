@extends('layouts.app')

@section('content')
@php
    // Detect type and handle the default state
    $type = request()->query('type');
    $isSelfBill = $type === 'self_bill';
    $pageTitle = $isSelfBill ? 'Self-Bill Submissions' : 'Invoice Submissions';

    // Fetch invoice types dynamically based on the current view mode
    // This ensures ONLY relevant types (01-04 for normal, 11-14 for self-bill) are shown
    $invoiceTypes = \Illuminate\Support\Facades\DB::table('invoice_type')
        ->where('code', 'like', $isSelfBill ? '1%' : '0%') 
        ->orderBy('code')
        ->get();

    // Define dynamic routes: Targets 'self_invoice' routes if $isSelfBill is true, otherwise 'invoice'
    $exportRoute = $isSelfBill ? route('self_invoice.export') : route('invoice.export');
    $importRoute = $isSelfBill ? route('self_invoice.import') : route('invoice.import');
    $templateRoute = $isSelfBill ? route('self_invoice.download_template') : route('invoice.download_template');
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
    
    /* Clean up default datatable buttons padding to match bootstrap */
    div.dt-buttons .dt-button {
        padding: 0;
        border: none;
        background: none;
    }
    
    /* Adjust datatables flex layout */
    .dt-buttons-container { flex: 1; display: flex; gap: 0.5rem; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">{{ $pageTitle }}</h3>
        {{-- Buttons used to be here, moved down to the table card --}}
    </div>

    {{-- Filter Form --}}
    <form method="GET" action="{{ url('/listing_submission') }}">
        <input type="hidden" name="type" value="{{ $type }}">
        
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted mb-1">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted mb-1">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted mb-1">Document Type</label>
                        <select name="invoice_type_code" class="form-select">
                            <option value="">All Types</option>
                            @foreach($invoiceTypes as $invType)
                                <option value="{{ $invType->code }}" {{ request('invoice_type_code') == $invType->code ? 'selected' : '' }}>
                                    {{ $invType->description }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="submitted" {{ strtolower(request('status')) == 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="pending" {{ strtolower(request('status')) == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="failed" {{ strtolower(request('status')) == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- ========================================================= --}}
    {{-- 🚀 BATCH SYNC PROGRESS BAR CONTAINER (HIDDEN BY DEFAULT) --}}
    {{-- ========================================================= --}}
    <div id="sync-progress-container" class="card mb-4 border-0 shadow-sm" style="display: none; border-left: 5px solid #007bff !important;">
        <div class="card-body">
            <h5 id="sync-status-text" class="text-primary font-weight-bold mb-1">Processing Invoices to LHDN...</h5>
            
            <div class="progress mt-3 mb-2" style="height: 25px;">
                <div id="sync-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                     role="progressbar" style="width: 0%; font-size: 14px; font-weight: bold;" 
                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-2">
                <span id="sync-detail-text" class="text-muted fw-bold small">Waiting for server response...</span>
                <button type="button" id="btn-stop-sync" class="btn btn-danger btn-sm shadow-sm">
                    <i class="ph ph-x-circle me-1"></i> Cancel Process
                </button>
            </div>
        </div>
    </div>
    {{-- ========================================================= --}}

    {{-- Table Card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            
            {{-- MOVED BUTTONS: Now placed directly above the table --}}
            <div class="d-flex justify-content-end gap-2 mb-3 pb-3 border-bottom">
                {{-- Bulk Import --}}
                <button type="button" class="btn btn-light border shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="ph ph-file-arrow-up me-2"></i> Bulk Import
                </button>

                {{-- Delete Selected --}}
                <button type="button" class="btn btn-outline-danger shadow-sm" id="deleteSelectedBtn">
                    <i class="ph ph-trash me-2"></i> Delete Selected
                </button>

                {{-- Submit Selected --}}
                <button class="btn btn-success shadow-sm" id="submitSelectedBtn">
                    <i class="ph ph-paper-plane-tilt me-2"></i> Submit Selected to LHDN
                </button>
            </div>
            {{-- END MOVED BUTTONS --}}

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
                           <td>{{ $invoice->customer_name ?? $invoice->supplier_name ?? $invoice->party_name ?? '-' }}</td>
                            <td>RM {{ number_format($invoice->price, 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d-m-Y H:i') }}</td>
                            <td>
                                @php 
                                    // Normalize status to lowercase to avoid case-sensitivity bugs (Failed vs failed)
                                    $status = strtolower($invoice->submission_status ?? 'pending'); 
                                @endphp
                                
                                @if ($status == 'submitted' || $status == 'accepted')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">{{ strtoupper($status) }}</span>
                                @elseif ($status == 'failed' || $status == 'rejected' || $status == 'error')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">{{ strtoupper($status) }}</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">{{ strtoupper($status) }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a target="_blank" href="{{ url('invoice/view/')}}/{{$invoice->unique_id}}" class="btn btn-sm btn-outline-primary">View</a>
                                    
                                    @if ($invoice->uuid)
                                        <a href="{{ url('/api/myinvois/cancelDocument/'.$invoice->unique_id) }}" class="cancel-link btn btn-sm btn-outline-danger ms-1">Cancel</a>
                                    @endif

                                    @if (!in_array($status, ['submitted', 'accepted']))
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

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import {{ $isSelfBill ? 'Self-Bill' : 'Invoice' }} CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ $importRoute }}" method="POST" enctype="multipart/form-data">
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
                        <a href="{{ $templateRoute }}" class="text-decoration-none small fw-bold">
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

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    // ==========================================================
    // Catch Laravel Flash Messages and show via SweetAlert
    // ==========================================================
    @if(session('success'))
        Swal.fire({ 
            icon: 'success', 
            title: 'Success', 
            text: "{!! session('success') !!}" 
        });
    @endif

    @if(session('warning'))
        Swal.fire({ 
            icon: 'warning', 
            title: 'Attention', 
            html: "{!! session('warning') !!}" 
        });
    @endif

    @if(session('error'))
        Swal.fire({ 
            icon: 'error', 
            title: 'Error', 
            html: "{!! session('error') !!}" 
        });
    @endif
    // ==========================================================

    let invoiceTypes = @json($invoiceTypes);
    let exportRoute = "{{ $exportRoute }}";
    
    // 1. Core Export Logic Function (Only used for specific selections now)
    function executeBackendExport(idsArray) {
        let url = new URL(exportRoute);
        let typeVal = $('input[name="type"]').val();
        if (typeVal) url.searchParams.append('type', typeVal);
        url.searchParams.append('ids', idsArray.join(','));
        window.location.href = url.toString();
    }

    // 2. Build Dropdown Buttons Array
    let dropdownButtons = [
        {
            text: '<i class="ph ph-check-square me-2 text-primary"></i> Export Selected',
            className: 'dropdown-item py-2 fw-semibold',
            action: function (e, dt, node, config) {
                let selected = [];
                // DataTables API loops through ALL pages, not just visible DOM
                dt.rows({ search: 'applied' }).every(function() {
                    let rowNode = this.node();
                    let checkbox = $(rowNode).find('input.select-item');
                    if (checkbox.prop('checked')) {
                        selected.push(checkbox.val());
                    }
                });
                
                if (selected.length === 0) {
                    return Swal.fire("Oops", "Please select at least one document.", "warning");
                }
                executeBackendExport(selected);
            }
        },
        // Divider
        {
            text: '<span class="text-muted small fw-bold mt-2 d-block px-3">EXPORT CURRENT SEARCH</span>',
            className: 'dropdown-item disabled',
            action: function(e, dt, node, config) { return false; }
        },
        // Native DataTable Exporter (Looks at table, respects filters, ignores pagination limits)
        {
            extend: 'csvHtml5',
            text: '<i class="ph ph-file-csv me-2 text-info"></i> All Filtered Data (CSV)',
            className: 'dropdown-item py-2 fw-semibold',
            exportOptions: { 
                columns: [1, 2, 3, 4, 5], 
                modifier: { search: 'applied' } // Explicitly targets current search across all pages
            }
        },
        // Native DataTable Exporter
        {
            extend: 'excelHtml5',
            text: '<i class="ph ph-file-xls me-2 text-warning"></i> All Filtered Data (Excel)',
            className: 'dropdown-item py-2 fw-semibold',
            exportOptions: { 
                columns: [1, 2, 3, 4, 5],
                modifier: { search: 'applied' }
            }
        },
        // Divider
        {
            text: '<span class="text-muted small fw-bold mt-2 d-block px-3">EXPORT SPECIFIC TYPE</span>',
            className: 'dropdown-item disabled',
            action: function(e, dt, node, config) { return false; }
        }
    ];

    // 3. Append Specific Types to Dropdown Menu (Uses Backend)
    let specificTypeButtons = invoiceTypes.map(function(type) {
        return {
            text: '<i class="ph ph-file-text me-2 text-secondary"></i> All ' + type.description + 's',
            className: 'dropdown-item py-2',
            action: function (e, dt, node, config) {
                let url = new URL(exportRoute);
                url.searchParams.append('invoice_type_code', type.code);
                
                let typeVal = $('input[name="type"]').val();
                if (typeVal) url.searchParams.append('type', typeVal);
                
                // Append form filters
                let startDate = $('input[name="start_date"]').val();
                let endDate = $('input[name="end_date"]').val();
                let status = $('select[name="status"]').val();
                if (startDate) url.searchParams.append('start_date', startDate);
                if (endDate) url.searchParams.append('end_date', endDate);
                if (status) url.searchParams.append('status', status);

                window.location.href = url.toString();
            }
        };
    });

    dropdownButtons = dropdownButtons.concat(specificTypeButtons);

    // 4. Initialize DataTables
    var table = $('#invoice-table').DataTable({
        pageLength: 30,
        ordering: true,
        columnDefs: [{ orderable: false, targets: 0 }],
        dom: '<"d-flex justify-content-between align-items-center mb-3"<"dt-buttons-container"B><"dt-search-container"f>>rt<"d-flex justify-content-between mt-3"ip>',
        buttons: [
            {
                extend: 'collection',
                text: '<button class="btn btn-light border shadow-sm dropdown-toggle"><i class="ph ph-file-arrow-down me-2"></i> Export Data</button>',
                buttons: dropdownButtons
            }
        ]
    });

    // 5. Select All Logic (Loops through ALL pages of filtered view)
    $('#selectAll').on('click', function() {
        var isChecked = this.checked;
        table.rows({ 'search': 'applied' }).every(function() {
            let rowNode = this.node();
            $(rowNode).find('input.select-item').prop('checked', isChecked);
        });
    });

    // ==========================================================
    // 🚀 6. NEW ASYNCHRONOUS BATCH SUBMISSION LOGIC
    // ==========================================================
    let checkProgressInterval;
    let pingWorkerInterval;
    let currentBatchId = null;
    let allSelectedInvoices = [];

    // 🚀 UPDATED: Pointing to the new secure Subscriber Routes
    const submitRoute = "{{ route('invoices.submitSelected') }}"; 
    const checkProgressRoute = "{{ route('invoices.checkBatch') }}";
    const triggerWorkerRoute = "{{ route('invoices.triggerWorker') }}"; 
    const stopWorkerRoute = "{{ route('invoices.stopWorker') }}";

    $('#submitSelectedBtn').on('click', function() {
        allSelectedInvoices = [];
        
        table.rows({ search: 'applied' }).every(function() {
            let rowNode = this.node();
            let checkbox = $(rowNode).find('input.select-item');
            if (checkbox.prop('checked')) {
                allSelectedInvoices.push(checkbox.val());
            }
        });

        if (allSelectedInvoices.length === 0) return Swal.fire("Selection Empty", "Please select at least one invoice.", "warning");

        Swal.fire({
            title: 'Start Sync Process?',
            text: `You are about to process ${allSelectedInvoices.length} invoice(s) to LHDN.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Start Process',
            confirmButtonColor: '#198754'
        }).then((result) => {
            if (result.isConfirmed) {
                startSubmissionBatch();
            }
        });
    });

    function startSubmissionBatch() {
        // Show progress UI
        $('#sync-progress-container').slideDown();
        $('#sync-progress-bar').css('width', '0%').text('0%').removeClass('bg-success bg-warning bg-danger').addClass('bg-primary progress-bar-animated');
        $('#sync-status-text').text('Initiating batch sync... Please wait.');
        $('#sync-detail-text').text('Sending payload to server...');
        $('#btn-stop-sync').prop('disabled', false).html('<i class="ph ph-x-circle me-1"></i> Cancel Process');

        $.ajax({
            url: submitRoute, 
            type: 'POST',
            data: { 
                _token: "{{ csrf_token() }}",
                invoices: allSelectedInvoices 
            },
            success: function(response) {
                if (response.success && response.batch_id) {
                    currentBatchId = response.batch_id;
                    $('#sync-status-text').text(response.message);
                    
                    // Start tracking and pinging worker
                    startTrackingProgress(currentBatchId, response.has_more);
                    startPinger();
                } else if (response.success) {
                    // Handled already submitted invoices or empty payload
                    $('#sync-progress-bar').css('width', '100%').removeClass('bg-primary progress-bar-animated').addClass('bg-success').text('Complete');
                    $('#sync-status-text').text(response.message);
                    setTimeout(() => { $('#sync-progress-container').slideUp(); location.reload(); }, 3000);
                } else {
                    // Fallback for custom logic that might return success: false
                    Swal.fire("System Error", response.message || "Failed to start process.", "error");
                    $('#sync-progress-container').slideUp();
                }
            },
            error: function(xhr) {
                Swal.fire("System Error", xhr.responseJSON?.message || "Failed to connect to the server.", "error");
                $('#sync-progress-container').slideUp();
            }
        });
    }

    function startTrackingProgress(batchId, hasMore) {
        clearInterval(checkProgressInterval);

        checkProgressInterval = setInterval(function() {
            $.ajax({
                url: checkProgressRoute, 
                type: 'GET',
                data: { batch_id: batchId },
                success: function(response) {
                    let progress = Math.round(response.progress);
                    
                    $('#sync-progress-bar').css('width', progress + '%').text(progress + '%');
                    $('#sync-detail-text').text(`Processing Queue: ${response.remaining_batch} / ${response.total_batch} jobs remaining.`);

                    // If batch encounters a failure but keeps running
                    if (response.has_failures) {
                        $('#sync-progress-bar').removeClass('bg-primary').addClass('bg-warning');
                        if (response.error_message) {
                            $('#sync-detail-text').text('Warning: ' + response.error_message);
                        }
                    }

                    // Batch finished
                    if (response.status === 'complete' || progress >= 100) {
                        clearInterval(checkProgressInterval);
                        clearInterval(pingWorkerInterval);
                        
                        $('#sync-progress-bar').removeClass('progress-bar-animated bg-warning bg-primary').addClass('bg-success');
                        
                        if (hasMore) {
                            // AUTO-RELAY: Loop again for next batch if > 5000 limit
                            $('#sync-status-text').text('Batch finished. Continuing with remaining queue...');
                            setTimeout(startSubmissionBatch, 2000);
                        } else {
                            $('#sync-status-text').text('All Invoices Successfully Dispatched!');
                            $('#sync-detail-text').text('Sync process has ended.');
                            $('#btn-stop-sync').prop('disabled', true);
                            
                            setTimeout(() => {
                                $('#sync-progress-container').slideUp();
                                location.reload(); 
                            }, 3000);
                        }
                    }
                }
            });
        }, 2000); 
    }

    function startPinger() {
        clearInterval(pingWorkerInterval);
        
        // Pings every 15 seconds to keep the worker alive on shared hosting
        pingWorkerInterval = setInterval(function() {
            $.ajax({
                url: triggerWorkerRoute, 
                type: 'GET',
                success: function() { console.log('Worker pulse sent.'); }
            });
        }, 15000); 
    }

    $('#btn-stop-sync').on('click', function() {
        if (!currentBatchId) return;
        
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Stopping...').prop('disabled', true);
        clearInterval(checkProgressInterval);
        clearInterval(pingWorkerInterval);

        $.ajax({
            url: stopWorkerRoute, 
            type: 'POST',
            data: { 
                _token: "{{ csrf_token() }}",
                batch_id: currentBatchId 
            },
            success: function(response) {
                $('#sync-progress-bar').removeClass('bg-primary progress-bar-animated').addClass('bg-danger');
                $('#sync-status-text').text('Process Terminated by User.');
                $('#sync-detail-text').text(response.message);
                setTimeout(() => location.reload(), 2000);
            }
        });
    });

    // 6b. Delete Selected Logic
    $('#deleteSelectedBtn').on('click', function(e) {
        e.preventDefault(); // Stops the browser from doing anything stupid

        let selected = [];
        table.rows({ search: 'applied' }).every(function() {
            let rowNode = this.node();
            let checkbox = $(rowNode).find('input.select-item');
            if (checkbox.prop('checked')) {
                selected.push(checkbox.val());
            }
        });

        if (selected.length === 0) return Swal.fire("Selection Empty", "Please select at least one invoice to delete.", "warning");

        Swal.fire({
            title: 'Delete Selected?',
            text: `Are you sure you want to soft-delete ${selected.length} invoice(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('/delete_selected_invoices') }}", 
                    method: "POST",
                    data: { 
                        _token: "{{ csrf_token() }}", 
                        invoices: selected 
                    },
                    beforeSend: function() { Swal.fire({ title: 'Deleting...', didOpen: () => { Swal.showLoading(); } }); },
                    success: function(response) {
                        Swal.fire({ 
                            icon: 'success', 
                            title: 'Deleted!', 
                            text: response.message || 'Invoices have been soft-deleted.', 
                            timer: 2000, 
                            showConfirmButton: false 
                        }).then(() => location.reload());
                    },
                    error: function() { Swal.fire("System Error", "Could not reach server to delete invoices.", "error"); }
                });
            }
        });
    });

    // 7. Cancellation Logic
    $(document).on('click', '.cancel-link', function (e) {
        e.preventDefault();
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
                    data: { _token: "{{ csrf_token() }}", reason: result.value },
                    beforeSend: function() {
                        Swal.fire({ title: 'Connecting to MyInvois...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });
                    },
                    success: function(response) {
                        Swal.fire({ icon: 'success', title: 'Success', text: response.message || 'Invoice cancelled successfully.' }).then(() => location.reload());
                    },
                    error: function(xhr) {
                        let errorMsg = xhr.responseJSON?.message || "LHDN rejected the cancellation.";
                        Swal.fire('Cancellation Failed', errorMsg, 'error');
                    }
                });
            }
        });
    });

    // 8. Single Delete Logic
    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Delete Invoice?',
            text: "This will soft-delete the record.",
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