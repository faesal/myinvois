@php
    // Dynamic Layout Logic
    $layout = $layout ?? (Auth::check() && Auth::user()->role === 'developer' ? 'layouts.developerLayout' : 'layouts.app');
@endphp

@extends($layout)

@section('title', 'Edit Consolidation Batch')

@section('content')
{{-- Phosphor Icons --}}
<script src="https://unpkg.com/@phosphor-icons/web"></script>

{{-- DataTables & Buttons CSS --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
    /* Apple-like soft action buttons */
    .btn-light-primary { background-color: #e7f1ff; color: #0d6efd; border: none; transition: all 0.2s; }
    .btn-light-primary:hover { background-color: #0d6efd; color: #fff; transform: translateY(-1px); }
    
    .btn-light-danger { background-color: #ffe5e5; color: #dc3545; border: none; transition: all 0.2s; }
    .btn-light-danger:hover { background-color: #dc3545; color: #fff; transform: translateY(-1px); }

    .action-btn {
        width: 32px; height: 32px;
        display: inline-flex; align-items: center; justify-content: center;
        text-decoration: none; border-radius: 50%;
    }

    /* Table Inputs */
    .table-compact input.form-control {
        font-size: 0.85rem; padding: 0.35rem 0.5rem; height: auto; border-radius: 6px;
        border: 1px solid #dee2e6;
    }
    .table-compact input.form-control:focus {
        border-color: #86b7fe; box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15);
    }
    
    .table-compact th {
        font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;
        background-color: #f8f9fa; color: #6c757d; border-bottom: 2px solid #dee2e6;
        padding: 12px 8px;
    }
    .table-compact td { padding: 8px; }
    .input-group-text { font-size: 0.75rem; padding: 0.35rem 0.5rem; }
    
    /* Export Buttons */
    .btn-export-pdf { background-color: #dc3545 !important; color: white !important; border: none !important; }
    .btn-export-csv { background-color: #198754 !important; color: white !important; border: none !important; }
    .btn-export-pdf:hover { background-color: #bb2d3b !important; }
    .btn-export-csv:hover { background-color: #157347 !important; }
    
    /* Ensure icons inside buttons are visible */
    .dt-button i { margin-right: 5px; }

    /* Custom Toast Styling - Clean White */
    body.swal2-toast-shown .swal2-container.swal2-top-end, 
    body.swal2-toast-shown .swal2-container.swal2-top-right {
        top: 20px !important;
        right: 20px !important;
    }
</style>

<div class="container-fluid">
    
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Edit Consolidation</h3>
            <p class="text-muted small mb-0">
                Batch ID: <span class="fw-bold text-dark">#{{ $invoice->invoice_no }}</span>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            {{-- Export Buttons will be appended here --}}
            <div id="exportButtonsContainer"></div>
            
            <a href="{{ route('consolidate.import.index') }}" class="btn btn-light border shadow-sm fw-medium">
                <i class="ph ph-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-0">
            <h6 class="fw-bold mb-0 text-primary"><i class="ph ph-list-dashes me-2"></i>Invoice Items</h6>
            <button onclick="addNewRow()" class="btn btn-dark btn-sm px-3 shadow-sm rounded-pill">
                <i class="ph ph-plus me-1"></i> Add New Item
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table id="itemsDataTable" class="table align-middle table-compact mb-0" style="table-layout: fixed; min-width: 1100px;">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Description</th>
                            <th style="width: 8%; text-align: center;">Qty</th>
                            <th style="width: 12%; text-align: center;">Price (RM)</th>
                            <th style="width: 10%; text-align: center;">Disc (RM)</th>
                            <th style="width: 12%; text-align: center;">Tax Rate (%)</th>
                            <th style="width: 10%; text-align: right;">Tax RM</th>
                            <th style="width: 12%; text-align: right;">Total</th>
                            <th style="width: 8%; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="invoice-items-body">
                        @foreach($items as $item)
                        @php
                            $calculatedRate = 0;
                            if($item->line_extension_amount > 0 && $item->tax > 0) {
                                $calculatedRate = round(($item->tax / $item->line_extension_amount) * 100, 2);
                            }
                        @endphp
                        <tr id="row-{{ $item->id_invoice_item }}" class="item-row">
                            <td>
                                <input type="text" class="form-control" value="{{ $item->item_description }}" id="desc-{{ $item->id_invoice_item }}" oninput="triggerAutoSave({{ $item->id_invoice_item }})">
                            </td>
                            <td>
                                <input type="number" class="form-control text-center" value="{{ $item->invoiced_quantity }}" id="qty-{{ $item->id_invoice_item }}" oninput="calculateRow({{ $item->id_invoice_item }})">
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control text-center" value="{{ $item->price_amount }}" id="price-{{ $item->id_invoice_item }}" oninput="calculateRow({{ $item->id_invoice_item }})">
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control text-center text-danger" value="{{ $item->price_discount ?? 0 }}" id="disc-{{ $item->id_invoice_item }}" oninput="calculateRow({{ $item->id_invoice_item }})">
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.1" class="form-control text-center" value="{{ ($calculatedRate == 0) ? '' : round($calculatedRate, 2) }}" id="tax-rate-{{ $item->id_invoice_item }}" placeholder="0.00" oninput="calculateRow({{ $item->id_invoice_item }})">
                                    <span class="input-group-text bg-light text-muted">%</span>
                                </div>
                            </td>
                            <td class="text-end fw-medium text-muted" style="font-size: 0.9rem;">
                                RM <span id="tax-rm-{{ $item->id_invoice_item }}">{{ number_format($item->tax ?? 0, 2) }}</span>
                            </td>
                            <td class="text-end fw-bold text-dark" style="font-size: 0.9rem;">
                                RM <span id="line-total-{{ $item->id_invoice_item }}">{{ number_format($item->line_extension_amount + $item->tax, 2) }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2 align-items-center">
                                    {{-- Save Button --}}
                                    <a href="javascript:void(0)" onclick="saveRow({{ $item->id_invoice_item }})" 
                                       class="action-btn btn-light-primary" id="save-btn-{{ $item->id_invoice_item }}" title="Save">
                                        <i class="ph ph-floppy-disk"></i>
                                    </a>
                                    {{-- Delete Button --}}
                                    <a href="javascript:void(0)" onclick="deleteRow({{ $item->id_invoice_item }})" 
                                       class="action-btn btn-light-danger" title="Delete">
                                        <i class="ph ph-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Totals Summary Card --}}
    <div class="row justify-content-end">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Summary</h6>
                    
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Subtotal (Excl. Tax):</span>
                        <span class="fw-bold">RM <span id="display-subtotal">{{ number_format($invoice->consolidate_total_amount_before, 2) }}</span></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-muted">Total Tax:</span>
                        <span class="fw-bold text-danger">RM <span id="display-tax">{{ number_format($invoice->tax_amount, 2) }}</span></span>
                    </div>
                    
                    <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">Grand Total:</h6>
                        <h5 class="fw-bold text-primary mb-0">RM <span id="display-total">{{ number_format($invoice->consolidate_complete_total, 2) }}</span></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Create Private Sandbox
    var $j = jQuery.noConflict(true);

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: '#fff',
        color: '#000',
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    window.addEventListener('load', function() {
        const invoiceNo = "{{ $invoice->invoice_no }}";

        // 1. FORCE DESTROY (Kill any table created by app.js)
        if ($j.fn.DataTable.isDataTable('#itemsDataTable')) {
            $j('#itemsDataTable').DataTable().destroy();
        }

        // 2. INITIALIZE TABLE
        var table = $j('#itemsDataTable').DataTable({
            "pageLength": 10,
            // REMOVED 'B' to allow manual insertion
            "dom": '<"p-3 d-flex justify-content-between align-items-center"l f>rtip'
        });

        // 3. MANUALLY CREATE BUTTONS (The "Bypass" Method for Visibility)
        var buttons = new $j.fn.dataTable.Buttons(table, {
            buttons: [
                {
                    extend: 'csv',
                    text: '<i class="ph ph-file-csv me-1"></i> CSV',
                    className: 'btn btn-export-csv btn-sm fw-bold px-3 ms-2 shadow-sm rounded-1',
                    filename: invoiceNo,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6],
                        format: {
                            body: function (data, row, column, node) {
                                const input = $j(node).find('input');
                                if (input.length) return input.val();
                                const span = $j(node).find('span');
                                if (span.length) return span.text().replace(/,/g, ''); 
                                return data.replace(/RM /g, '');
                            }
                        }
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="ph ph-file-pdf me-1"></i> PDF',
                    className: 'btn btn-export-pdf btn-sm fw-bold px-3 ms-2 shadow-sm rounded-1',
                    filename: invoiceNo,
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6],
                        format: {
                            body: function (data, row, column, node) {
                                const input = $j(node).find('input');
                                if (input.length) return input.val();
                                const span = $j(node).find('span');
                                if (span.length) return span.text();
                                return data.replace(/RM /g, '');
                            }
                        }
                    },
                    customize: function (doc) {
                        doc.content.splice(0, 0, { 
                            text: 'Invoice ID: ' + invoiceNo, 
                            fontSize: 14, 
                            bold: true, 
                            margin: [0, 0, 0, 15] 
                        });
                        doc.styles.tableHeader.fillColor = '#f8f9fa';
                        doc.defaultStyle.fontSize = 9;
                        doc.content[2].table.widths = ['30%', '8%', '12%', '12%', '12%', '13%', '13%'];
                        
                        // Get current totals from DOM
                        const subtotal = document.getElementById('display-subtotal').innerText;
                        const taxTotal = document.getElementById('display-tax').innerText;
                        const grandTotal = document.getElementById('display-total').innerText;
                        
                        doc.content.push({
                            margin: [0, 20, 0, 0],
                            table: {
                                widths: ['*', '20%'],
                                body: [
                                    [{ text: 'Subtotal (RM)', alignment: 'right', border: [] }, { text: subtotal, alignment: 'right', border: [] }],
                                    [{ text: 'Total Tax (RM)', alignment: 'right', border: [] }, { text: taxTotal, alignment: 'right', border: [] }],
                                    [{ text: 'GRAND TOTAL (RM)', alignment: 'right', bold: true, fontSize: 11, border: [] }, { text: grandTotal, alignment: 'right', bold: true, fontSize: 11, border: [] }]
                                ]
                            }, layout: 'noBorders'
                        });
                    }
                }
            ]
        });

        // 4. INJECT BUTTONS INTO CONTAINER
        $j('#exportButtonsContainer').empty().append(buttons.container());
    });

    let saveTimers = {};

    window.calculateRow = function(id) {
        const qty = parseFloat(document.getElementById('qty-' + id).value) || 0;
        const price = parseFloat(document.getElementById('price-' + id).value) || 0;
        const disc = parseFloat(document.getElementById('disc-' + id).value) || 0;
        const taxRate = parseFloat(document.getElementById('tax-rate-' + id).value) || 0;

        let taxable = (qty * price) - disc;
        if(taxable < 0) taxable = 0;

        const taxRM = Math.round((taxable * (taxRate / 100)) * 100) / 100;
        const lineTotal = Math.round((taxable + taxRM) * 100) / 100;

        document.getElementById('tax-rm-' + id).innerText = taxRM.toFixed(2);
        document.getElementById('line-total-' + id).innerText = lineTotal.toFixed(2);

        recalculateGrandTotals();
        triggerAutoSave(id);
    }

    window.triggerAutoSave = function(id) {
        const icon = document.querySelector(`#save-btn-${id} i`);
        if(icon) icon.className = "ph ph-spinner ph-spin text-warning"; 
        if (saveTimers[id]) clearTimeout(saveTimers[id]);
        saveTimers[id] = setTimeout(() => { saveRow(id); }, 1200); 
    }

    window.recalculateGrandTotals = function() {
        let subtotal = 0, totalTax = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const id = row.id.replace('row-', '');
            const qty = parseFloat(document.getElementById('qty-' + id).value) || 0;
            const price = parseFloat(document.getElementById('price-' + id).value) || 0;
            const disc = parseFloat(document.getElementById('disc-' + id).value) || 0;
            const taxRate = parseFloat(document.getElementById('tax-rate-' + id).value) || 0;
            let taxable = (qty * price) - disc;
            if(taxable < 0) taxable = 0;
            let rowTax = Math.round((taxable * (taxRate / 100)) * 100) / 100;
            subtotal += taxable;
            totalTax += rowTax;
        });
        document.getElementById('display-subtotal').innerText = subtotal.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('display-tax').innerText = totalTax.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('display-total').innerText = (subtotal + totalTax).toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    window.saveRow = function(id) {
        const data = {
            _token: '{{ csrf_token() }}',
            description: document.getElementById('desc-' + id).value,
            qty: document.getElementById('qty-' + id).value,
            price: document.getElementById('price-' + id).value,
            discount: document.getElementById('disc-' + id).value,
            tax_rate: document.getElementById('tax-rate-' + id).value
        };
        
        // Safer route construction
        var url = "{{ route('consolidate.item.update', ':id') }}";
        url = url.replace(':id', id);

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(result => {
            const icon = document.querySelector(`#save-btn-${id} i`);
            if(result.success) {
                icon.className = "ph ph-check-circle text-success";
                
                // Fire Toast
                Toast.fire({ icon: 'success', title: result.message || 'Saved successfully' });
                
                // Update Totals from Server response to be precise
                if(result.new_subtotal) document.getElementById('display-subtotal').innerText = result.new_subtotal;
                if(result.new_tax) document.getElementById('display-tax').innerText = result.new_tax;
                if(result.new_total) document.getElementById('display-total').innerText = result.new_total;
                
                setTimeout(() => { icon.className = "ph ph-floppy-disk"; }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const icon = document.querySelector(`#save-btn-${id} i`);
            icon.className = "ph ph-warning text-danger";
        });
    }

    window.addNewRow = function() {
        var url = "{{ route('consolidate.item.add', $invoice->id_invoice) }}";
        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(() => location.reload());
    }

    window.deleteRow = function(id) {
        Swal.fire({
            title: 'Delete Item?',
            text: "This item will be removed from the batch.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, delete'
        }).then((result) => {
            if (result.isConfirmed) {
                var url = "{{ route('consolidate.item.delete', ':id') }}";
                url = url.replace(':id', id);
                
                fetch(url, {
                    method: 'GET',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                }).then(() => location.reload());
            }
        });
    }
</script>
@endsection