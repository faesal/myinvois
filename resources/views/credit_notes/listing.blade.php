@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4 fw-bold">Credit Notes</h2>
    <div class="card">
    <div class="card-body">
    <div class="alert alert-success alert-dismissible">
					<div class="alert-heading fw-semibold">Note</div>
                    <p class="text-muted">Manage and track all credit notes in the system</p>
			    </div>
        <!-- Table -->

   

    <!-- Panel Statistik (Grid Layout) -->
    <section class="mb-1">
        <br>
        <div class="row justify-content-center g-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white rounded-xl shadow-lg overflow-hidden text-center">
                    <div class="card-body p-3">
                        <h3 class="fw-bold mb-2">{{ $total }}</h3>
                        <div class="fw-semibold">Total Credit Notes</div>
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

</div>
</div>
<hr>
    <!-- Panel Senarai -->
    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"></h5>
            <a href="{{ route('credit-note.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> New Credit Note
            </a>
        </div>
    <!-- Table -->
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="datatable-items">
                <thead class="table-light">
                    <tr>
                      
                        <th>Credit Note #</th>
                        <th>Company</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>LHDN Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($creditNotes as $note)
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
                            @if ($note->invoice_status == 'submitted')
                                <span class="badge bg-success">Submitted</span>
                            @else
                                <span class="badge bg-secondary">Draft</span>
                            @endif
                        </td>
                        <td><a href="#" class="text-primary">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $creditNotes->links() }}
        </div>
    </section>
</div>
</div>
</div>
<script>
$(document).ready(function () {
    const table = $('#datatable-items').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        pageLength: 50,
        initComplete: function () {
            // Style the length dropdown
            $('#datatable-items_length select').addClass('form-select form-select-sm');
        }
    });

    $('#searchBox').on('keyup', function () {
        table.search(this.value).draw();
    });
});
</script>

<style>
/* Align search box to right */
.dataTables_filter {
    float: right !important;
}

/* Smaller dropdown */
.dataTables_length select {
    width: auto;
    display: inline-block;
    padding: 10px 20px;
}

/* Optional: Smaller font for controls */
.dataTables_filter label,
.dataTables_length label {
    font-size: 0.9rem;
}
</style>
@endsection
