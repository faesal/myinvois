@extends('layouts.app')

@section('content')
{{-- DataTables & Buttons CSS --}}
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container-fluid py-4">
    
    {{-- Header --}}
    <div class="card shadow-sm mb-4 border-0 bg-white">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0"><i class="ph-file-csv me-2 text-primary"></i> LHDN Data Consolidation</h4>
                <div class="text-muted">
                    <i class="ph-user-circle me-1"></i> Admin User
                </div>
            </div>
        </div>
    </div>

    {{-- Upload Section --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold text-start mb-3"><i class="ph-cloud-arrow-up me-2"></i>Data Upload</h6>
                    <form action="{{ route('consolidate.import.process') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf
                        <div class="upload-area p-5 border rounded bg-light" 
                             style="border: 2px dashed #dee2e6 !important; cursor: pointer;" 
                             onclick="document.getElementById('fileInput').click();">
                            <i class="ph-upload-simple text-secondary mb-3" style="font-size: 40px;"></i>
                            <h6 class="fw-bold mb-1">Click to upload CSV file</h6>
                            <input type="file" name="file" id="fileInput" class="d-none" accept=".csv" onchange="document.getElementById('uploadForm').submit();">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="ph-download-simple me-2"></i>Download Template</h6>
                    <a href="{{ route('consolidate.template') }}" class="btn btn-dark w-100 py-3 fw-bold">Download CSV Template</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Data Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="fw-bold mb-0">Consolidate Data</h5>
                </div>
                <div class="col-auto d-flex align-items-center">
                    {{-- UPDATED BUTTON TEXT --}}
                    <button id="submitBtn" class="btn btn-success fw-bold px-4 shadow-sm" onclick="submitSelectedToInvoice()">
                        <i class="ph-paper-plane-tilt me-1"></i> Submit Selected to Listing Invoices
                    </button>
                    
                    {{-- Export Buttons Container --}}
                    <div id="exportButtonsContainer" class="ms-2"></div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="consolidateDataTable" class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th>Invoice ID</th>
                            <th>Sale ID / Batch</th>
                            <th class="text-center">Items</th>
                            <th>Amount (RM)</th>
                            <th>Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consolidations as $batch)
                        <tr>
                            <td class="text-center">
                                {{-- LOGIC TO REMOVE CHECKBOX IF ALREADY SUBMITTED --}}
                                {{-- Note: This assumes $batch has a 'submition_status' or similar field joined from the items or header --}}
                                @if(isset($batch->submition_status) && $batch->submition_status === 'submitted')
                                    <span class="badge bg-success"><i class="ph-check"></i></span>
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
                                <a href="{{ route('consolidate.view', $batch->id_invoice) }}" class="btn btn-sm btn-light border shadow-sm me-1"><i class="ph-eye"></i></a>
                                <a href="{{ route('consolidate.delete', $batch->id_invoice) }}" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('Delete batch?')"><i class="ph-trash"></i></a>
                            </td>
                        </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
{{-- Export Libraries --}}
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#consolidateDataTable').DataTable({
            "order": [[5, "desc"]], 
            "columnDefs": [
                { "orderable": false, "targets": 0 } 
            ],
            "pageLength": 10,
            "dom": '<"p-3 d-flex justify-content-between align-items-center"l f>rt<"p-3 d-flex justify-content-between align-items-center"i p>',
            "buttons": [
                {
                    extend: 'csv',
                    text: '<i class="ph-file-csv me-1"></i> CSV',
                    className: 'btn btn-export-csv btn-sm fw-bold px-3 ms-2',
                    exportOptions: { 
                        columns: [1, 2, 3, 4, 5],
                        format: {
                            body: function (data, row, column, node) {
                                return data.replace(/RM /g, '').replace(/,/g, '');
                            }
                        }
                    } 
                },
                {
                    extend: 'pdf',
                    text: '<i class="ph-file-pdf me-1"></i> PDF',
                    className: 'btn btn-export-pdf btn-sm fw-bold px-3 ms-2',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: [1, 2, 3, 4, 5] },
                    customize: function (doc) {
                        doc.content.splice(0, 0, {
                            text: 'LHDN Data Consolidation Summary',
                            fontSize: 14,
                            bold: true,
                            margin: [0, 0, 0, 15],
                            alignment: 'left'
                        });
                        doc.styles.tableHeader.fillColor = '#f8f9fa';
                        doc.styles.tableHeader.color = '#333';
                        doc.styles.tableHeader.alignment = 'center';
                        doc.defaultStyle.fontSize = 10;
                        doc.content[2].table.widths = ['20%', '30%', '10%', '20%', '20%'];
                    }
                }
            ]
        });

        table.buttons().container().appendTo('#exportButtonsContainer');

        $('#selectAll').on('click', function() {
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
        });

        $('#consolidateDataTable tbody').on('change', '.row-checkbox', function() {
            if (!this.checked) {
                $('#selectAll').prop('checked', false);
            }
        });
    });

    /**
     * SUBMIT SELECTED RECORDS TO LISTING INVOICES
     */
    function submitSelectedToInvoice() {
        var selectedIds = [];
        var table = $('#consolidateDataTable').DataTable();
        
        $('.row-checkbox:checked', table.rows().nodes()).each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Please select at least one record to submit.');
            return;
        }

        // Updated Confirmation Text
        if (confirm('Submit ' + selectedIds.length + ' selected batches to Invoice Listing?')) {
            const btn = $('#submitBtn');
            const originalHtml = btn.html();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Processing...');

            // Ensure this route points to your new/updated controller function
            fetch("{{ route('consolidate.submit_lhdn') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ids: selectedIds }) 
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during processing.');
            })
            .finally(() => {
                btn.prop('disabled', false).html(originalHtml);
            });
        }
    }
</script>

<style>
    .btn-export-pdf { background-color: #dc3545 !important; color: white !important; border: none !important; }
    .btn-export-csv { background-color: #198754 !important; color: white !important; border: none !important; }
    .btn-export-pdf:hover { background-color: #bb2d3b !important; }
    .btn-export-csv:hover { background-color: #157347 !important; }
    #exportButtonsContainer .dt-buttons { display: flex; gap: 10px; }
    .form-check-input:checked { background-color: #198754; border-color: #198754; }
    .dataTables_filter input { border: 1px solid #dee2e6; padding: 0.45rem 0.8rem; border-radius: 0.5rem; outline: none; width: 250px; background-color: #fcfcfc; }
</style>
@endsection