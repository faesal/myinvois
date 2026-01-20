@extends('layouts.app')

@section('content')
{{-- DataTables & Buttons CSS --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
    .table-compact input.form-control {
        font-size: 0.75rem;
        padding: 0.2rem 0.4rem; 
        height: auto;
    }
    .table-compact th {
        font-size: 0.7rem;
        text-transform: uppercase;
        background-color: #f8f9fa;
        color: #6c757d;
        border-bottom: 2px solid #dee2e6;
    }
    .table-compact td { padding: 0.3rem 0.4rem; }
    .input-group-text { font-size: 0.7rem; padding: 0.2rem 0.4rem; }
    
    /* Custom Export Button Colors */
    .btn-export-pdf { background-color: #dc3545 !important; color: white !important; border: none !important; }
    .btn-export-csv { background-color: #198754 !important; color: white !important; border: none !important; }
    .btn-export-pdf:hover { background-color: #bb2d3b !important; }
    .btn-export-csv:hover { background-color: #157347 !important; }
</style>

<div class="container-fluid py-4">
    {{-- WRAPPED HEADER CARD --}}
    <div class="card shadow-sm mb-4 border-0 bg-white">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0" style="font-size: 1.25rem;">
                    <i class="ph-note-pencil me-2 text-primary"></i> Edit Consolidate #<span id="invoice-id-text">{{ $invoice->invoice_no }}</span>
                </h4>
                <div id="exportButtonsContainer"></div> {{-- Container for PDF/CSV --}}
            </div>
        </div>
    </div>

    {{-- ITEM TABLE CARD --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h6 class="fw-bold mb-0">Invoice Items</h6>
            <button onclick="addNewRow()" class="btn btn-dark btn-sm px-3">
                <i class="ph-plus me-1"></i> Add Item
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table id="itemsDataTable" class="table align-middle table-compact mb-0" style="table-layout: fixed; min-width: 1100px;">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Description</th>
                            <th style="width: 8%;">Qty</th>
                            <th style="width: 10%;">Price (RM)</th>
                            <th style="width: 10%;">Disc (RM)</th>
                            <th style="width: 12%;">Tax Rate (%)</th>
                            <th style="width: 10%;">Tax RM</th>
                            <th style="width: 10%;">Total</th>
                            <th style="width: 8%;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="invoice-items-body">
                        @foreach($items as $item)
                       @php
                        $calculatedRate = 0;
                        // We round the reverse calculation to 2 decimals to keep it clean (e.g. 6.00)
                        if($item->line_extension_amount > 0 && $item->tax > 0) {
                            $calculatedRate = round(($item->tax / $item->line_extension_amount) * 100, 2);
                        }
                        @endphp
                        <tr id="row-{{ $item->id_invoice_item }}" class="item-row">
                            <td><input type="text" class="form-control" value="{{ $item->item_description }}" id="desc-{{ $item->id_invoice_item }}" oninput="triggerAutoSave({{ $item->id_invoice_item }})"></td>
                            <td><input type="number" class="form-control text-center" value="{{ $item->invoiced_quantity }}" id="qty-{{ $item->id_invoice_item }}" oninput="calculateRow({{ $item->id_invoice_item }})"></td>
                            <td><input type="number" step="0.01" class="form-control text-center" value="{{ $item->price_amount }}" id="price-{{ $item->id_invoice_item }}" oninput="calculateRow({{ $item->id_invoice_item }})"></td>
                            <td><input type="number" step="0.01" class="form-control text-center text-danger" value="{{ $item->price_discount ?? 0 }}" id="disc-{{ $item->id_invoice_item }}" oninput="calculateRow({{ $item->id_invoice_item }})"></td>
                            <td>
                                <div class="input-group">
                                    <input type="number" step="0.1" class="form-control text-center" value="{{ ($calculatedRate == 0) ? '' : round($calculatedRate, 2) }}" id="tax-rate-{{ $item->id_invoice_item }}" placeholder="0.00" oninput="calculateRow({{ $item->id_invoice_item }})">
                                    <span class="input-group-text bg-light text-muted small">%</span>
                                </div>
                            </td>
                            <td class="text-primary fw-bold" style="font-size: 0.8rem;">RM <span id="tax-rm-{{ $item->id_invoice_item }}">{{ number_format($item->tax ?? 0, 2) }}</span></td>
                            <td class="fw-bold text-dark" style="font-size: 0.8rem;">RM <span id="line-total-{{ $item->id_invoice_item }}">{{ number_format($item->line_extension_amount, 2) }}</span></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2 align-items-center">
                                    <span id="save-status-{{ $item->id_invoice_item }}" class="me-1"></span>
                                    <a href="javascript:void(0)" onclick="saveRow({{ $item->id_invoice_item }})" class="fs-5 save-btn text-decoration-none" id="save-btn-{{ $item->id_invoice_item }}"><i class="ph-floppy-disk text-secondary"></i></a>
                                    <a href="javascript:void(0)" onclick="deleteRow({{ $item->id_invoice_item }})" class="text-danger fs-5 text-decoration-none"><i class="ph-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Grand Totals --}}
    <div class="row justify-content-end">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Subtotal:</span><span class="fw-bold">RM <span id="display-subtotal">{{ number_format($invoice->consolidate_total_amount_before, 2) }}</span></span></div>
                    <div class="d-flex justify-content-between mb-3 small"><span class="text-muted">Total Tax:</span><span class="fw-bold">RM <span id="display-tax">{{ number_format($invoice->tax_amount, 2) }}</span></span></div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center"><h6 class="fw-bold mb-0">Total:</h6><h5 class="fw-bold text-dark mb-0">RM <span id="display-total">{{ number_format($invoice->consolidate_complete_total, 2) }}</span></h5></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    const invoiceNo = "{{ $invoice->invoice_no }}";

    var table = $('#itemsDataTable').DataTable({
        "pageLength": 10,
        "dom": '<"p-3 d-flex justify-content-between align-items-center"l f>rtip',
        "buttons": [
            {
                extend: 'csv',
                text: '<i class="ph-file-csv me-1"></i> CSV',
                className: 'btn btn-export-csv btn-sm fw-bold px-3 ms-2',
                filename: invoiceNo,
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6], // Excludes Actions
                    format: {
                        body: function (data, row, column, node) {
                            // Strip HTML tags and return only clean text/values
                            const input = $(node).find('input');
                            if (input.length) return input.val();
                            
                            const span = $(node).find('span');
                            // This removes the "RM" and HTML tags, leaving just the number
                            if (span.length) return span.text().replace(/,/g, ''); 
                            
                            return data.replace(/RM /g, '');
                        }
                    }
                }
            },
            {
    extend: 'pdf',
    text: '<i class="ph-file-pdf me-1"></i> PDF',
    className: 'btn btn-export-pdf btn-sm fw-bold px-3 ms-2',
    filename: invoiceNo,
    orientation: 'landscape',
    pageSize: 'A4',
    exportOptions: {
        columns: [0, 1, 2, 3, 4, 5, 6],
        format: {
            body: function (data, row, column, node) {
                // Returns input value if cell is an input, otherwise clean text
                const input = $(node).find('input');
                if (input.length) return input.val();
                
                const span = $(node).find('span');
                if (span.length) return span.text();
                
                return data.replace(/RM /g, '');
            }
        }
    },
    customize: function (doc) {
    // 1. Add the Invoice ID as a title/header
    doc.content.splice(0, 0, {
        text: 'Invoice ID: ' + invoiceNo,
        fontSize: 14,
        bold: true,
        margin: [0, 0, 0, 15],
        alignment: 'left'
    });

    // 2. Improve table styling
    doc.styles.tableHeader.fillColor = '#f8f9fa';
    doc.styles.tableHeader.color = '#333';
    doc.styles.tableHeader.alignment = 'center';
    doc.defaultStyle.fontSize = 9;
    
    // 3. Set column widths
    doc.content[2].table.widths = ['30%', '8%', '12%', '12%', '12%', '13%', '13%'];
    
    // 4. Center numeric columns
    const rowCount = doc.content[2].table.body.length;
    for (var i = 1; i < rowCount; i++) {
        doc.content[2].table.body[i][1].alignment = 'center'; // Qty
        doc.content[2].table.body[i][4].alignment = 'center'; // Tax %
        doc.content[2].table.body[i][5].alignment = 'right';  // Tax RM
        doc.content[2].table.body[i][6].alignment = 'right';  // Total
    }

    // 5. EXTRACT AND ADD GRAND TOTALS
    const subtotal = $('#display-subtotal').text();
    const taxTotal = $('#display-tax').text();
    const grandTotal = $('#display-total').text();

    doc.content.push({
        margin: [0, 20, 0, 0],
        table: {
            widths: ['*', '20%'],
            body: [
                [
                    { text: 'Subtotal (RM)', alignment: 'right', border: [] },
                    { text: subtotal, alignment: 'right', border: [] }
                ],
                [
                    { text: 'Total Tax (RM)', alignment: 'right', border: [] },
                    { text: taxTotal, alignment: 'right', border: [] }
                ],
                [
                    { text: 'GRAND TOTAL (RM)', alignment: 'right', bold: true, fontSize: 11, border: [] },
                    { text: grandTotal, alignment: 'right', bold: true, fontSize: 11, border: [] }
                ]
            ]
        },
        layout: 'noBorders'
    });
}
}
        ]
    });

    table.buttons().container().appendTo('#exportButtonsContainer');
});

let saveTimers = {};

function calculateRow(id) {
    const qty = parseFloat(document.getElementById('qty-' + id).value) || 0;
    const price = parseFloat(document.getElementById('price-' + id).value) || 0;
    const disc = parseFloat(document.getElementById('disc-' + id).value) || 0;
    const taxRate = parseFloat(document.getElementById('tax-rate-' + id).value) || 0;

    let gross = qty * price;
    let taxable = gross - disc;
    if(taxable < 0) taxable = 0;

    // USE MATH.ROUND TO FORCE 2 DECIMALS
    const taxRM = Math.round((taxable * (taxRate / 100)) * 100) / 100;
    const lineTotal = Math.round((taxable + taxRM) * 100) / 100;

    document.getElementById('tax-rm-' + id).innerText = taxRM.toFixed(2);
    document.getElementById('line-total-' + id).innerText = lineTotal.toFixed(2);

    recalculateGrandTotals();
    triggerAutoSave(id);
}

function triggerAutoSave(id) {
    const icon = document.querySelector(`#save-btn-${id} i`);
    if(icon) icon.className = "ph-spinner ph-spin text-warning"; 
    if (saveTimers[id]) clearTimeout(saveTimers[id]);
    saveTimers[id] = setTimeout(() => { saveRow(id); }, 1000); 
}

function recalculateGrandTotals() {
    let subtotal = 0, totalTax = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const id = row.id.replace('row-', '');
        
        const qty = parseFloat(document.getElementById('qty-' + id).value) || 0;
        const price = parseFloat(document.getElementById('price-' + id).value) || 0;
        const disc = parseFloat(document.getElementById('disc-' + id).value) || 0;
        const taxRate = parseFloat(document.getElementById('tax-rate-' + id).value) || 0;
        
        let taxable = (qty * price) - disc;
        if(taxable < 0) taxable = 0;
        
        // Round each row tax individually before summing
        let rowTax = Math.round((taxable * (taxRate / 100)) * 100) / 100;
        
        subtotal += taxable;
        totalTax += rowTax;
    });

    document.getElementById('display-subtotal').innerText = subtotal.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('display-tax').innerText = totalTax.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('display-total').innerText = (subtotal + totalTax).toLocaleString('en-US', {minimumFractionDigits: 2});
}

function saveRow(id) {
    const data = {
        _token: '{{ csrf_token() }}',
        description: document.getElementById('desc-' + id).value,
        qty: document.getElementById('qty-' + id).value,
        price: document.getElementById('price-' + id).value,
        discount: document.getElementById('disc-' + id).value,
        tax_rate: document.getElementById('tax-rate-' + id).value
    };
    fetch("{{ url('/consolidate/item/update') }}/" + id, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data)
    }).then(response => response.json()).then(result => {
        const icon = document.querySelector(`#save-btn-${id} i`);
        if(result.success) {
            icon.className = "ph-check-circle text-success";
            setTimeout(() => { icon.className = "ph-floppy-disk text-secondary"; }, 2000);
        }
    });
}

function addNewRow() {
    fetch("{{ route('consolidate.item.add', $invoice->id_invoice) }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(() => location.reload());
}

function deleteRow(id) {
    if (confirm('Delete this item?')) {
        fetch("{{ url('/consolidate/item/delete-record') }}/" + id, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        }).then(() => location.reload());
    }
}
</script>
@endsection