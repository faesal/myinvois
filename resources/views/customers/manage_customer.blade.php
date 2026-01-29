{{-- 
    DYNAMIC LAYOUT LOGIC:
    The $layout variable is passed from ManageCustomerController.php
--}}
@extends($layout)

@section('title', 'Manage Customer')

@section('content')
{{-- Load Phosphor Icons via CDN --}}
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
    /* Apple-like soft action buttons */
    .btn-light-primary { 
        background-color: #e7f1ff; 
        color: #0d6efd; 
        transition: all 0.2s ease;
    }
    .btn-light-primary:hover { 
        background-color: #0d6efd; 
        color: #fff; 
        transform: translateY(-1px);
    }
    .btn-light-danger { 
        background-color: #ffe5e5; 
        color: #dc3545; 
        transition: all 0.2s ease;
    }
    .btn-light-danger:hover { 
        background-color: #dc3545; 
        color: #fff; 
        transform: translateY(-1px);
    }
    /* Ensures the icon stays centered inside the circle */
    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    /* Select styling */
    .form-select-sm {
        font-size: 0.8rem;
    }
</style>

<div class="container-fluid">
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Customer Listing</h3>
            <p class="text-muted small mb-0">Manage and view customer information</p>
        </div>
        <div>
            {{-- Passing the specific customer ID context to the create page --}}
            <a href="{{ route('manage_customer.create', ['lhdn_cust_id' => request('lhdn_cust_id')]) }}" class="btn btn-primary shadow-sm fw-medium px-4">
                <i class="ph ph-plus-circle me-1"></i> Add New
            </a>
        </div>
    </div>

    {{-- Filters & Actions Toolbar --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('manage_customer.index') }}" id="filterForm">
                <div class="row g-2 align-items-center">
                    @if(Auth::user()->role !== 'subscriber')
                    <div class="col-md-3 d-flex align-items-center">
                        <label class="small fw-bold text-muted me-2 text-nowrap">LHDN Account:</label>
                        {{-- 
                            ULTIMATE FIX: 
                            Using lhdn_cust_id (Primary Key of customer record) instead of connection codes.
                            This makes every option mathematically unique so the browser cannot jump 
                            between different suppliers like Degree Venture and Winner.
                        --}}
                        <select name="lhdn_cust_id" class="form-select form-select-sm border-light-subtle" onchange="document.getElementById('filterForm').submit()">
                            <option value="">-- All Suppliers --</option>
                            @foreach($lhdnAccounts as $acc)
                                <option value="{{ $acc->id_customer }}" {{ request('lhdn_cust_id') == $acc->id_customer ? 'selected' : '' }}>
                                    {{ strtoupper($acc->supplier_name ?? $acc->connection_name ?? $acc->code) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="{{ Auth::user()->role === 'subscriber' ? 'col-md-12' : 'col-md-9' }} d-flex justify-content-end gap-2 flex-wrap">
                        {{-- MOVED: Download Template Button is now here --}}
                        <a href="{{ route('manage_customer.download_template') }}" class="btn btn-light border shadow-sm btn-sm px-3" title="Download CSV Template">
                            <i class="ph ph-download-simple me-1"></i> Template
                        </a>

                        <button type="button" class="btn btn-light border shadow-sm btn-sm px-3" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="ph ph-file-arrow-up me-1"></i> Import
                        </button>
                        
                        <a href="{{ route('manage_customer.export') }}" class="btn btn-light border shadow-sm btn-sm px-3">
                            <i class="ph ph-file-arrow-down me-1"></i> Export Data
                        </a>
                        
                        <div class="input-group input-group-sm" style="max-width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="ph ph-magnifying-glass"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="Search customers..." value="{{ request('search') }}">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead>
                        <tr class="text-muted small fw-bold">
                            <th class="ps-4 py-3" style="width: 80px;">Self Bill</th>
                            <th>Description</th>
                            <th>TIN No</th>
                            <th>Registration Name</th>
                            <th>ID No</th>
                            <th>Type</th>
                            <th>SST</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-center" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td class="ps-4 text-center">
                                @if($customer->is_selfbill_supplier == 1)
                                    <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Yes</span>
                                @else
                                    <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">No</span>
                                @endif
                            </td>
                            <td>{{ $customer->business_description ?? '-' }}</td>
                            <td class="fw-bold text-dark">{{ $customer->tin_no ?? '-' }}</td>
                            <td class="fw-medium">{{ $customer->registration_name ?? '-' }}</td>
                            <td>{{ $customer->identification_no ?? '-' }}</td>
                            <td class="text-uppercase text-muted small">{{ $customer->identification_type ?? '-' }}</td>
                            <td>
                                @if($customer->sst_registration == 1)
                                    <i class="ph ph-check-circle text-success fs-5"></i>
                                @else
                                    <i class="ph ph-minus text-muted opacity-50"></i>
                                @endif
                            </td>
                            <td>{{ $customer->phone ?? '-' }}</td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    {{-- Edit Button --}}
                                    <a href="{{ route('manage_customer.edit', $customer->id_customer) }}" 
                                       class="action-btn btn btn-sm btn-light-primary rounded-circle" 
                                       title="Edit">
                                        <i class="ph ph-pencil-simple"></i>
                                    </a>

                                    {{-- Delete Button --}}
                                    <form action="{{ route('manage_customer.destroy', $customer->id_customer) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this customer?')">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="action-btn btn btn-sm btn-light-danger rounded-circle" title="Delete">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">No customers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Pagination --}}
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <p class="text-muted small mb-0">
                    Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} results
                </p>
                {{ $customers->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Customers from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('manage_customer.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                {{-- Passing unique customer ID context --}}
                <input type="hidden" name="lhdn_cust_id" value="{{ request('lhdn_cust_id') }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="ph ph-info me-1"></i> 
                        Please ensure your CSV headers match exactly with the downloaded template to avoid errors.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Start Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection