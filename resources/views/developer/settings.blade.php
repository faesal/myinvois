@extends('layouts.developerLayout')

@section('title', 'IP & Consolidation Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Setting IP & Consolidate</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active text-uppercase small fw-bold text-muted">Developer Integration Settings</li>
                </ol>
            </nav>
        </div>
        <div>
            <button type="button" class="btn btn-outline-secondary me-2 px-4">Cancel</button>
            <button type="button" class="btn btn-primary px-4 shadow-sm" id="saveAllSettings">
                <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary p-2 rounded me-3">
                            <i class="fa-solid fa-calendar-check fa-lg"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Consolidation Frequency</h5>
                    </div>
                    <p class="text-muted small mb-4">Select how often you want to consolidate e-Invoice data for your subscribers.</p>
                    
                    <form id="consolidationForm">
                        <div class="space-y-3">
                            <div class="form-check p-3 border rounded-3 mb-3 hover-shadow-sm transition">
                                <input class="form-check-input ms-0 me-3" type="radio" name="frequency" id="freqDaily" value="daily" checked>
                                <label class="form-check-label w-100" for="freqDaily">
                                    <span class="fw-bold d-block">Daily</span>
                                    <span class="small text-muted">Automatically consolidate at the end of each day (11:59 PM).</span>
                                </label>
                            </div>

                            <div class="form-check p-3 border rounded-3 mb-3 hover-shadow-sm transition">
                                <input class="form-check-input ms-0 me-3" type="radio" name="frequency" id="freqWeekly" value="weekly">
                                <label class="form-check-label w-100" for="freqWeekly">
                                    <span class="fw-bold d-block">Weekly</span>
                                    <span class="small text-muted">Consolidate every Sunday at midnight.</span>
                                </label>
                            </div>

                            <div class="form-check p-3 border rounded-3 mb-4">
                                <input class="form-check-input ms-0 me-3" type="radio" name="frequency" id="freqMonthly" value="monthly">
                                <label class="form-check-label w-100" for="freqMonthly">
                                    <span class="fw-bold d-block">Monthly</span>
                                    <span class="small text-muted">Consolidate on the last day of each calendar month.</span>
                                </label>
                            </div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <h6 class="fw-bold mb-3">Additional Preferences</h6>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="notifyEmail" checked>
                            <label class="form-check-label small" for="notifyEmail">Send email notification when consolidated invoice is generated</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="includePaid">
                            <label class="form-check-label small" for="includePaid">Include already paid invoices in consolidation</label>
                        </div>
                    </form>

                    <div class="mt-4 p-3 bg-light rounded-3 border-start border-primary border-4">
                        <div class="small fw-bold text-uppercase text-muted mb-2" style="font-size: 10px;">Next Scheduled Consolidation</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">December 31, 2024 at 11:59 PM</span>
                            <span class="badge bg-primary">15 Invoices pending</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-dark text-white p-2 rounded me-3">
                            <i class="fa-solid fa-shield-halved fa-lg"></i>
                        </div>
                        <h5 class="fw-bold mb-0">IP Whitelist Management</h5>
                    </div>
                    <p class="text-muted small mb-4">Manage IP addresses allowed to access your API and e-Invoice services.</p>

                    <form id="addIpForm" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-7">
                                <input type="text" class="form-control" name="ip_address" placeholder="IP Address (e.g. 192.168.1.1)" required>
                            </div>
                            <div class="col-md-5">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="description" placeholder="Description">
                                    <button class="btn btn-dark" type="submit">Add IP</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-top" id="ipTable">
                            <thead class="bg-light">
                                <tr class="small text-uppercase text-muted">
                                    <th class="py-3">IP Address</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code class="fw-bold text-dark">192.168.1.100</code></td>
                                    <td class="small text-muted">Main Office Network</td>
                                    <td><span class="badge bg-success-subtle text-success rounded-pill px-3">Active</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-link text-danger p-0 delete-ip"><i class="fa-solid fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code class="fw-bold text-dark">10.0.0.50</code></td>
                                    <td class="small text-muted">Development Server</td>
                                    <td><span class="badge bg-success-subtle text-success rounded-pill px-3">Active</span></td>
                                    <td class="text-end">
                                        <button class="btn btn-link text-danger p-0 delete-ip"><i class="fa-solid fa-trash-can"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="form-check form-check-inline small">
                            <input class="form-check-input" type="checkbox" id="autoBlock" checked>
                            <label class="form-check-label text-muted" for="autoBlock">Auto-block IPs after 5 failed attempts</label>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary px-3">
                            <i class="fa-solid fa-file-export me-1"></i> Export List
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // 1. Save All Settings Action
    $('#saveAllSettings').click(function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        // Simulate AJAX request
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'Settings Saved',
                text: 'Your consolidation and IP preferences have been updated.',
                confirmButtonColor: '#6366f1'
            });
            btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i> Save Changes');
        }, 1000);
    });

    // 2. Delete IP Confirmation
    $(document).on('click', '.delete-ip', function() {
        const row = $(this).closest('tr');
        Swal.fire({
            title: 'Remove IP?',
            text: "This IP will no longer be able to access the API.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                row.fadeOut(300, function() { $(this).remove(); });
                Swal.fire('Deleted!', 'IP Whitelist updated.', 'success');
            }
        });
    });
});
</script>
@endsection