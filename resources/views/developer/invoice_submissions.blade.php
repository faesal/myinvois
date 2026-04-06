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
    .status-card.active { border: 2px solid #333; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
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

<div class="container-fluid">
    <h3 class="mb-4">Invoice Submissions</h3>

    {{-- Status Cards --}}
    <div class="row mb-4">
        <div class="col-md-4 col-12 mb-3 mb-md-0">
            <div class="status-card card-submitted" data-status="Submitted" id="card-submitted">
                <div class="card-title"><i class="fas fa-check-circle me-2"></i>Submitted</div>
                <h2 class="card-count" id="count-submitted">0</h2>
            </div>
        </div>
        <div class="col-md-4 col-12 mb-3 mb-md-0">
            <div class="status-card card-pending" data-status="Pending" id="card-pending">
                <div class="card-title"><i class="fas fa-clock me-2"></i>Pending</div>
                <h2 class="card-count" id="count-pending">0</h2>
            </div>
        </div>
        <div class="col-md-4 col-12 mb-3 mb-md-0">
            <div class="status-card card-failed" data-status="Failed" id="card-failed">
                <div class="card-title"><i class="fas fa-times-circle me-2"></i>Failed</div>
                <h2 class="card-count" id="count-failed">0</h2>
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
                    <small class="text-success fw-bold" id="time-remaining-text">Est: Calculating...</small>
                </div>
                {{-- NEW STOP BUTTON --}}
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
        <button class="btn btn-warning text-white" id="resubmitSelectedBtn"><i class="fas fa-sync me-1"></i> Resubmit Selected</button>
        <button class="btn btn-outline-danger" id="bulkCancelSelectedBtn"><i class="fas fa-ban me-1"></i> Cancel Selected</button>
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
                        @if(request()->filled('connection_integrate') && $invoices->isNotEmpty())
                            @foreach($invoices as $inv)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="select-item" value="{{ $inv->id_invoice }}">
                                        <input type="hidden" class="supplier-id" value="{{ $inv->id_supplier }}">
                                    </td>
                                    <td class="fw-bold text-nowrap">{{ $inv->invoice_no }}</td>
                                    <td class="text-nowrap">{{ $inv->sale_id }}</td>
                                    <td>{{ $inv->invoice_type_name ?? '-' }}</td>
                                    <td>{{ $inv->registration_name }}</td>
                                    @php
                                        $total = $inv->taxable_amount + $inv->tax_amount;
                                        $status = strtoupper(trim($inv->submission_status ?: 'PENDING'));
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

<script>
    window.statusCounts = @json($statusCounts ?? ['Submitted' => 0, 'Pending' => 0, 'Failed' => 0]);
</script>

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
    let invoiceTypes = @json($invoiceTypes);
    let exportRoute = "{{ route('developer.invoices.export') }}";
    let isPinging = false; 
    let cardAjaxInterval = null;
    let forceStop = false; // Emergency kill switch flag

    // --- 1. INITIALIZE CARD COUNTS ---
    if (window.statusCounts) {
        $('#count-submitted').text(window.statusCounts.Submitted || 0);
        $('#count-pending').text(window.statusCounts.Pending || 0);
        $('#count-failed').text(window.statusCounts.Failed || 0);
    }

    // --- 2. AUTO-RESUME ON REFRESH ---
    let savedTotalBatches = localStorage.getItem('total_batches');
    if (savedTotalBatches && parseInt(savedTotalBatches) > 0) {
        $("#background-progress-banner").show();
        $("#progress-text").text("Resuming background sync with LHDN...");
        $("#progress-bar-fill").addClass('progress-bar-animated progress-bar-striped');
        
        startLiveCardUpdates(); 
        pingWorker(parseInt(savedTotalBatches));
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
    @if(request()->filled('connection_integrate') && $invoices->isNotEmpty())
        table = $('#invoiceTable').DataTable({
            pageLength: 30,
            lengthMenu: [[10, 30, 50, 100, -1], [10, 30, 50, 100, "All"]],
            dom: '<"d-flex flex-wrap justify-content-between align-items-center mb-3"<"dt-buttons-container"B><"d-flex gap-2"l<"dt-search-container"f>>>rt<"d-flex flex-wrap justify-content-between mt-3"ip>',
            buttons: [{ extend: 'collection', text: '<button class="btn btn-secondary dropdown-toggle">Export Data</button>', buttons: dropdownButtons }],
            columnDefs: [{ orderable: false, targets: [0, 8] }]
        });
        
        let currentStatus = "{{ request('status', 'ALL') }}";
        if (currentStatus !== 'ALL') {
            $('.status-card[data-status="' + currentStatus + '"]').addClass('active');
        }
    @endif

    // --- 5. EVENT HANDLERS ---
    $('.status-card').on('click', function() {
        $('#statusFilter').val($(this).data('status'));
        $('#searchForm').submit();
    });

    $("#select-all").on("click", function() {
        let isChecked = this.checked;
        if (table !== null) table.rows().nodes().to$().find(".select-item").prop('checked', isChecked);
        else $(".select-item").prop('checked', isChecked);
    });

    // =========================================================================
    // EMERGENCY STOP HANDLER
    // =========================================================================
    $("#stopSyncBtn").on("click", function() {
        Swal.fire({
            title: 'Stop Sync?',
            text: "This will instantly kill the queue and stop processing any remaining batches.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, stop it!'
        }).then((result) => {
            if (result.isConfirmed) {
                forceStop = true; // Stop the local JS loop
                stopLiveCardUpdates();
                
                // Show stopping UI
                $("#banner-title").html(`<i class="fas fa-spinner fa-spin me-2"></i> Stopping...`);
                $("#progress-bar-fill").removeClass('bg-success').addClass('bg-danger');

                // Hit backend to truncate jobs table
                $.ajax({
                    url: "{{ url('/api/stop-worker') }}",
                    method: "POST",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function() {
                        localStorage.removeItem('total_batches');
                        $("#background-progress-banner").slideUp();
                        Swal.fire("Stopped", "Background sync has been aborted.", "info");
                        setTimeout(() => location.reload(), 1500); // Reload to reflect correct DB state
                    }
                });
            }
        });
    });

    // =========================================================================
    // LIVE AJAX CARD UPDATER
    // =========================================================================
    function startLiveCardUpdates() {
        if (cardAjaxInterval) clearInterval(cardAjaxInterval);
        cardAjaxInterval = setInterval(() => {
            $.ajax({
                url: window.location.href,
                method: "GET",
                cache: false,
                success: function(data) {
                    let $html = $(data);
                    let newSub = $html.find('#count-submitted').text().trim();
                    let newPen = $html.find('#count-pending').text().trim();
                    let newFail = $html.find('#count-failed').text().trim();

                    if($('#count-submitted').text() !== newSub) $('#count-submitted').fadeOut(100, function(){ $(this).text(newSub).fadeIn(100); });
                    if($('#count-pending').text() !== newPen) $('#count-pending').fadeOut(100, function(){ $(this).text(newPen).fadeIn(100); });
                    if($('#count-failed').text() !== newFail) $('#count-failed').fadeOut(100, function(){ $(this).text(newFail).fadeIn(100); });
                }
            });
        }, 2000); 
    }

    function stopLiveCardUpdates() {
        if (cardAjaxInterval) clearInterval(cardAjaxInterval);
    }

    // =========================================================================
    // BACKGROUND QUEUE MANAGER
    // =========================================================================
    async function processInvoices(actionType) {
        let selected = [];
        let totalPrice = 0;
        let supplierCheck = null;
        let supplierMismatch = false;

        let $checkboxes = (table !== null) ? table.$(".select-item:checked") : $(".select-item:checked");
        
        $checkboxes.each(function() {
            let row = $(this).closest("tr");
            selected.push($(this).val());
            let amountText = row.find("td:nth-child(6)").text().replace(/[^\d.-]/g, '');
            totalPrice += parseFloat(amountText) || 0;
            let supplierId = row.find(".supplier-id").val();
            if (supplierCheck === null) supplierCheck = supplierId;
            else if (supplierId && supplierCheck !== supplierId) supplierMismatch = true;
        });

        if (selected.length === 0) return Swal.fire({ icon: "warning", title: "No invoices selected" });
        if (supplierMismatch) return Swal.fire({ icon: "error", title: "Supplier mismatch" });

        let url = actionType === 'resubmit' ? "{{ url('api/invoices/bulk-resubmit') }}" : "{{ route('developer.invoices.submitSelected') }}";

        Swal.fire({
            icon: "info",
            title: "Confirm MySyncTax Sync",
            html: `<b>Total:</b> ${selected.length} invoices<br><b>Total Amount:</b> RM ${totalPrice.toLocaleString(undefined, {minimumFractionDigits: 2})}<br><br><small class="text-muted">This will run in the background. You can navigate away safely.</small>`,
            showCancelButton: true,
            confirmButtonText: "Start Sync",
            confirmButtonColor: "#22c55e"
        }).then((res) => {
            if (!res.isConfirmed) return;

            forceStop = false; // Reset emergency stop flag
            
            $("#progress-text").text("Queuing invoices to MySyncTax server...");
            $("#progress-percentage").text("0%");
            $("#progress-bar-fill").css('width', "0%").removeClass('bg-danger').addClass('bg-success progress-bar-animated progress-bar-striped');
            $("#batches-left-text").text("...");
            $("#time-remaining-text").text("Est: Calculating...");
            $("#background-progress-banner").stop(true, true).slideDown();

            $.ajax({
                url: url,
                method: "POST",
                data: { _token: "{{ csrf_token() }}", invoices: selected, connection_integrate: $("select[name='connection_integrate']").val(), id_supplier: supplierCheck, mode: actionType },
                success: function(response) {
                    if (response.success && parseInt(response.total_batches) > 0) {
                        let totalBatches = parseInt(response.total_batches);
                        localStorage.setItem('total_batches', totalBatches);
                        $("#progress-text").html(`Processing batches with LHDN...`);
                        
                        startLiveCardUpdates(); 
                        pingWorker(totalBatches);
                    } else {
                        $("#background-progress-banner").slideUp();
                        Swal.fire("Notice", response.message || "No batches queued.", "info");
                    }
                },
                error: function() {
                    $("#background-progress-banner").slideUp();
                    Swal.fire("Error", "Failed to reach the server.", "error");
                }
            });
        });
    }

    function pingWorker(initialTotalBatches) {
        if (isPinging || forceStop) return; // Halt if stop button was clicked
        isPinging = true;

        $.ajax({
            url: "{{ url('/api/trigger-worker') }}", 
            method: "POST",
            data: { _token: "{{ csrf_token() }}" },
            success: function(res) {
                isPinging = false;
                if (forceStop) return; // Prevent race conditions
                
                let remaining = parseInt(res.remaining) || 0;

                if (res.status === 'complete' || remaining === 0) {
                    localStorage.removeItem('total_batches');
                    stopLiveCardUpdates(); 
                    
                    $("#progress-percentage").text("100%");
                    $("#progress-bar-fill").css('width', "100%").removeClass('progress-bar-animated');
                    $("#progress-text").html(`<span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Sync Completed Successfully!</span>`);
                    $("#time-remaining-text").text("Done");
                    $("#batches-left-text").text("0");

                    setTimeout(() => { $("#background-progress-banner").slideUp(); }, 4000);
                } else {
                    let completed = initialTotalBatches - remaining;
                    
                    // If the backend isn't processing jobs, warn the user
                    if (completed === 0) {
                        $("#time-remaining-text").text(`Stuck? Check error logs or hit Stop Sync.`);
                    }

                    if (completed < 0) {
                        initialTotalBatches = remaining;
                        localStorage.setItem('total_batches', initialTotalBatches);
                        completed = 0;
                    }
                    
                    let percent = Math.min(Math.round((completed / initialTotalBatches) * 100), 99);
                    let secondsLeft = Math.ceil(remaining * 1.5);
                    let timeStr = secondsLeft > 60 ? `${Math.floor(secondsLeft/60)}m ${secondsLeft%60}s` : `${secondsLeft}s`;

                    // Move progress bar
                    if(percent > 0) {
                        $("#progress-percentage").text(`${percent}%`);
                        $("#progress-bar-fill").css('width', `${percent}%`);
                        $("#time-remaining-text").text(`Est: ${timeStr} left`);
                    }
                    $("#batches-left-text").text(remaining);
                    
                    setTimeout(() => { pingWorker(initialTotalBatches); }, 500); 
                }
            },
            error: function() {
                isPinging = false;
                if (!forceStop) {
                    $("#progress-text").html(`<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> Network lag. Retrying...</span>`);
                    setTimeout(() => pingWorker(initialTotalBatches), 3000);
                }
            }
        });
    }

    // --- DELETE / CANCEL HELPERS ---
    $("#bulkDeleteSelectedBtn").on("click", function(e) { 
        e.preventDefault(); 
        let selected = [];
        let $checkboxes = (table !== null) ? table.$(".select-item:checked") : $(".select-item:checked");
        $checkboxes.each(function() { selected.push($(this).val()); });

        if (selected.length === 0) return Swal.fire({ icon: "warning", title: "No invoices selected" });
        Swal.fire({ title: 'Delete Selected?', text: "Hide record from MySyncTax dashboard?", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' })
        .then((result) => {
            if (result.isConfirmed) {
                $.ajax({ url: "{{ route('developer.invoices.bulkDelete') }}", method: "POST", data: { _token: "{{ csrf_token() }}", invoices: selected }, success: function() { location.reload(); }});
            }
        });
    }); 
    
    $("#submitSelectedBtn").on("click", function(e) { e.preventDefault(); processInvoices('submit'); });
    $("#resubmitSelectedBtn").on("click", function(e) { e.preventDefault(); processInvoices('resubmit'); });
});

window.confirmDelete = function(id) {
    Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true }).then((r) => {
        if (r.isConfirmed) window.location.href = "{{ route('developer.invoices.delete', '') }}/" + id;
    });
};

window.cancelDocument = function(uniqueId) {
    Swal.fire({ title: 'Cancel Document?', icon: 'warning', input: 'select', inputOptions: { 'wrong_data': 'Incorrect Data', 'duplicate': 'Duplicate', 'order_cancelled': 'Cancelled', 'other': 'Other' }, showCancelButton: true, inputValidator: (v) => { if (!v) return 'Required!' }
    }).then((r) => {
        if (r.isConfirmed) {
            $.ajax({ url: "{{ url('/api/myinvois/cancelDocument') }}/" + uniqueId, method: "POST", data: { _token: "{{ csrf_token() }}", reason: r.value }, beforeSend: function() { Swal.fire({ title: 'Processing...', didOpen: () => Swal.showLoading() }); }, success: function() { location.reload(); }});
        }
    });
};
</script>
@endsection