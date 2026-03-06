@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="fw-bold mb-2">Customer Management</h4>
   
    <div class="alert alert-success alert-dismissible">
        <div class="alert-heading fw-semibold">Note</div>
        <p class="text-muted">Manage and view all customer records</p>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end align-items-center mb-3">
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
                                    <form method="POST" action="{{ url('/customer/destroy/' . $cust->id_customer) }}" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete">🗑</button>
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

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
    $(document).ready(function () {
        $('#customerTable').DataTable({
            pageLength: 10,
            responsive: true,
            order: [[0, 'desc']], // Default sorting
            
            // DOM Layout
            dom: "<'row mb-3 align-items-center'<'col-sm-12 col-md-auto'l><'col-sm-12 col-md-auto me-auto'B><'col-sm-12 col-md-auto'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                 
            buttons: [
                {
                    extend: 'copy',
                    text: 'Copy',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: { columns: ':not(:last-child)' },
                    action: function(e, dt, node, config) {
                        $.fn.dataTable.ext.buttons.copyHtml5.action.call(this, e, dt, node, config);
                        Swal.fire({
                            title: 'Copied!',
                            text: 'Customer records copied to clipboard.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: { columns: ':not(:last-child)' },
                    action: function(e, dt, node, config) {
                        $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, node, config);
                        Swal.fire({
                            title: 'Exported!',
                            text: 'CSV file has been downloaded.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: { columns: ':not(:last-child)' },
                    action: function(e, dt, node, config) {
                        $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, node, config);
                        Swal.fire({
                            title: 'Exported!',
                            text: 'Excel file has been downloaded.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: { columns: ':not(:last-child)' },
                    action: function(e, dt, node, config) {
                        $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, node, config);
                        Swal.fire({
                            title: 'Exported!',
                            text: 'PDF file has been generated.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'btn btn-sm btn-outline-secondary',
                    exportOptions: { columns: ':not(:last-child)' }
                }
            ],
            
            columnDefs: [
                { orderable: false, targets: -1 } // Disable sorting on Actions column
            ],

            // This fires after the table is fully set up
            initComplete: function() {
                // Strips the Bootstrap class that forces buttons to merge together
                $('.dt-buttons').removeClass('btn-group');
            }
        });

        // SweetAlert for Delete Action
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            let form = $(this).closest('form');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

<style>
/* * CRITICAL FIX: Completely hide the default DataTables Copy notification box. 
 * This ensures only SweetAlert shows up when copying!
 */
div.dt-button-info {
    display: none !important;
}

/* Clean up overrides so Bootstrap grid works correctly */
.dataTables_filter { float: none !important; text-align: right; }
.dataTables_filter label {
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-bottom: 0;
}

.dataTables_length { margin-bottom: 0; }
.dataTables_length select {
    width: auto;
    display: inline-block;
    padding: 0.375rem 2.25rem 0.375rem 0.75rem; 
}
.dataTables_length label {
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0;
}

/* Force buttons to be separated and visibly styled */
div.dt-buttons {
    margin-bottom: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem; /* Adds physical space between un-grouped buttons */
}
div.dt-buttons .btn {
    border-radius: 0.25rem !important; 
    background-color: transparent !important;
    color: #6c757d !important;
    border: 1px solid #6c757d !important;
    box-shadow: none !important;
    margin: 0 !important; /* Resets any lingering btn-group margins */
}
div.dt-buttons .btn:hover {
    background-color: #6c757d !important;
    color: #fff !important;
}
</style>
@endsection