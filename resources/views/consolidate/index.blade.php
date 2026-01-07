@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><i class="ph-file-csv me-2"></i> LHDN Data Consolidation</h4>
        <div class="text-muted">
            <i class="ph-user-circle me-1"></i> Admin User
        </div>
    </div>

    {{-- ALERT MESSAGES --}}
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-12">
            <h6 class="fw-bold mb-3"><i class="ph-cloud-arrow-up me-2"></i>Data Upload</h6>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border">
                <div class="card-body d-flex flex-column justify-content-center align-items-center p-5 text-center">
                    <form action="{{ route('consolidate.import') }}" method="POST" enctype="multipart/form-data" id="uploadForm" class="w-100">
                        @csrf
                        <div class="upload-area p-4 border rounded bg-light" 
                             style="border: 2px dashed #ccc; cursor: pointer; transition: all 0.3s ease;" 
                             onclick="document.getElementById('fileInput').click();"
                             onmouseover="this.style.borderColor='#2196f3'; this.style.backgroundColor='#f8faff';"
                             onmouseout="this.style.borderColor='#ccc'; this.style.backgroundColor='#f8f9fa';">
                            
                            <i class="ph-upload-simple text-secondary mb-3" style="font-size: 48px;"></i>
                            <h6 class="fw-bold">Click to upload CSV file</h6>
                            <p class="text-muted small mb-0">or drag and drop</p>
                            <p class="text-muted small mt-2">Format: .CSV Only (Max 10MB)</p>
                            
                            <input type="file" name="file" id="fileInput" class="d-none" accept=".csv, text/csv" onchange="document.getElementById('uploadForm').submit();">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <h6 class="fw-bold mb-3"><i class="ph-download-simple me-2"></i>Download Template</h6>
                    <p class="text-muted small mb-4">
                        Use our template to ensure proper data format for LHDN submission.
                        <br><span class="text-danger">*Please save as CSV (Comma delimited) before uploading.</span>
                    </p>
                    <a href="{{ route('consolidate.template') }}" class="btn btn-dark w-100 py-2">
                        Download CSV Template
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Consolidate Data</h6>
                <div class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="ph-magnifying-glass text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Search invoices...">
                    </div>
                    <button class="btn btn-outline-secondary"><i class="ph-funnel me-1"></i> Filter</button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Invoice ID</th>
                            <th>Sale ID / Batch</th>
                            <th>Amount (RM)</th>
                            <th>Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consolidations as $batch)
                        <tr>
                            <td class="ps-4 fw-bold text-dark">{{ $batch->invoice_no }}</td>
                            <td class="text-secondary">{{ $batch->unique_id }}</td>
                            <td class="fw-bold">{{ number_format($batch->consolidate_complete_total, 2) }}</td>
                            <td class="text-secondary">{{ \Carbon\Carbon::parse($batch->created_at)->format('Y-m-d') }}</td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    {{-- VIEW BUTTON --}}
                                    <a href="{{ route('consolidate.view', $batch->id_invoice) }}" class="btn btn-sm btn-light border" title="View">
                                        <i class="ph-eye"></i>
                                    </a>

                                    {{-- EDIT BUTTON (Blue) --}}
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $batch->id_invoice }}" title="Edit">
                                        <i class="ph-pencil"></i>
                                    </button>

                                    {{-- DELETE BUTTON (Red) --}}
                                    <a href="{{ route('consolidate.delete', $batch->id_invoice) }}" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this data?')"
                                       title="Delete">
                                        <i class="ph-trash"></i>
                                    </a>
                                </div>

                               {{-- EDIT MODAL FOR EACH ROW --}}
<div class="modal fade" id="editModal{{ $batch->id_invoice }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('consolidate.update', $batch->id_invoice) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Consolidated Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Invoice ID / Number</label>
                        <input type="text" name="invoice_no" class="form-control" value="{{ $batch->invoice_no }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Sale ID / Batch (Unique ID)</label>
                        <input type="text" name="unique_id" class="form-control" value="{{ $batch->unique_id }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Amount (RM)</label>
                        <div class="input-group">
                            <span class="input-group-text">RM</span>
                            <input type="number" step="0.01" name="amount" class="form-control" value="{{ $batch->consolidate_complete_total }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Date (YYYY-MM-DD)</label>
                        <input type="date" name="created_at" class="form-control" value="{{ \Carbon\Carbon::parse($batch->created_at)->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Database</button>
                </div>
            </div>
        </form>
    </div>
</div>
{{-- END MODAL --}}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="ph-folder-open text-muted mb-2" style="font-size: 32px;"></i>
                                <p class="text-muted mb-0">No consolidated data found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">
                    Showing {{ $consolidations->firstItem() ?? 0 }} to {{ $consolidations->lastItem() ?? 0 }} of {{ $consolidations->total() }} results
                </div>
                <div>
                    {{ $consolidations->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

</div>
@endsection