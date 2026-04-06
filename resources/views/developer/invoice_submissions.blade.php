@extends('layouts.developerLayout')

@section('content')

<style>
    /* Styling for the DataTables toolbar to match your portal aesthetic */
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
    }
    
    .status-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .status-card.active {
        border: 2px solid #333;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .status-card .card-title {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-card .card-count {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
    }
    
    .status-card.card-submitted {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .status-card.card-pending {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }
    
    .status-card.card-failed {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
    }
    
    @media (max-width: 768px) {
        .filter-col { margin-bottom: 15px; }
        .badge { width: 100%; padding: 8px !important; font-size: 0.75rem !important; }
        .btn-info, .btn-warning, .btn-danger, .resubmit-btn { width: 100%; }
        .status-card { margin-bottom: 15px; }
        .status-card .card-count { font-size: 2rem; }
    }
</style>

<div class="container-fluid">
    <h3 class="mb-4">Invoice Submissions</h3>

    {{-- Status Cards --}}
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="status-card card-submitted" data-status="Submitted" id="card-submitted">
                <div class="card-title">
                    <i class="fas fa-check-circle me-2"></i>Submitted
                </div>
                <h2 class="card-count" id="count-submitted">0</h2>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="status-card card-pending" data-status="Pending" id="card-pending">
                <div class="card-title">
                    <i class="fas fa-clock me-2"></i>Pending
                </div>
                <h2 class="card-count" id="count-pending">0</h2>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="status-card card-failed" data-status="Failed" id="card-failed">
                <div class="card-title">
                    <i class="fas fa-times-circle me-2"></i>Failed
                </div>
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

    {{-- Action Buttons Row --}}
    <div class="mb-3 d-flex gap-2 align-items-center">
        <button class="btn btn-success" id="submitSelectedBtn">Submit Selected</button>
        <button class="btn btn-warning text-white" id="resubmitSelectedBtn">Resubmit Selected</button>
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
                                    <td class="fw-bold">{{ $inv->invoice_no }}</td>
                                    <td>{{ $inv->sale_id }}</td>
                                    <td>{{ $inv->invoice_type_name ?? '-' }}</td>
                                    <td>{{ $inv->registration_name }}</td>
                                    @php
                                        $total = $inv->taxable_amount + $inv->tax_amount;
                                        $status = strtoupper(trim($inv->submission_status ?: 'PENDING'));
                                        $map = ['SUBMITTED'=>'primary','FAILED'=>'danger','PENDING'=>'warning'];
                                    @endphp
                                    <td>{{ number_format($total ?? 0, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('d-m-Y H:i:s') }}</td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-{{ $map[$status] ?? 'secondary' }}">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td class="text-center">
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

{{-- Hidden data for JavaScript --}}
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

    // Update card counts from server data
    if (window.statusCounts) {
        $('#count-submitted').text(window.statusCounts.Submitted || 0);
        $('#count-pending').text(window.statusCounts.Pending || 0);
        $('#count-failed').text(window.statusCounts.Failed || 0);
    }

    // --- 1. Define Export Dropdown Buttons ---
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
            text: '<i class="fa-solid fa-list me-2 text-info"></i> Export All (Current Search)',
            className: 'dropdown-item py-2 fw-semibold',
            action: function () {
                let url = new URL(exportRoute);
                url.searchParams.append('start_date', $('input[name="start_date"]').val());
                url.searchParams.append('end_date', $('input[name="end_date"]').val());
                url.searchParams.append('status', $('select[name="status"]').val());
                url.searchParams.append('connection_integrate', $('select[name="connection_integrate"]').val());
                url.searchParams.append('invoice_type', $('select[name="invoice_type"]').val());
                window.location.href = url.toString();
            }
        },
        {
            text: '<span class="text-muted small fw-bold mt-2 d-block px-3 border-top pt-2">EXPORT BY TYPE</span>',
            className: 'dropdown-item disabled'
        }
    ];

    invoiceTypes.forEach(function(type) {
        dropdownButtons.push({
            text: 'All ' + type.description + 's',
            className: 'dropdown-item py-2',
            action: function() {
                let url = new URL(exportRoute);
                url.searchParams.append('invoice_type_code', type.code);
                url.searchParams.append('start_date', $('input[name="start_date"]').val());
                url.searchParams.append('end_date', $('input[name="end_date"]').val());
                url.searchParams.append('connection_integrate', $('select[name="connection_integrate"]').val());
                window.location.href = url.toString();
            }
        });
    });

    // --- 2. Initialize DataTable with Pagination Options ---
    @if(request()->filled('connection_integrate') && $invoices->isNotEmpty())
        var table = $('#invoiceTable').DataTable({
            pageLength: 30,
            lengthMenu: [[10, 30, 50, 100, -1], [10, 30, 50, 100, "All"]],
            dom: '<"d-flex justify-content-between align-items-center mb-3"<"dt-buttons-container"B><"d-flex gap-2"l<"dt-search-container"f>>>rt<"d-flex justify-content-between mt-3"ip>',
            buttons: [
                {
                    extend: 'collection',
                    text: '<button class="btn btn-secondary dropdown-toggle">Export Data</button>',
                    buttons: dropdownButtons
                }
            ],
            columnDefs: [{ orderable: false, targets: [0, 8] }],
            language: {
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ invoices",
                infoEmpty: "Showing 0 to 0 of 0 invoices",
                infoFiltered: "(filtered from _MAX_ total invoices)"
            }
        });

        // Highlight active status card based on current filter
        let currentStatus = "{{ request('status', 'ALL') }}";
        if (currentStatus !== 'ALL') {
            $('.status-card[data-status="' + currentStatus + '"]').addClass('active');
        }
    @endif

    // --- 3. Status Card Click Handler ---
    $('.status-card').on('click', function() {
        let status = $(this).data('status');
        
        // Update dropdown
        $('#statusFilter').val(status);
        
        // Highlight clicked card
        $('.status-card').removeClass('active');
        $(this).addClass('active');
        
        // Submit form
        $('#searchForm').submit();
    });

    // --- 4. Unified Select All Logic ---
    $("#select-all").on("click", function() {
        let isChecked = this.checked;
        if ($.fn.DataTable.isDataTable('#invoiceTable')) {
            table.$(".select-item").prop('checked', isChecked);
        } else {
            $(".select-item").prop('checked', isChecked);
        }
    });

 // Helper function to create a delay (to respect LHDN rate limits)
