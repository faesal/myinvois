@php
    // Determine if we are in Developer Mode
    // We keep this check here to ensure the page works even if the Controller wasn't updated
    $isDeveloper = (Auth::check() && Auth::user()->role === 'developer');
    $layout = $isDeveloper ? 'layouts.developerLayout' : 'layouts.app';
@endphp

@extends($layout)

@section('content')

{{-- 1. CSS & STYLES --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
    /* DESIGN CONSISTENCY */
    .btn-export-pdf { background-color: #dc3545 !important; color: white !important; border: none !important; }
    .btn-export-csv { background-color: #198754 !important; color: white !important; border: none !important; }
    .btn-export-pdf:hover { background-color: #bb2d3b !important; }
    .btn-export-csv:hover { background-color: #157347 !important; }
    
    .upload-area { border: 2px dashed #dee2e6; background-color: #f8f9fa; cursor: pointer; transition: all 0.2s; }
    .upload-area:hover { background-color: #e9ecef; border-color: #adb5bd; }

    /* Fix container visibility */
    #exportButtonsContainer { display: inline-flex !important; gap: 8px; align-items: center; vertical-align: middle; }
    #consolidateDataTable { width: 100% !important; }
    .card { margin-bottom: 1.5rem; }

    /* Ensure icons inside buttons are visible */
    .dt-button i { margin-right: 5px; }

    /* FIX: Make SweetAlert icons smaller and more proportionate */
    .swal2-icon {
        transform: scale(0.6) !important;
        margin: 10px auto -10px auto !important; 
    }
    .swal2-popup {
        padding-top: 15px !important;
    }
</style>

@if($isDeveloper)
<div class="container-fluid">
    <div class="card shadow-sm border-0 bg-white">
        <div class="card-header bg-white border-bottom py-3">
             <h5 class="mb-0 fw-bold">LHDN Data Consolidation</h5>
        </div>
        <div class="card-body">
@endif

    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 p-2 rounded me-3 text-primary">
                <i class="{{ $isDeveloper ? 'fas fa-file-csv' : 'ph-file-csv' }} fs-3"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0">Consolidation Dashboard</h5>
                <small class="text-muted">Manage batch submissions</small>
            </div>
        </div>
        <div class="text-muted small">
            <i class="{{ $isDeveloper ? 'fas fa-user-circle' : 'ph-user-circle' }} me-1"></i> 
            {{ Auth::user()->name ?? 'User' }}
        </div>
    </div>

    {{-- Upload & Template --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card h-100 shadow-sm border">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-start mb-3"><i class="{{ $isDeveloper ? 'fas fa-cloud-upload-alt' : 'ph-cloud-arrow-up' }} me-2 text-primary"></i>Upload Data</h6>
                    <form action="{{ route('consolidate.import.process') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf
                        
                        {{-- 👉 ADDED: Developer Connection Dropdown for Upload --}}
                        @if($isDeveloper)
                            <div class="mb-3 text-start">
                                <label class="form-label fw-bold small text-muted">Select Target LHDN Account <span class="text-danger">*</span></label>
                                <select name="connection_integrate" id="uploadConnection" class="form-select bg-light" required>
                                    <option value="">-- Choose Connection --</option>
                                    @foreach($connections ?? [] as $conn)
                                        <option value="{{ $conn->connection_integrate }}" {{ (isset($selectedConnection) && $selectedConnection == $conn->connection_integrate) ? 'selected' : '' }}>
                                            {{ strtoupper($conn->registration_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="upload-area p-4 rounded" onclick="triggerUpload();">
                            <i class="{{ $isDeveloper ? 'fas fa-upload' : 'ph-upload-simple' }} text-secondary mb-3" style="font-size: 32px;"></i>
                            <h6 class="fw-bold mb-1">Click to upload CSV</h6>
                            <input type="file" name="file" id="fileInput" class="d-none" accept=".csv" onchange="document.getElementById('uploadForm').submit();">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="fw-bold mb-2"><i class="{{ $isDeveloper ? 'fas fa-download' : 'ph-download-simple' }} me-2 text-success"></i>CSV Template</h6>
                        <p class="text-muted small mb-3">Download the standardized format for imports.</p>
                    </div>
                    <a href="{{ route('consolidate.template') }}" class="btn btn-dark w-100 py-2 fw-bold">Download Template</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Table Section --}}
    <div class="card shadow-sm border">
        <div class="card-header bg-light py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Batch List</h6>
                <div class="d-flex align-items-center gap-2">
                    <button id="submitBtn" class="btn btn-success fw-bold px-3 btn-sm shadow-sm" onclick="submitSelectedToInvoice(event)">
                        <i class="{{ $isDeveloper ? 'fas fa-paper-plane' : 'ph-paper-plane-tilt' }} me-1"></i> Submit Selected
                    </button>
                    {{-- THE CONTAINER FOR CSV/PDF BUTTONS --}}
                    <div id="exportButtonsContainer"></div>
                </div>
            </div>

            {{-- 👉 ADDED: Developer Table Filter --}}
            @if($isDeveloper)
            <div class="mt-3 pt-3 border-top">
                <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted mb-0 text-nowrap">Filter Table:</label>
                    <select name="connection_integrate" class="form-select form-select-sm w-auto">
                        <option value="">All Accounts</option>
                        @foreach($connections ?? [] as $conn)
                            <option value="{{ $conn->connection_integrate }}" {{ (isset($selectedConnection) && $selectedConnection == $conn->connection_integrate) ? 'selected' : '' }}>
                                {{ strtoupper($conn->registration_name) }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm px-3">Filter</button>
                </form>
            </div>
            @endif
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="consolidateDataTable" class="table table-hover align-middle mb-0 text-nowrap table-striped">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 40px;" class="text-center"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                            <th>Invoice ID</th>
                            <th>Sale ID / Batch</th>
                            <th class="text-center">Items</th>
                            <th>Amount (RM)</th>
                            <th>Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($consolidations as $batch)
                        <tr>
                            <td class="text-center">
                                @if(isset($batch->submition_status) && $batch->submition_status === 'submitted')
                                    <span class="badge bg-success rounded-pill d-inline-flex align-items-center justify-content-center p-1" style="width: 24px; height: 24px;">
                                        <i class="{{ $isDeveloper ? 'fas fa-check' : 'ph-check' }}" style="font-size: 14px;"></i>
                                    </span>
                                @else
                                    <input type="checkbox" class="form-check-input row-checkbox" value="{{ $batch->id_invoice }}">
                                @endif
                            </td>
                            <td class="fw-bold text-dark">{{ $batch->invoice_no }}</td>
                            <td class="text-secondary small">{{ $batch->unique_id }}</td>
                            <td class="text-center fw-bold text-dark">{{ $batch->consolidate_total_item ?? 0 }}</td>
                            <td class="fw-bold text-primary">RM {{ number_format($batch->consolidate_complete_total, 2) }}</td>
                            <td class="text-muted">{{ \Carbon\Carbon::parse($batch->created_at)->format('d-m-Y') }}</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('consolidate.view', $batch->id_invoice) }}" class="btn btn-sm btn-light border shadow-sm me-1"><i class="{{ $isDeveloper ? 'fas fa-eye' : 'ph-eye' }}"></i></a>
                                <button type="button" onclick="confirmDelete('{{ route('consolidate.delete', $batch->id_invoice) }}')" class="btn btn-sm btn-outline-danger shadow-sm"><i class="{{ $isDeveloper ? 'fas fa-times' : 'ph-x' }}"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 👉 ADDED: Pagination Links --}}
        <div class="px-3 pt-3 border-top">
            @if(method_exists($consolidations, 'links'))
                {{ $consolidations->appends(request()->query())->links('pagination::bootstrap-5') }}
            @endif
        </div>
    </div>

@if($isDeveloper)
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
{{-- 1. LOAD DEPENDENCIES (Fresh tools just for this page) --}}
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
    // Create Private Sandbox
    var $j = jQuery.noConflict(true);

    // 👉 ADDED: Validation Check before opening File Dialog
    window.triggerUpload = function() {
        @if($isDeveloper)
        var conn = document.getElementById('uploadConnection').value;
        if (!conn) {
            Swal.fire({
                icon: 'warning', 
                title: 'Connection Required', 
                text: 'Please select a Target LHDN Account before uploading the CSV.'
            });
            return;
        }
        @endif
        document.getElementById('fileInput').click();
    };

    // Wait for everything to load to beat the app layout scripts
    window.addEventListener('load', function() {
        
        // 1. FORCE DESTROY (Kill any table created by app.js)
        if ($j.fn.DataTable.isDataTable('#consolidateDataTable')) {
            $j('#consolidateDataTable').DataTable().destroy();
        }

        // 2. INITIALIZE TABLE 
        // Note: Removed standard pagination/info so it doesn't conflict with Laravel's Server-Side links
        var table = $j('#consolidateDataTable').DataTable({
            "order": [[5, "desc"]], 
            "columnDefs": [{ "orderable": false, "targets": 0 }],
            "pageLength": 100, // Large number so we see all paginate items
            "paging": false,
            "info": false,
            "dom": '<"p-3 d-flex justify-content-end align-items-center"f>rt'
        });

        // 3. MANUALLY CREATE BUTTONS (The "Bypass" Method)
        var buttons = new $j.fn.dataTable.Buttons(table, {
            buttons: [
                {
                    extend: 'csv',
                    text: '<i class="{{ $isDeveloper ? "fas fa-file-csv" : "ph-file-csv" }} me-1"></i> CSV',
                    className: 'btn btn-export-csv btn-sm fw-bold',
                    exportOptions: { 
                        columns: [1, 2, 3, 4, 5],
                        rows: function ( idx, data, node ) {
                            var checkedRows = $j('.row-checkbox:checked').length;
                            return checkedRows > 0 ? $j(node).find('.row-checkbox').prop('checked') : true;
                        }
                    } 
                },
                {
                    extend: 'pdf',
                    text: '<i class="{{ $isDeveloper ? "fas fa-file-pdf" : "ph-file-pdf" }} me-1"></i> PDF',
                    className: 'btn btn-export-pdf btn-sm fw-bold',
                    orientation: 'landscape',
                    exportOptions: { 
                        columns: [1, 2, 3, 4, 5],
                        rows: function ( idx, data, node ) {
                            var checkedRows = $j('.row-checkbox:checked').length > 0;
                            return checkedRows ? $j(node).find('.row-checkbox').prop('checked') : true;
                        }
                    }
                }
            ]
        });

        // 4. INJECT BUTTONS INTO YOUR CONTAINER
        $j('#exportButtonsContainer').empty().append(buttons.container());

        // 5. Select All Logic
        $j('#selectAll').on('click', function() {
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $j('input[type="checkbox"]', rows).prop('checked', this.checked);
        });
        
        // Safety check: Ensure the submit button doesn't act like a traditional form submit
        $j('#submitBtn').attr('type', 'button');
    });

    // Global Functions (Keep these available for your onClick events)
    window.confirmDelete = function(url) {
        Swal.fire({
            title: 'Delete Batch?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => { if (result.isConfirmed) window.location.href = url; });
    };

    // Passed 'event' to prevent default form submission if it's trapped in a form
    window.submitSelectedToInvoice = function(event) {
        if(event) {
            event.preventDefault(); 
        }

        var selectedIds = [];
        $j('.row-checkbox:checked').each(function() { 
            selectedIds.push($j(this).val()); 
        });

        if (selectedIds.length === 0) {
            Swal.fire({ icon: 'info', title: 'No Selection', text: 'Please select records.' });
            return;
        }

        Swal.fire({
            title: 'Confirm Submission',
            text: 'Submit ' + selectedIds.length + ' batches?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Yes, Submit',
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $j('#submitBtn');
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

                fetch("{{ route('consolidate.submit_lhdn') }}", {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json', // Explicitly ask Laravel for JSON back
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({ ids: selectedIds }) 
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Success -> Alert and standard window reload
                        Swal.fire({ icon: 'success', title: 'Submitted', text: result.message || 'Successfully submitted!', timer: 2000 })
                            .then(() => window.location.replace(window.location.href)); 
                    } else {
                        // Warning -> Will show "Batches already submitted." gracefully
                        Swal.fire({ icon: 'warning', title: 'Notice', text: result.message || 'Action could not be completed.' });
                        btn.prop('disabled', false).html(originalHtml);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({ icon: 'error', title: 'System Error', text: 'An unexpected error occurred. Check the console.' });
                    btn.prop('disabled', false).html(originalHtml);
                });
            }
        });
    };
</script>
@endsection