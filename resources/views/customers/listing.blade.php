@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="fw-bold mb-2">Customer Management</h4>
   

    <div class="alert alert-success alert-dismissible">
        <div class="alert-heading fw-semibold">Note</div>
        <p class="text-muted">Manage and view all customer records</p>
    </div>

    <!-- Filter Panel -->
    <!--<form method="POST" action="{{ url('/customer/listing_customer') }}" class="card mb-4 p-4">
    {{ csrf_field() }}
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label>Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search customers..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label>Customer Type</label>
                <input type="text" name="customer_type" class="form-control" placeholder="e.g. GOV, INDIVIDUAL" value="{{ request('customer_type') }}">
            </div>
            <div class="col-md-3">
                <label>Country</label>
                <input type="text" name="country" class="form-control" placeholder="e.g. MYS" value="{{ request('country') }}">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
        </div>
    </form>-->

    <!-- Customer List -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end align-items-center mb-3">
                <!--<h5 class="mb-0">Customer List <span class="badge bg-secondary">{{ $customers->count() }} records</span></h5>-->
                <a href="{{ url('/customer/form_customer') }}" class="btn btn-primary">New Customer</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="customerTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Customer Details</th>
                            <th>Contact Info</th>
                            <th>Tax Info</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $cust)
                            <tr>
                                <td>#{{ str_pad($cust->id_customer, 6, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <strong>{{ $cust->registration_name }}</strong><br>
                                    ID: {{ $cust->identification_no ?? '-' }}<br>
                                    @if($cust->identification_type == 'NRIC')
                                        NRIC: {{ $cust->identification_no ?? '-' }}
                                    @elseif($cust->identification_type == 'BRN')
                                        REG: {{ $cust->registration_no ?? '-' }}
                                    @endif
                                </td>
                                <td>
                                    {{ $cust->phone ?? '-' }}<br>
                                    {{ $cust->email ?? '-' }}
                                </td>
                                <td>
                                    TIN: {{ $cust->tin_no ?? '-' }}<br>
                                    SST: {{ $cust->sst_registration ?? '-' }}
                                </td>
                                <td>
                                    {{ $cust->city_name ?? '-' }}<br>
                                    {{ $cust->postal_zone ?? '-' }}, {{ $cust->country_code ?? 'MYS' }}
                                </td>
                                <td>
                                    <a href="{{ url('/customer/form_customer/' . $cust->id_customer) }}" class="btn btn-sm btn-outline-primary">✎</a>
                                    <form method="POST" action="{{ url('/customer/destroy/' . $cust->id_customer) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button onclick="return confirm('Are you sure to delete this customer?')" class="btn btn-sm btn-outline-danger">🗑</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No customers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>



<!-- DataTables CSS & JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        $('#customerTable').DataTable({
            pageLength: 10,
            responsive: true,
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: -1 } // disable sorting on Actions column
            ]
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
@endsection