const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

// --- 5. Submission & Resubmission Logic (Sequential 1-by-1) ---
async function processInvoices(actionType) {
    let selected = [];
    let totalPrice = 0;
    let supplierCheck = null;
    let supplierMismatch = false;

    let $checkboxes = $.fn.DataTable.isDataTable('#invoiceTable') 
        ? table.$(".select-item:checked") 
        : $(".select-item:checked");

    $checkboxes.each(function() {
        let row = $(this).closest("tr");
        let id = $(this).val();
        let amountText = row.find("td:nth-child(6)").text().trim(); 
        let amount = parseFloat(amountText.replace(/,/g, '')) || 0;
        let supplierId = row.find(".supplier-id").val();

        if (supplierCheck === null) {
            supplierCheck = supplierId;
        } else if (supplierId && supplierCheck !== supplierId) {
            supplierMismatch = true;
        }

        selected.push(id);
        totalPrice += amount;
    });

    if (selected.length === 0) return Swal.fire({ icon: "warning", title: "No invoices selected" });
    if (supplierMismatch) return Swal.fire({ icon: "error", title: "Supplier mismatch", text: "Invoices must be from the same supplier." });

    let config = actionType === 'resubmit' 
        ? { title: "Confirm Resubmission", btn: "Resubmit Now", color: "#f1c40f", url: "{{ url('api/invoices/bulk-resubmit') }}" }
        : { title: "Confirm Submission", btn: "Submit Now", color: "#22c55e", url: "{{ route('developer.invoices.submitSelected') }}" };

    Swal.fire({
        icon: "info",
        title: config.title,
        html: `<b>Total:</b> ${selected.length} invoices<br><b>Amount:</b> RM ${totalPrice.toFixed(2)}<br><br><small class="text-muted">Invoices will be processed sequentially.</small>`,
        showCancelButton: true,
        confirmButtonText: config.btn,
        confirmButtonColor: config.color,
    }).then(async (res) => {
        if (!res.isConfirmed) return;

        let total = selected.length;
        let successCount = 0;
        let failCount = 0;

        // Open the loading alert
        Swal.fire({
            title: `Processing 1 of ${total}...`,
            html: "Please wait. Do not close or refresh this page.",
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // Loop through each selected invoice ONE BY ONE
        for (let i = 0; i < total; i++) {
            let currentInvoiceId = selected[i];

            // Update SweetAlert text so the user sees real-time progress
            Swal.update({
                title: `Processing ${i + 1} of ${total}...`
            });

            try {
                // Wait for this specific AJAX request to finish before moving to the next
                let response = await $.ajax({
                    url: config.url,
                    method: "POST",
                    data: { 
                        _token: "{{ csrf_token() }}", 
                        invoices: [currentInvoiceId], // Send exactly 1 invoice in the array
                        connection_integrate: $("select[name='connection_integrate']").val(), 
                        id_supplier: supplierCheck, 
                        mode: actionType 
                    }
                });
                
                successCount++;

            } catch (xhr) {
                console.error(`Invoice ${currentInvoiceId} failed:`, xhr);
                failCount++;
            }

            // Optional but HIGHLY recommended: Pause for 1 second between requests 
            // so LHDN doesn't block the frontend for spamming the API too fast
            if (i < total - 1) {
                await sleep(1000); 
            }
        }

        // Final Result Alert
        let finalIcon = failCount === 0 ? "success" : (successCount === 0 ? "error" : "warning");
        let finalTitle = failCount === 0 ? "All Complete!" : "Completed with Errors";
        let finalText = `Successfully submitted: <b>${successCount}</b><br>Failed: <b>${failCount}</b>`;

        Swal.fire({ 
            icon: finalIcon, 
            title: finalTitle, 
            html: finalText, 
            confirmButtonText: "Refresh Page"
        }).then(() => { 
            location.reload(); 
        });

    });
}
    $("#submitSelectedBtn").on("click", () => processInvoices('submit'));
    $("#resubmitSelectedBtn").on("click", () => processInvoices('resubmit'));
});

