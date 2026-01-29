{{-- 
    VIEW: Self-Bill Invoice Listing
    Controller: SelfInvoiceController.php
    Path: resources/views/invoices/selfbill.blade.php
--}}
@extends($layout ?? 'layouts.app')

@section('title', 'Self Bill Invoices')

@section('content')
{{-- Load Assets --}}
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
    /* Action Button Styles */
    .btn-light-primary { background-color: #e7f1ff; color: #0d6efd; transition: all 0.2s ease; border: 1px solid #cfe2ff; }
    .btn-light-primary:hover { background-color: #0d6efd; color: #fff; transform: translateY(-1px); }
    .btn-light-danger { background-color: #ffe5e5; color: #dc3545; transition: all 0.2s ease; border: 1px solid #f8d7da; }
    .btn-light-danger:hover { background-color: #dc3545; color: #fff; transform: translateY(-1px); }
    .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; }
    
    /* Type Badges (LHDN Codes) */
    .badge-type-11 { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; } /* Invoice */
    .badge-type-12 { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; } /* Credit Note */
    .badge-type-13 { background-color: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; } /* Debit Note */
    .badge-type-14 { background-color: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; } /* Refund Note */
</style>

<div class="container-fluid px-4">
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Self-Bill Document Listing</h3>
            <p class="text-muted small mb-0">LHDN Malaysia Compliance (Types 11-14)</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" id="btnExportSelected" class="btn btn-light border shadow-sm fw-medium px-3">
                <i class="ph ph-file-arrow-down me-1"></i> Export Selected
            </button>
            <a href="{{ route('self_invoice.create') }}" class="btn btn-primary shadow-sm fw-medium px-4">
                <i class="ph ph-plus-circle me-1"></i> Add New
            </a>
        </div>
    </div>

    {{-- Filters Toolbar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('self_invoice.index') }}" id="filterForm">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4 d-flex align-items-center">
                        <label class="small fw-bold text-muted me-2 text-nowrap">Filter Supplier:</label>
                        <select name="id_supplier" class="form-select form-select-sm border-light-subtle" onchange="this.form.submit()">
                            <option value="">-- All Suppliers --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id_customer }}" {{ request('id_supplier') == $supplier->id_customer ? 'selected' : '' }}>
                                    {{ strtoupper($supplier->registration_name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light border shadow-sm btn-sm px-3" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="ph ph-file-arrow-up me-1"></i> Bulk Import
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main DataTable --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table id="selfBillTable" class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="bg-light">
                        <tr class="text-muted small fw-bold">
                            <th style="width: 30px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>Invoice No</th>
                            <th>Type</th>
                            <th>Supplier Name</th>
                            <th>Issue Date</th>
                            <th class="text-end">Total (RM)</th>
                            <th class="text-center">LHDN Status</th>
                            <th class="text-center" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                        <tr>
                            <td><input type="checkbox" class="invoice-checkbox form-check-input" value="{{ $inv->id_invoice }}"></td>
                            <td>
                                <span class="fw-bold text-dark">{{ $inv->invoice_no }}</span>
                                @if(isset($inv->previous_invoice_no) && $inv->previous_invoice_no)
                                    <div class="text-muted x-small" style="font-size: 0.7rem;">Ref: {{ $inv->previous_invoice_no }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill badge-type-{{ $inv->invoice_type_code }}">
                                    {{ $inv->invoice_type_code }} - {{ $inv->type_description ?? 'Document' }}
                                </span>
                            </td>
                            <td class="text-uppercase fw-medium">{{ $inv->supplier_name ?? 'Unknown' }}</td>
                            <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('d/m/Y') }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($inv->price, 2) }}</td>
                            
                            <td class="text-center">
                                @php $status = strtolower($inv->submission_status); @endphp
                                @if(str_contains($status, 'submitted') || str_contains($status, 'valid'))
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3">Valid</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3">{{ ucfirst($status) ?: 'Pending' }}</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    {{-- Edit Trigger --}}
                                    <button type="button" class="action-btn btn-light-primary rounded-circle edit-btn"
                                            data-id="{{ $inv->id_invoice }}" data-no="{{ $inv->invoice_no }}"
                                            data-date="{{ date('Y-m-d', strtotime($inv->issue_date)) }}" data-price="{{ $inv->price }}"
                                            data-bs-toggle="modal" data-bs-target="#editInvoiceModal">
                                        <i class="ph ph-pencil-simple"></i>
                                    </button>

                                    <form action="{{ route('self_invoice.destroy', $inv->id_invoice) }}" method="POST" class="delete-form">
                                        @csrf @method('DELETE')
                                        <button type="button" class="action-btn btn-light-danger rounded-circle confirm-delete">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Pagination Links for large datasets --}}
            <div class="mt-3">
                {{ $invoices->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

{{-- MODAL: Edit Header Details --}}
<div class="modal fade" id="editInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Document Header</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editInvoiceForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Document Number</label>
                            <input type="text" name="invoice_no" id="edit_invoice_no" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Issue Date</label>
                            <input type="date" name="issue_date" id="edit_issue_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Total Amount (RM)</label>
                            <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Header</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: Bulk Import --}}
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

{{-- Scripts --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // 1. DataTable Initialization
    const table = $('#selfBillTable').DataTable({
        "order": [[4, "desc"]], // Sort by date descending
        "paging": false,        // Use Laravel Pagination
        "info": false,
        "columnDefs": [
            { "orderable": false, "targets": [0, 7] }
        ]
    });

    // 2. Select All
    $('#selectAll').on('click', function() {
        $('.invoice-checkbox').prop('checked', this.checked);
    });

    // 3. Bulk Export
    $('#btnExportSelected').on('click', function() {
        const ids = $('.invoice-checkbox:checked').map(function() { return $(this).val(); }).get();
        if (ids.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Oops...', text: 'Please select at least one document.' });
            return;
        }
        window.location.href = "{{ route('self_invoice.export') }}?ids=" + ids.join(',');
    });

    // 4. Edit Modal Logic
    $('.edit-btn').on('click', function() {
        const id = $(this).data('id');
        let url = "{{ route('self_invoice.update', ':id') }}";
        $('#editInvoiceForm').attr('action', url.replace(':id', id));
        $('#edit_invoice_no').val($(this).data('no'));
        $('#edit_issue_date').val($(this).data('date'));
        $('#edit_price').val($(this).data('price'));
    });

    // 5. SweetAlert Delete
    $('.confirm-delete').on('click', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        Swal.fire({
            title: 'Delete Document?',
            text: "This action will remove the record from your local database.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endsection