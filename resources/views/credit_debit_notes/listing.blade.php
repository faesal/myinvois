@extends('layouts.app')

@section('content')
@php
    $noteType = $noteType ?? 'credit';

    $title = match($noteType) {
        'credit' => 'Credit Notes',
        'debit' => 'Debit Notes',
        'refund' => 'Refund Notes',
        default => 'Notes'
    };

    // ✅ UPDATED: Use custom route if provided (for Self-Bill), otherwise use default
    $routeCreate = $customCreateRoute ?? route('note.create', ['note_type' => $noteType . '_note']);
    
    $labelNew = 'New ' . rtrim($title, 's');
@endphp

<div class="container">
    <h2 class="mb-4 fw-bold">{{ $title }}</h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="alert alert-success alert-dismissible">
        <div class="alert-heading fw-semibold">Note</div>
        <p class="text-muted">Manage and track all {{ strtolower($title) }} in the system</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <section class="mb-1">
                <br>
                <div class="row justify-content-center g-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white rounded-xl shadow-lg overflow-hidden text-center border-0">
                            <div class="card-body p-3">
                                <h3 class="fw-bold mb-2">{{ $total }}</h3>
                                <div class="fw-semibold">Total {{ $title }}</div>
                                <div class="text-sm opacity-75">Created in the system</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-success text-white rounded-xl shadow-lg overflow-hidden text-center border-0">
                            <div class="card-body p-3">
                                <h3 class="fw-bold mb-2">{{ $submitted }}</h3>
                                <div class="fw-semibold">Submitted</div>
                                <div class="text-sm opacity-75">Sent to LHDN</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <hr class="my-4">

    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-bold">Records</h5>
            <a href="{{ $routeCreate }}" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> {{ $labelNew }}
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle" id="datatable-items">
                        <thead class="table-light">
                            <tr>
                                <th>{{ ucfirst($noteType) }} Note #</th>
                                <th>Company</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($notes as $note)
                            <tr>
                                <td>
                                    <span class="text-primary fw-bold">{{ $note->invoice_no }}</span><br>
                                    <small class="text-muted">UUID: {{ $note->uuid ?: 'Not Generated' }}</small>
                                </td>
                                <td>
                                    {{ $note->supplier_name }}<br>
                                    <small class="text-muted">TIN: {{ $note->supplier_tin }}</small>
                                </td>
                                <td>
                                    {{ $note->customer_name }}<br>
                                    <small class="text-muted">{{ $note->customer_email }}</small>
                                </td>
                                <td class="fw-semibold">RM {{ number_format($note->price, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($note->issue_date)->format('d-m-Y') }}</td>
                                <td>
                                    @if (strtolower($note->submission_status) == 'submitted' || !empty($note->uuid))
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2">Submitted</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2">Failed</span>
                                    @endif
                                </td>
                                <td> 
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a target="_blank" href="{{ route('invoice.view.public', ['unique_id' => $note->unique_id]) }}" class="btn btn-sm btn-outline-primary px-3">View</a>

                                        @if (empty($note->uuid))
                                            <form action="{{ route('self_bill_note.destroy', ['note_type' => $noteTypeSlug, 'id' => $note->id_invoice]) }}" method="POST" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-3">Delete</button>
                                            </form>
                                        @endif

                                        @if ($note->uuid != '')
                                            {{-- ✅ UPDATED: Use unique_id in URL and AJAX Logic --}}
                                            <button type="button" 
                                                    data-url="{{ url('/api/myinvois/cancelDocument/'.$note->unique_id) }}" 
                                                    class="btn btn-sm btn-danger cancel-link px-3">
                                                Cancel
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $notes->links() }}
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
    const table = $('#datatable-items').DataTable({
        paging: false, 
        searching: true,
        ordering: true,
        info: false,
        initComplete: function () {
            $('#datatable-items_length select').addClass('form-select form-select-sm');
        }
    });

    // Custom SweetAlert for Delete
    $('.delete-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Delete Note?',
            text: "This action cannot be undone. Original items will be released.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // ✅ UPDATED: Cancel Function matching submission.blade.php logic
    $(document).on('click', '.cancel-link', function (e) {
        e.preventDefault();
        const cancelUrl = $(this).data('url');
        
        Swal.fire({
            title: 'Cancel Document?',
            text: 'This will void the document on LHDN. This action is permanent.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel It',
            confirmButtonColor: '#ef4444',
            cancelButtonText: 'No',
            cancelButtonColor: '#6b7280'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: cancelUrl,
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}", 
                        reason: "Wrong invoice details"
                    },
                    beforeSend: function() {
                        Swal.fire({ title: 'Cancelling...', didOpen: () => { Swal.showLoading(); } });
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cancelled!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    },
                    error: function(xhr) {
                        let msg = 'Failed to cancel document.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    });
});
</script>

<style>
.card { border-radius: 12px; }
.rounded-xl { border-radius: 1rem; }
.badge { font-weight: 500; font-size: 0.85rem; }
.table thead th { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
.dataTables_filter { float: right !important; margin-bottom: 1rem; }
.dataTables_filter input { border-radius: 6px; border: 1px solid #dee2e6; padding: 5px 10px; }

/* ✅ FIX: Prevent SweetAlert Icon from being too big */
.swal2-icon {
    font-size: 1rem !important; 
    width: 5em !important;
    height: 5em !important;
    margin: 2.5em auto .6em !important;
}
</style>

@endsection