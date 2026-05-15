@extends('layouts.developerLayout')

@section('content')

<style>
    /* Styling for the DataTables toolbar */
    .dt-buttons-container { flex: 1; display: flex; gap: 0.5rem; }
    div.dt-buttons .dt-button { padding: 0; border: none; background: none; }
    .dataTables_filter { float: right !important; margin-bottom: 15px; }
    
    /* Status Cards */
    .status-card {
        border-radius: 10px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        height: 100%;
    }
    
    .status-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .status-card.active { border: 2px solid #333; box-shadow: 0 5px 15px rgba(0,0,0,0.3); transform: translateY(-2px); }
    .status-card .card-title { font-size: 0.9rem; font-weight: 600; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-card .card-count { font-size: 2.5rem; font-weight: 700; margin: 0; transition: color 0.3s ease; }
    
    .status-card.card-submitted { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .status-card.card-pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
    .status-card.card-failed { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
    
    /* 🚀 Live Background Progress Banner */
    #background-progress-banner {
        display: none;
        background: #fff;
        border-left: 5px solid #22c55e;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        padding: 15px 20px;
        margin-bottom: 20px;
        animation: slideDown 0.5s ease-out;
        position: relative;
        z-index: 1000;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .filter-col { margin-bottom: 15px; }
        .badge { width: 100%; padding: 8px !important; font-size: 0.75rem !important; }
        .btn-info, .btn-warning, .btn-danger, .resubmit-btn { width: 100%; margin-bottom: 5px; }
        .status-card { margin-bottom: 15px; }
        .status-card .card-count { font-size: 2rem; }
        .action-buttons-wrapper { flex-direction: column; width: 100%; }
        .action-buttons-wrapper button { width: 100%; }
    }
</style>

@php
    // ========================================================================
    // 🚀 BLADE-LEVEL FILTERING & COUNTING
    // Fixes 1: Prevents cards dropping to 0 by using backend $statusCounts
    // Fixes 2: Catches Unrecognized/NULL statuses and maps them to 'Pending'
    // Fixes 3: Enforces strict table filtering
    // ========================================================================
    $reqStatus = strtoupper(request('status', 'ALL'));
    
    // 1. Process Status Cards from the global backend variable
    $scSubmitted = 0;
    $scPending = 0;
    $scFailed = 0;
    
    $rawCounts = $statusCounts ?? [];
    if (is_iterable($rawCounts)) {
        foreach($rawCounts as $key => $count) {
            $k = strtoupper(trim((string)$key));
            if ($k === 'SUBMITTED' || $k === 'ACCEPTED') {
                $scSubmitted += (int)$count;
            } elseif ($k === 'FAILED' || $k === 'REJECTED' || $k === 'ERROR') {
                $scFailed += (int)$count;
            } else {
                // Safely catches 'PENDING', '', NULL, or any weird database artifacts
                $scPending += (int)$count;
            }
        }
    }

    // 2. Strict Filter for the Table Rows
    $filteredInvoices = [];
    if(isset($invoices) && $invoices->isNotEmpty()) {
        foreach($invoices as $inv) {
            $rawStatus = strtoupper(trim($inv->submission_status ?: 'PENDING'));
            
            if($rawStatus === 'SUBMITTED' || $rawStatus === 'ACCEPTED') {
                $mappedStatus = 'SUBMITTED';
            } elseif($rawStatus === 'FAILED' || $rawStatus === 'REJECTED' || $rawStatus === 'ERROR') {
                $mappedStatus = 'FAILED';
            } else {
                $mappedStatus = 'PENDING';
            }

            if($reqStatus === 'ALL' || $reqStatus === $mappedStatus) {
                $filteredInvoices[] = $inv;
            }
        }
    }
@endphp

<div class="container-fluid">
    <h3 class="mb-4">Invoice Submissions</h3>

    {{-- Status Cards (Now strictly using the cleaned StatusCounts variable) --}}
    <div class="row mb-4">
        <div class="col-md-4 col-12 mb-3 mb-md-0">
            <div class="status-card card-submitted {{ $reqStatus === 'SUBMITTED' ? 'active' : '' }}" data-status="Submitted" id="card-submitted">
                <div class="card-title"><i class="fas fa-check-circle me-2"></i>Submitted</div>
                <h2 class="card-count" id="count-submitted">{{ $scSubmitted }}</h2>
            </div>
        </div>
        <div class="col-md-4 col-12 mb-3 mb-md-0">
            <div class="status-card card-pending {{ $reqStatus === 'PENDING' ? 'active' : '' }}" data-status="Pending" id="card-pending">
                <div class="card-title"><i class="fas fa-clock me-2"></i>Pending</div>
                <h2 class="card-count" id="count-pending">{{ $scPending }}</h2>
            </div>
        </div>
        <div class="col-md-4 col-12 mb-3 mb-md-0">
            <div class="status-card card-failed {{ $reqStatus === 'FAILED' ? 'active' : '' }}" data-status="Failed" id="card-failed">
                <div class="card-title"><i class="fas fa-times-circle me-2"></i>Failed</div>
                <h2 class="card-count" id="count-failed">{{ $scFailed }}</h2>
            </div>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('developer.invoices.index') }}" id="searchForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-2 col-6 filter-col">
                        <label class="form-label small fw-bold">Start Date</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                    </div>
                    <div class="col-md-2 col-6 filter-col">
                        <label class="form-label small fw-bold">End Date</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                    </div>
                    <div class="col-md-2 col-6 filter-col">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" id="statusFilter" class="form-control">
                            <option value="ALL">All</option>
                            <option value="Submitted" {{ request('status')=='Submitted'?'selected':'' }}>Submitted</option>
                            <option value="Pending" {{ request('status')=='Pending'?'selected':'' }}>Pending</option>
                            <option value="Failed" {{ request('status')=='Failed'?'selected':'' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6 filter-col">
                        <label class="form-label small fw-bold">Invoice Type</label>
                        <select name="invoice_type" class="form-control">
                            <option value="ALL">All</option>
                            @foreach ($invoiceTypes as $type)
                                <option value="{{ $type->code }}" {{ request('invoice_type') == $type->code ? 'selected' : '' }}>
                                    {{ $type->description }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-12 filter-col">
                        <label class="form-label small fw-bold">LHDN Account</label>
                        <select name="connection_integrate" class="form-control" required>
                            <option value="">Please choose</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->connection_integrate }}" {{ request('connection_integrate')==$c->connection_integrate?'selected':'' }}>
                                    {{ $c->registration_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 col-12 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- 🚀 LIVE BACKGROUND PROGRESS BANNER --}}
    <div id="background-progress-banner">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h6 class="mb-0 fw-bold text-success" id="banner-title"><i class="fas fa-satellite-dish fa-spin me-2"></i> Syncing with LHDN...</h6>
                <small class="text-muted" id="progress-text">Processing background batches. You can continue using the portal.</small>
            </div>
            <div class="text-end d-flex align-items-center gap-3">
                <div>
                    <div class="fw-bold" id="progress-percentage">0%</div>
                </div>
                <button id="stopSyncBtn" class="btn btn-sm btn-danger shadow-sm"><i class="fas fa-times me-1"></i> Stop Sync</button>
            </div>
        </div>
        <div class="progress" style="height: 12px; border-radius: 10px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="progress-bar-fill" role="progressbar" style="width: 0%"></div>
        </div>
        <div class="mt-2 small text-muted d-flex justify-content-between">
             <span><i class="fas fa-layer-group me-1"></i> Remaining: <span id="batches-left-text" class="fw-bold">0</span> batches</span>
             <span><i class="fas fa-history me-1"></i> Updates live in background</span>
        </div>
    </div>

    {{-- Action Buttons Row --}}
    <div class="mb-3 d-flex gap-2 align-items-center action-buttons-wrapper">
        <button class="btn btn-success" id="submitSelectedBtn"><i class="fas fa-paper-plane me-1"></i> Submit Selected</button>
        <button class="btn btn-outline-warning" id="bulkCancelSelectedBtn"><i class="fas fa-ban me-1"></i> Cancel Selected</button>
        <button class="btn btn-danger" id="bulkDeleteSelectedBtn"><i class="fas fa-trash-alt me-1"></i> Delete Selected</button>
    </div>

    {{-- Table Card --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoiceTable" class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th width="40" class="text-center"><input type="checkbox" id="select-all"></th>
                            <th>Invoice ID</th>
                            <th>Sale ID</th>
                            <th>Invoice Type</th>
                            <th>Customer</th>
                            <th>Amount (RM)</th>
                            <th>Date</th>
                            <th>LHDN Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- 🚀 Render using our strictly filtered array --}}
                        @if(request()->filled('connection_integrate') && !empty($filteredInvoices))
                            @foreach($filteredInvoices as $inv)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="select-item" value="{{ $inv->id_invoice }}" data-unique-id="{{ $inv->unique_id }}">
                                        <input type="hidden" class="supplier-id" value="{{ $inv->id_supplier }}">
                                    </td>
                                    <td class="fw-bold text-nowrap">{{ $inv->invoice_no }}</td>
                                    <td class="text-nowrap">{{ $inv->sale_id }}</td>
                                    <td>{{ $inv->invoice_type_name ?? '-' }}</td>
                                    <td>{{ $inv->registration_name }}</td>
                                    @php
                                        $total = $inv->taxable_amount + $inv->tax_amount;
                                        $rawStatus = strtoupper(trim($inv->submission_status ?: 'PENDING'));
                                        
                                        // Standardize status for the badge display
                                        if($rawStatus === 'SUBMITTED' || $rawStatus === 'ACCEPTED') $status = 'SUBMITTED';
                                        elseif($rawStatus === 'FAILED' || $rawStatus === 'REJECTED' || $rawStatus === 'ERROR') $status = 'FAILED';
                                        else $status = 'PENDING';

                                        $map = ['SUBMITTED'=>'primary','FAILED'=>'danger','PENDING'=>'warning'];
                                    @endphp
                                    <td class="text-nowrap">{{ number_format($total ?? 0, 2) }}</td>
                                    <td class="text-nowrap">{{ \Carbon\Carbon::parse($inv->issue_date)->format('d-m-Y H:i:s') }}</td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-{{ $map[$status] ?? 'secondary' }}">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <div class="btn-group">
                                            <a href="{{ route('invoice.view.public', $inv->unique_id) }}" target="_blank" class="btn btn-sm btn-info text-white">View</a>
                                            @if($status === 'SUBMITTED')
                                                <button class="btn btn-sm btn-warning text-white" onclick="cancelDocument('{{ $inv->unique_id }}')">Cancel</button>
                                            @endif
                                            @if($status !== 'SUBMITTED')
                                                <button class="btn btn-sm btn-danger" onclick="confirmDelete('{{ $inv->id_invoice }}')">Delete</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    let invoiceTypes = @json($invoiceTypes ?? []);
    let exportRoute = "{{ route('developer.invoices.export') }}";
    let isPinging = false; 
    let cardAjaxInterval = null;
    let forceStop = false; 

    // --- 1. INITIALIZE CARD COUNTS ---
    window.statusCounts = {
        Submitted: {{ $scSubmitted }},
        Pending: {{ $scPending }},
        Failed: {{ $scFailed }}
    };

    // --- 2. AUTO-RESUME ON REFRESH ---
    let savedBatchId = localStorage.getItem('active_batch_id');
    let savedTotal = localStorage.getItem('batch_total_count');
    let savedIds = localStorage.getItem('active_invoice_ids');

    if (savedBatchId && savedIds) {
        resumeSync(savedBatchId, savedTotal, JSON.parse(savedIds));
    }

    function resumeSync(batchId, total, ids) {
        $("#background-progress-banner").show();
        $("#progress-text").text("Resuming background sync...");
        $("#progress-bar-fill").addClass('progress-bar-animated progress-bar-striped');
        
        startLiveCardUpdates(); 
        startMultiPinger(batchId, total, ids);
    }

    // --- 3. EXPORT BUTTONS ---
    let dropdownButtons = [
        {
            text: '<i class="fa-solid fa-square-check me-2 text-primary"></i> Export Selected',
            className: 'dropdown-item py-2 fw-semibold',
            action: function (e, dt, node, config) {
                let selected = [];
                dt.rows({ search: 'applied' }).every(function() {
                    let checkbox = $(this.node()).find('input.select-item');
                    if (checkbox.prop('checked')) selected.push(checkbox.val());
                });
                if (selected.length === 0) return Swal.fire("Oops", "Please select items to export.", "warning");
                let url = new URL(exportRoute);
                url.searchParams.append('ids', selected.join(','));
                window.location.href = url.toString();
            }
        },
        {
            text: '<i class="fa-solid fa-list me-2 text-info"></i> Export All',
            className: 'dropdown-item py-2 fw-semibold',
            action: function () {
                let url = new URL(exportRoute);
                url.searchParams.append('start_date', $('input[name="start_date"]').val());
                url.searchParams.append('end_date', $('input[name="end_date"]').val());
                url.searchParams.append('status', $('select[name="status"]').val());
                url.searchParams.append('connection_integrate', $('select[name="connection_integrate"]').val());
                window.location.href = url.toString();
            }
        }
    ];

    // --- 4. DATATABLE INIT ---
    var table = null;
    @if(request()->filled('connection_integrate') && !empty($filteredInvoices))
        table = $('#invoiceTable').DataTable({
            pageLength: 30,
            lengthMenu: [[10, 30, 50, 100, -1], [10, 30, 50, 100, "All"]],
            dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"<"dt-buttons-container"B><"d-flex gap-2"l<"dt-search-container"f>>>rt<"d-flex flex-wrap justify-content-between mt-3"ip>',
            buttons: [{ extend: 'collection', text: '<button class="btn btn-secondary dropdown-toggle">Export Data</button>', buttons: dropdownButtons }],
            columnDefs: [{ orderable: false, targets: [0, 8] }]
        });
    @endif

    // --- 5. EMERGENCY STOP ---
    $("#stopSyncBtn").on("click", function() {
        let activeBatchId = localStorage.getItem('active_batch_id');
        Swal.fire({
            title: 'Stop Sync?',
            text: "This will kill the queue and stop processing remaining batches.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, stop it!'
        }).then((result) => {
            if (result.isConfirmed) {
                forceStop = true; 
                stopLiveCardUpdates();
                $("#banner-title").html(`<i class="fas fa-spinner fa-spin me-2"></i> Stopping...`);
                
                $.post("{{ url('/api/stop-worker') }}", { _token: "{{ csrf_token() }}", batch_id: activeBatchId }, function() {
                    localStorage.clear();
                    location.reload(); 
                });
            }
        });
    });

    // --- 6. AUTO-RELAY & PINGER LOGIC ---
    function triggerRelay(actionType, selectedIds, isFirstRun = false) {
        let url = "{{ route('developer.invoices.submitSelected') }}";

        forceStop = false;
        $("#background-progress-banner").slideDown();
        
        if (!isFirstRun) {
            $("#banner-title").html(`<i class="fas fa-satellite-dish fa-spin me-2"></i> Syncing Next Batch...`);
            $("#progress-bar-fill").removeClass('bg-warning').addClass('bg-success progress-bar-animated progress-bar-striped').css('width', '0%');
        }

        $.post(url, {
            _token: "{{ csrf_token() }}",
            invoices: JSON.stringify(selectedIds), 
            connection_integrate: $("select[name='connection_integrate']").val()
        }, function(response) {
            if (response.success && response.batch_id) {
                
                localStorage.setItem('active_batch_id', response.batch_id);
                localStorage.setItem('active_invoice_ids', JSON.stringify(selectedIds));
                localStorage.setItem('active_action_type', actionType);
                localStorage.setItem('has_more', response.has_more ? '1' : '0');

                if (response.leftover_count > 0) {
                    $("#progress-text").text(`Processing up to 5,000 invoices (${response.leftover_count} remaining in queue...)`);
                } else {
                    $("#progress-text").text("Processing final batch.");
                }

                if (isFirstRun) startLiveCardUpdates();
                
                startMultiPinger(response.batch_id, selectedIds.length, selectedIds);
            
            } else if (response.success && !response.batch_id) {
                if (!isFirstRun) handleComplete({status: 'complete', has_failures: false});
            } else {
                Swal.fire("Error", response.message || "Failed to start batch.", "error");
            }
        }).fail(function() {
            Swal.fire("Error", "Network error starting batch.", "error");
        });
    }

    function startMultiPinger(batchId, totalCount, invoiceIds) {
        let isChecking = false;

        function checkProgress() {
            if (forceStop || isChecking) return;
            isChecking = true;

            let savedIds = JSON.parse(localStorage.getItem('active_invoice_ids') || '[]');

            $.post("{{ url('/api/check-batch') }}", { 
                _token: "{{ csrf_token() }}", 
                batch_id: batchId,
                invoice_ids: savedIds
            }).done(function(res) {
                
                // If a manual force stop occurred, break out
                if (res.has_failures && res.error_message && forceStop) {
                    isChecking = false;
                    return; 
                }

                let p = res.progress || 0;
                $("#progress-percentage").text(p + "%");
                $("#progress-bar-fill").css('width', p + "%");
                $("#batches-left-text").text(res.remaining_batch || 0);

                // 🚀 THE FIX IS RIGHT HERE: || res.is_cancelled guarantees the loop breaks!
                if (res.status === 'complete' || res.is_cancelled || p >= 100) {
                    
                    $("#banner-title").removeClass('text-success').addClass('text-info').html(`<i class="fas fa-database fa-spin me-2"></i> Fetching Error Logs...`);
                    $("#progress-text").text("Batch finished. Gathering error logs from the database...");
                    $("#progress-bar-fill").removeClass('bg-success progress-bar-animated').addClass('bg-info').css('width', '100%');
                    
                    setTimeout(() => {
                        $.post("{{ url('/api/check-batch') }}", { 
                            _token: "{{ csrf_token() }}", 
                            batch_id: batchId,
                            invoice_ids: savedIds 
                        }).done(function(finalRes) {
                            if(!finalRes.error_message && res.error_message) finalRes.error_message = res.error_message;
                            handleComplete(finalRes);
                        }).fail(function() {
                            handleComplete(res); 
                        });
                    }, 3500); 

                } else {
                    isChecking = false;
                    setTimeout(checkProgress, 2500); 
                }
            }).fail(function() {
                isChecking = false;
                setTimeout(checkProgress, 5000); 
            });
        }

        checkProgress();

        for (let i = 0; i < 3; i++) {
            runWorkerLoop();
        }
    }

    async function runWorkerLoop() {
        while (!forceStop) {
            let activeId = localStorage.getItem('active_batch_id');
            if (!activeId) break;

            try {
                await $.get("{{ url('/api/trigger-worker') }}", { _token: "{{ csrf_token() }}" });
                await new Promise(r => setTimeout(r, 1000));
            } catch (e) {
                await new Promise(r => setTimeout(r, 5000));
            }
        }
    }

    // 🚀 FULLY UPDATED COMPLETE HANDLER (WITH SMART COUNTING)
    function handleComplete(res) {
        let hasMore = localStorage.getItem('has_more') === '1';
        let actionType = localStorage.getItem('active_action_type');
        let savedIds = JSON.parse(localStorage.getItem('active_invoice_ids') || '[]');

        if (hasMore && !res.has_failures && !forceStop) {
            $("#banner-title").removeClass('text-success text-info').addClass('text-warning').html(`<i class="fas fa-sync fa-spin me-2"></i> Auto-starting next batch...`);
            $("#progress-text").text("Taking a 3-second breather before continuing...");
            $("#progress-percentage").text("...");
            $("#progress-bar-fill").css('width', '100%').removeClass('bg-success bg-info').addClass('bg-warning progress-bar-animated progress-bar-striped');

            setTimeout(() => {
                triggerRelay(actionType, savedIds, false);
            }, 3000);
            return; 
        }

        // 1. GATHER DATA METRICS
        let startTime = localStorage.getItem('batch_start_time');
        let totalCount = parseInt(localStorage.getItem('batch_total_count')) || 0;
        let totalRM = parseFloat(localStorage.getItem('batch_total_rm') || 0);
        let timeTaken = "Unknown";

        if (startTime) {
            let diff = Math.floor((Date.now() - parseInt(startTime)) / 1000);
            timeTaken = diff > 60 ? `${Math.floor(diff/60)}m ${diff%60}s` : `${diff}s`;
        }

        // 🚀 SMART COUNTING: Fail-safe to ensure counts match reality even if DB lags
        let successCount = parseInt(res.success_count) || 0;
        let failedCount = parseInt(res.failed_count) || 0;

        if (totalCount > 0 && (successCount + failedCount) === 0) {
            if (res.has_failures || res.is_cancelled) {
                failedCount = totalCount;
            } else {
                successCount = totalCount;
            }
        }

        localStorage.clear();
        stopLiveCardUpdates();

        // 2. BUILD THE UI TEMPLATE
        let modalHtml = `
            <div style="text-align: left; font-size: 15px; margin-top: 10px;">
                <p style="margin: 5px 0;"><strong>Total Processed:</strong> ${totalCount}</p>
                <p style="margin: 5px 0;"><strong>Total Amount:</strong> RM ${totalRM.toLocaleString()}</p>
                <p style="margin: 5px 0;"><strong>Total Time:</strong> ${timeTaken}</p>
                <hr style="margin: 10px 0; border-color: #e5e7eb;">
                <p style="margin: 5px 0; color: #10b981;"><strong><i class="fas fa-check-circle me-1"></i> Successful:</strong> ${successCount}</p>
                <p style="margin: 5px 0; color: #ef4444;"><strong><i class="fas fa-times-circle me-1"></i> Failed:</strong> ${failedCount}</p>
            </div>
        `;

        // 3. ATTACH THE ERROR REMINDER BOX IF FAILURES OCCURRED
        if (res.has_failures || failedCount > 0 || res.is_cancelled) {
            let errorMsg = res.error_message ? res.error_message : "Invoices rejected by LHDN API. Check validation requirements.";
            
            modalHtml += `
                <div style="margin-top: 15px; padding: 12px; background-color: #fee2e2; border-left: 4px solid #ef4444; border-radius: 4px; color: #991b1b; text-align: left; font-size: 13px; max-height: 250px; overflow-y: auto;">
                    <strong>Specific Failure Reasons:</strong><br>
                    <div style="margin-top: 8px;">${errorMsg}</div>
                </div>
                <p style="margin-top: 15px; font-size: 13px; color: #6b7280; text-align: left; line-height: 1.4;">
                    <em>Reminder: The valid invoices were safely submitted. Please fix the data for the failed invoices and resubmit them.</em>
                </p>
            `;
        }

        // 4. FIRE THE SWEETALERT
        Swal.fire({
            icon: res.has_failures || failedCount > 0 || res.is_cancelled ? 'warning' : 'success',
            title: res.has_failures || failedCount > 0 || res.is_cancelled ? 'Finished with Errors' : 'Sync Complete!',
            html: modalHtml,
            confirmButtonText: 'Refresh Page',
            confirmButtonColor: '#6366f1',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.reload(); 
            }
        });
    }

    // --- 7. CORE SUBMISSION BOOTSTRAP ---
    async function processInvoices(actionType) {
        let selected = [];
        let totalPrice = 0;
        let $checkboxes = (table !== null) ? table.$(".select-item:checked") : $(".select-item:checked");
        
        $checkboxes.each(function() {
            selected.push($(this).val());
            let amountText = $(this).closest("tr").find("td:nth-child(6)").text().replace(/[^\d.-]/g, '');
            totalPrice += parseFloat(amountText) || 0;
        });

        if (selected.length === 0) return Swal.fire("Warning", "No invoices selected", "warning");

        Swal.fire({
            title: "Start LHDN Sync?",
            html: `Queueing ${selected.length} invoices (RM ${totalPrice.toLocaleString()})`,
            showCancelButton: true,
            confirmButtonText: "Start Sync"
        }).then((res) => {
            if (!res.isConfirmed) return;

            localStorage.setItem('batch_start_time', Date.now());
            localStorage.setItem('batch_total_count', selected.length);
            localStorage.setItem('batch_total_rm', totalPrice);
            
            triggerRelay(actionType, selected, true);
        });
    }

    // --- 8. UI HELPERS ---
    function startLiveCardUpdates() {
        if (cardAjaxInterval) clearInterval(cardAjaxInterval);
        cardAjaxInterval = setInterval(() => {
            $.get(window.location.href, function(data) {
                let $html = $(data);
                $('#count-submitted').text($html.find('#count-submitted').text());
                $('#count-pending').text($html.find('#count-pending').text());
                $('#count-failed').text($html.find('#count-failed').text());
            });
        }, 5000);
    }

    function stopLiveCardUpdates() { clearInterval(cardAjaxInterval); }

    $("#submitSelectedBtn").on("click", () => processInvoices('submit'));
    
    // Status Card click logic strictly tied to the dropdown value
    $('.status-card').on('click', function() {
        $('#statusFilter').val($(this).data('status'));
        $('#searchForm').submit();
    });

    $("#select-all").on("click", function() {
        let isChecked = this.checked;
        if (table !== null) table.rows().nodes().to$().find(".select-item").prop('checked', isChecked);
        else $(".select-item").prop('checked', isChecked);
    });

    // --- 9. CANCEL & DELETE FUNCTIONALITY ---
    window.cancelDocument = function(uniqueId) {
        Swal.fire({
            title: "Cancel Document?",
            text: "Are you sure you want to cancel this document in LHDN?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            confirmButtonText: "Yes, Cancel"
        }).then((res) => {
            if (res.isConfirmed) {
                $.post("{{ route('developer.invoices.cancel') }}", { _token: "{{ csrf_token() }}", unique_id: uniqueId }, function(response) {
                    if(response.success) Swal.fire("Cancelled", "Document cancelled successfully.", "success").then(()=>location.reload());
                    else Swal.fire("Error", response.message || "Failed to cancel document.", "error");
                });
            }
        });
    };

    window.confirmDelete = function(invoiceId) {
        Swal.fire({
            title: "Delete Invoice?",
            text: "Are you sure you want to delete this invoice? This cannot be undone.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: "Yes, Delete"
        }).then((res) => {
            if (res.isConfirmed) {
                $.post("{{ route('developer.invoices.delete') }}", { _token: "{{ csrf_token() }}", id_invoice: invoiceId }, function(response) {
                    if(response.success) Swal.fire("Deleted", "Invoice deleted successfully.", "success").then(()=>location.reload());
                    else Swal.fire("Error", response.message || "Failed to delete invoice.", "error");
                });
            }
        });
    };

    function handleBulkAction(url, title, text, confirmColor, btnText, useUniqueId = false) {
        let selected = [];
        let $checkboxes = (table !== null) ? table.$(".select-item:checked") : $(".select-item:checked");
        
        $checkboxes.each(function() {
            selected.push(useUniqueId ? $(this).data('unique-id') : $(this).val());
        });

        if (selected.length === 0) return Swal.fire("Warning", "No invoices selected.", "warning");

        Swal.fire({
            title: title,
            text: text.replace(':count', selected.length),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            confirmButtonText: btnText
        }).then((res) => {
            if (res.isConfirmed) {
                $.post(url, { _token: "{{ csrf_token() }}", ids: selected }, function(response) {
                    if (response.success) Swal.fire("Success", response.message || "Action completed.", "success").then(() => location.reload());
                    else Swal.fire("Error", response.message || "Action failed.", "error");
                }).fail(() => Swal.fire("Error", "Server or network error.", "error"));
            }
        });
    }

    $("#bulkCancelSelectedBtn").on("click", () => handleBulkAction("{{ route('developer.invoices.bulkCancel') }}", "Cancel Selected?", "You are about to cancel :count selected documents in LHDN.", "#f39c12", "Yes, Cancel", true));
    $("#bulkDeleteSelectedBtn").on("click", () => handleBulkAction("{{ route('developer.invoices.bulkDelete') }}", "Delete Selected?", "You are about to delete :count selected invoices.", "#d33", "Yes, Delete", false));
});
</script>
@endsection