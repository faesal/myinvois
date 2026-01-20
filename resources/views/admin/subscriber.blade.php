@extends('layouts.adminLayout')

@section('title', 'Manage Subscribers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-0">MySynctax Subscribers</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active text-uppercase small fw-bold text-muted">Admin Area Manage Subscriber</li>
            </ol>
        </nav>
    </div>
    
    {{-- NEW: Check Expired Button --}}
    <div>
        <form action="{{ route('admin.subscribers.check_expired') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger text-white shadow-sm">
                <i class="fa-solid fa-envelope me-2"></i> Check Expired & Email Me
            </button>
        </form>
    </div>
</div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.subscribers.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="Search by name or email..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Developer Name</label>
                        <select name="developer_name" class="form-select">
                            <option value="">All Developers</option>
                            @foreach($developers as $dev)
                                <option value="{{ $dev->id }}" {{ request('developer_name') == $dev->id ? 'selected' : '' }}>
                                    {{ $dev->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-dark w-100 py-2">
                            <i class="fa-solid fa-filter me-2"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="subscriberTable">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="ps-4 py-3">DEVELOPER</th>
                            <th>LHDN ACCOUNT NAME</th>
                            <th>API CREDENTIALS</th>
                            <th>STATUS</th>
                            <th>REGISTERED DATE</th>
                            <th>DATE START</th>
                            <th>DATE END</th>
                            <th class="text-end pe-4">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscribers as $sub)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar me-2" style="background: #eef2ff; color: #4338ca; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600;">
                                        {{ strtoupper(substr($sub->developer_name, 0, 2)) }}
                                    </div>
                                    <span class="fw-medium text-dark">{{ $sub->developer_name }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $sub->lhdn_account_name }}</div>
                                <div class="small text-muted">{{ $sub->email }}</div>
                            </td>
                            <td>
                                <div class="small text-nowrap"><strong>Key:</strong> <span class="text-muted">{{ $sub->mysynctax_key }}</span></div>
                                <div class="small text-nowrap"><strong>Sec:</strong> <span class="text-muted">{{ $sub->mysynctax_secret }}</span></div>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch" 
                                           {{ $sub->status ? 'checked' : '' }} 
                                           data-id="{{ $sub->id }}">
                                </div>
                            </td>
                            <td class="small">
                                {{ $sub->registered_date ? \Carbon\Carbon::parse($sub->registered_date)->format('d/m/Y') : '-' }}
                            </td>
                            <td>
                                <input type="date" class="form-control form-control-sm start-date border-light-subtle mb-1" 
                                       value="{{ $sub->date_start }}" 
                                       style="max-width: 130px;"
                                       data-id="{{ $sub->id }}">
                                <div class="small text-muted text-center display-date-start">
                                    {{ $sub->date_start ? \Carbon\Carbon::parse($sub->date_start)->format('d/m/Y') : '-' }}
                                </div>
                            </td>
                            <td>
                                <input type="date" class="form-control form-control-sm end-date bg-light border-0 mb-1" 
                                       value="{{ $sub->date_end }}" 
                                       style="max-width: 130px;"
                                       readonly>
                                <div class="small text-muted text-center display-date-end">
                                    {{ $sub->date_end ? \Carbon\Carbon::parse($sub->date_end)->format('d/m/Y') : '-' }}
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <a href="{{ route('developer.client.edit', $sub->id) }}" class="btn btn-sm btn-light text-primary border-0 rounded-circle" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    
                                    <button class="btn btn-sm btn-outline-dark px-3 rounded-pill border-light-subtle shadow-sm" style="font-size: 0.75rem;">Resend</button>
                                    
                                    <button class="btn btn-sm btn-light text-danger border-0 rounded-circle"><i class="fa-solid fa-trash"></i></button>

                                    {{-- NEW: Login / Impersonate Button --}}
                                    <a href="{{ route('admin.subscribers.impersonate', $sub->id) }}" 
                                       class="btn btn-sm btn-link text-decoration-none text-primary fw-bold p-0 ms-2"
                                       title="Login as Subscriber">
                                        Login <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted italic">
                                No subscribers found matching your criteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <p class="text-muted small mb-0">Showing {{ $subscribers->firstItem() }} to {{ $subscribers->lastItem() }} of {{ $subscribers->total() }} results</p>
                {{ $subscribers->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {

    // Auto Update Logic (Date)
    $(document).on('change', '.start-date', function() {
        let startDateVal = $(this).val();
        let $row = $(this).closest('tr');
        let id = $(this).data('id'); 
        
        if(startDateVal && id) {
            let date = new Date(startDateVal);
            let d = String(date.getDate()).padStart(2, '0');
            let m = String(date.getMonth() + 1).padStart(2, '0');
            let y = date.getFullYear();
            $row.find('.display-date-start').text(`${d}/${m}/${y}`);

            date.setFullYear(date.getFullYear() + 1);
            let nextY = date.getFullYear();
            let nextM = String(date.getMonth() + 1).padStart(2, '0');
            let nextD = String(date.getDate()).padStart(2, '0');
            let newEndDateDB = `${nextY}-${nextM}-${nextD}`;
            
            $row.find('.end-date').val(newEndDateDB);
            $row.find('.display-date-end').text(`${nextD}/${nextM}/${nextY}`);
            
            updateSubscriber(id, { date_start: startDateVal, date_end: newEndDateDB });
        }
    });

    // Handle Status Toggle
    $(document).on('change', '.status-toggle', function() {
        let id = $(this).data('id');
        let status = $(this).is(':checked'); 
        if(id) { updateSubscriber(id, { status: status }); }
    });

    // Core AJAX Update Function
    function updateSubscriber(id, data) {
        data._token = "{{ csrf_token() }}";
        $.ajax({
            url: "{{ route('admin.subscribers.update', '') }}/" + id,
            method: "POST",
            data: data,
            dataType: "json",
            success: function(response) {
                if(response.success) {
                    const Toast = Swal.mixin({
                        toast: true, position: "top-end", showConfirmButton: false, timer: 2000, timerProgressBar: true
                    });
                    Toast.fire({ icon: "success", title: response.message || "Updated successfully" });
                }
            },
            error: function(xhr) {
                console.error("Server Error:", xhr.responseText);
                Swal.fire({ icon: 'error', title: 'Auto-save Failed', text: 'An error occurred while saving.' });
            }
        });
    }
});
</script>
@endsection