// Confirmation Logic (Delete/Cancel)
window.confirmDelete = function(id) {
    Swal.fire({ title: 'Are you sure?', text: "Remove invoice record?", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' })
    .then((r) => { if (r.isConfirmed) window.location.href = "{{ route('developer.invoices.delete', '') }}/" + id; });
};

window.cancelDocument = function(uniqueId) {
    Swal.fire({
        title: 'Cancel Document?',
        text: "Valid only within 72 hours of submission.",
        icon: 'warning',
        input: 'select',
        inputOptions: { 'wrong_data': 'Incorrect Data', 'duplicate': 'Duplicate', 'order_cancelled': 'Cancelled', 'other': 'Other' },
        inputPlaceholder: 'Select reason',
        showCancelButton: true,
        confirmButtonText: 'Confirm Cancellation',
        inputValidator: (v) => { if (!v) return 'Reason is required!' }
    }).then((r) => {
        if (r.isConfirmed) {
            $.ajax({
                url: "{{ url('/api/myinvois/cancelDocument') }}/" + uniqueId,
                method: "POST",
                data: { _token: "{{ csrf_token() }}", reason: r.value },
                beforeSend: function() { Swal.fire({ title: 'Connecting...', didOpen: () => Swal.showLoading() }); },
                success: function(res) { Swal.fire('Success', res.message, 'success').then(() => location.reload()); },
                error: function(xhr) { Swal.fire('Failed', xhr.responseJSON?.message || "LHDN rejected cancellation.", 'error'); }
            });
        }
    });
};
</script>
@endsection