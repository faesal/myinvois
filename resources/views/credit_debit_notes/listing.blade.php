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

    // ✅ Gunakan route universal berdasarkan prefix URL yang dinamik
    $routeCreate = route('note.create', ['note_type' => $noteType . '_note']);
    $labelNew = 'New ' . rtrim($title, 's');
@endphp

<div class="container">
    <h2 class="mb-4 fw-bold">{{ $title }}</h2>

    <div class="alert alert-success alert-dismissible">
        <div class="alert-heading fw-semibold">Note</div>
        <p class="text-muted">Manage and track all {{ strtolower($title) }} in the system</p>
    </div>

    <div class="card">
    <div class="card-body">
        <!-- Statistik Panel -->
        <section class="mb-1">
            <br>
            <div class="row justify-content-center g-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white rounded-xl shadow-lg overflow-hidden text-center">
                        <div class="card-body p-3">
                            <h3 class="fw-bold mb-2">{{ $total }}</h3>
                            <div class="fw-semibold">Total {{ $title }}</div>
                            <div class="text-sm opacity-75">Created in the system</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-success text-white rounded-xl shadow-lg overflow-hidden text-center">
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

    <hr>

    <!-- Action Button -->
    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"></h5>
            <a href="{{ $routeCreate }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> {{ $labelNew }}
            </a>
        </div>

        <!-- Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered" id="datatable-items">
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
                                    <a href="#" class="text-primary fw-semibold">{{ $note->invoice_no }}</a><br>
                                    <small class="text-muted">UUID: {{ $note->uuid }}</small>
                                </td>
                                <td>
                                    {{ $note->supplier_name }}<br>
                                    <small class="text-muted">TIN: {{ $note->supplier_tin }}</small>
                                </td>
                                <td>
                                    {{ $note->customer_name }}<br>
                                    <small class="text-muted">{{ $note->customer_email }}</small>
                                </td>
                                <td>RM {{ number_format($note->price, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($note->issue_date)->format('d-m-Y') }}</td>
                                <td>
                                    @if ($note->submission_status == 'submitted')
                                        <span class="badge bg-success bg-opacity-20 text-success">Submitted</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-20 text-danger">Failed</span>
                                    @endif
                                </td>

                                
                                <td> <a target="_blank" href="{{url('/show_invoice')}}/{{$note->id_supplier}}/{{$note->id_customer}}/{{$note->id_invoice}}" class="text-primary">View</a>

                               <!-- <a target="_blank" href="{{url('/invoice/resubmit')}}/{{$note->id_invoice}}" class="text-primary">Resubmit</a>-->
                                @if ($note->uuid != '')
                                <a href="{{ url('/cncelDocument') }}/{{ $note->uuid }}" 
                                class="cancel-link text-danger">
                                Cancel
                                </a>
                                @endif
                            
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

<!-- DataTable Script -->
<script>
$(document).ready(function () {
    const table = $('#datatable-items').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        pageLength: 50,
        initComplete: function () {
            $('#datatable-items_length select').addClass('form-select form-select-sm');
        }
    });

    $('#searchBox').on('keyup', function () {
        table.search(this.value).draw();
    });
});
</script>

<!-- Styling -->
<style>
.dataTables_filter { float: right !important; }
.dataTables_length select {
    width: auto;
    display: inline-block;
    padding: 10px 20px;
}
.dataTables_filter label,
.dataTables_length label {
    font-size: 0.9rem;
}
</style>

<script>
document.querySelectorAll('.cancel-link').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        const href = this.href;

        Swal.fire({
            icon: 'warning',
            title: 'Warning!',
            text: 'Are you sure you want to cancel this document?',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel',
            confirmButtonColor: '#ef4444',
            cancelButtonText: 'No',
            cancelButtonColor: '#6b7280'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
});
</script>

@endsection
