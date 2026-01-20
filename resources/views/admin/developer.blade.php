@extends('layouts.adminLayout')

@section('title', 'Manage Developers')

@section('content')
<style>
    /* Custom Styling for the Stat Cards */
    .stat-card {
        transition: all 0.2s ease-in-out;
        border: 1px solid #f3f4f6;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .stat-icon-wrapper {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }
    .avatar-circle {
        width: 40px;
        height: 40px;
        background-color: #f3f4f6;
        color: #4b5563;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    /* Modal Styling */
    .modal-content {
        border-radius: 15px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">MySynctax Developers</h3>
            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Admin Panel</small>
        </div>
        <button class="btn btn-dark px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addDeveloperModal">
            <i class="fa-solid fa-plus me-2"></i> Add Developer
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="flex-grow-1">
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Developers</h6>
                        <h2 class="fw-bold mb-0 text-dark" id="total-count">{{ $totalDevelopers ?? 0 }}</h2>
                    </div>
                    <div class="stat-icon-wrapper bg-light text-primary">
                        <i class="fa-solid fa-users fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100 border-start border-success border-4">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="flex-grow-1">
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Active</h6>
                        <h2 class="fw-bold mb-0 text-success" id="active-count">{{ $activeDevelopers ?? 0 }}</h2>
                    </div>
                    <div class="stat-icon-wrapper bg-success-subtle text-success">
                        <i class="fa-solid fa-circle-check fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="flex-grow-1">
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Inactive</h6>
                        <h2 class="fw-bold mb-0 text-secondary" id="inactive-count">{{ $inactiveDevelopers ?? 0 }}</h2>
                    </div>
                    <div class="stat-icon-wrapper bg-light text-muted">
                        <i class="fa-solid fa-circle-pause fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.developers.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0 py-2" placeholder="Search developers by name or email....">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-secondary w-100 py-2">
                            <i class="fa-solid fa-sliders me-2"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-4 py-3">Developer</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($developers as $dev)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3">
                                        {{ strtoupper(substr($dev->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $dev->name }}</div>
                                        <div class="small text-muted">Developer</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted">{{ $dev->email }}</td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-toggle" type="checkbox" role="switch" 
                                           {{ $dev->status ? 'checked' : '' }}
                                           data-id="{{ $dev->id }}">
                                </div>
                            </td>
                            <td class="text-muted">{{ \Carbon\Carbon::parse($dev->created_at)->format('d M, Y') }}</td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end align-items-center gap-3">
                                    <form action="{{ route('admin.developers.resend', $dev->id) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-link text-decoration-none text-muted fw-medium p-0" title="Resend Credential">
                                            <i class="fa-regular fa-envelope"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.developers.reset', $dev->id) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-link text-decoration-none text-muted fw-medium p-0" title="Reset Password">
                                            <i class="fa-solid fa-key"></i>
                                        </button>
                                    </form>

                                    {{-- NEW: Delete Button --}}
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-danger p-0 delete-developer" 
                                            data-id="{{ $dev->id }}" 
                                            data-name="{{ $dev->name }}" 
                                            title="Delete Developer">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>

                                    <a href="{{ route('admin.developers.impersonate', $dev->id) }}" class="btn btn-sm btn-link text-decoration-none text-primary fw-bold p-0">
                                        Login <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No developers found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <p class="text-muted small mb-0">Showing {{ $developers->firstItem() }} to {{ $developers->lastItem() }} of {{ $developers->total() }} results</p>
                {{ $developers->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addDeveloperModal" tabindex="-1" aria-labelledby="addDeveloperModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold" id="addDeveloperModalLabel">Add New Developer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addDeveloperForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter developer name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="example@mysynctax.com" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Create password" required minlength="8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password" required>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">An automated welcome email will be sent to the developer after saving.</small>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark px-4" id="saveBtn">Save Developer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    
    /**
     * 1. Handle "Add Developer" Form via AJAX
     */
    $('#addDeveloperForm').on('submit', function(e) {
        e.preventDefault();
        
        const saveBtn = $('#saveBtn');
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.ajax({
            url: "{{ url('/admin/developers/store') }}", 
            method: "POST",
            data: $(this).serialize(),
            success: function(response) {
                let totalEl = $('#total-count');
                let inactiveEl = $('#inactive-count');
                if(totalEl.length) totalEl.text(parseInt(totalEl.text()) + 1);
                if(inactiveEl.length) inactiveEl.text(parseInt(inactiveEl.text()) + 1);

                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                saveBtn.prop('disabled', false).text('Save Developer');
                let errorMessage = 'Something went wrong.';
                if (xhr.status === 422) {
                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else {
                    errorMessage = xhr.responseJSON?.message || errorMessage;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMessage
                });
            }
        });
    });

    /**
     * 2. Status Toggle Logic
     */
    $(document).on('change', '.status-toggle', function() {
        let id = $(this).data('id');
        let isChecked = $(this).is(':checked');
        let finalUrl = "{{ url('/admin/developers') }}/" + id + "/update";
        let $checkbox = $(this);

        let activeEl = $('#active-count');
        let inactiveEl = $('#inactive-count');
        
        let activeVal = parseInt(activeEl.text()) || 0;
        let inactiveVal = parseInt(inactiveEl.text()) || 0;

        if (isChecked) {
            activeEl.text(activeVal + 1);
            inactiveEl.text(Math.max(0, inactiveVal - 1));
        } else {
            activeEl.text(Math.max(0, activeVal - 1));
            inactiveEl.text(inactiveVal + 1);
        }

        $.ajax({
            url: finalUrl,
            method: "POST",
            data: {
                status: isChecked,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });

                Toast.fire({
                    icon: "success",
                    title: response.message || "Status updated"
                });

                setTimeout(function() {
                    location.reload();
                }, 2000);
            },
            error: function(xhr) {
                if (isChecked) {
                    activeEl.text(activeVal);
                    inactiveEl.text(inactiveVal);
                } else {
                    activeEl.text(activeVal);
                    inactiveEl.text(inactiveVal);
                }
                $checkbox.prop('checked', !isChecked);
                
                let errorMessage = xhr.responseJSON?.message || 'Failed to update status.';
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: errorMessage
                });
            }
        });
    });

    /**
     * 3. Handle Delete Developer Logic
     */
    $(document).on('click', '.delete-developer', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');
        let deleteUrl = "{{ url('/admin/developers') }}/" + id + "/delete"; 

        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete developer: " + name + ". This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl,
                    method: "DELETE",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message || 'Developer has been removed.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = xhr.responseJSON?.message || 'Failed to delete developer.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    });
});
</script>
@endsection