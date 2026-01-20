@extends('layouts.app')

@section('content')

<style>
/* Responsive tweaks */
@media (max-width: 768px) {
    .filter-col { margin-bottom: 15px; }
    .badge { width: 100%; padding: 8px !important; font-size: 0.75rem !important; }
}
/* Ensure checkboxes are easy to click */
input[type="checkbox"] { cursor: pointer; width: 18px; height: 18px; }
</style>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Select Consolidated Invoices</h3>
        <div>
            <a href="#" class="btn btn-outline-secondary">Export</a>
            <button type="button" class="btn btn-primary" id="submitSelectedBtnTop">Submit Selected</button>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('consolidate.listing') }}">
                <div class="row g-3 align-items-end">
                    
                    <div class="col-md-2 col-6 filter-col">
                        <label class="form-label fw-bold">Start Date</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                    </div>

                    <div class="col-md-2 col-6 filter-col">
                        <label class="form-label fw-bold">End Date</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                    </div>

                    <div class="col-md-2 col-6 filter-col">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-control">
                            <option value="ALL">All Statuses</option>
                            <option value="Submitted" {{ request('status')=='Submitted'?'selected':'' }}>Submitted</option>
                            <option value="Failed" {{ request('status')=='Failed'?'selected':'' }}>Failed</option>
                            <option value="Pending" {{ request('status')=='Pending'?'selected':'' }}>Pending</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-12 filter-col">
                        <label class="form-label fw-bold">Customer</label> <select name="connection_integrate" class="form-control">
                            <option value="ALL">All Customers</option>
                            @foreach ($customers as $c)
                                @php $val = $c->connection_integrate ?? $c->registration_name; @endphp
                                <option value="{{ $val }}" {{ request('connection_integrate')==$val?'selected':'' }}>
                                    {{ $c->registration_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-12">
                        <button class="btn btn-primary w-100">
                            <i class="ph-magnifying-glass me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoiceTable" class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="30" class="text-center">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Invoice No</th>
                            <th>Customer</th>
                            <th>Amount (RM)</th>
                            <th>Date</th>
                            <th class="text-center">LHDN Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="select-item form-check-input" value="{{ $inv->id_invoice }}">
                            </td>

                            <td class="fw-bold text-primary">{{ $inv->invoice_no }}</td>
                            
                            <td>{{ $inv->registration_name }}</td>
                            
                            <td class="text-end fw-bold">{{ number_format($inv->price ?? 0, 2) }}</td>
                            
                            <td data-sort="{{ \Carbon\Carbon::parse($inv->issue_date)->timestamp }}">
                                {{ \Carbon\Carbon::parse($inv->issue_date)->format('d-m-Y') }}
                            </td>

                            <td class="text-center">
                                @php
                                    $status = strtoupper(trim($inv->submission_status ?: 'PENDING'));
                                    $map = ['SUBMITTED'=>'success','FAILED'=>'danger','PENDING'=>'warning'];
                                    $badge = $map[$status] ?? 'secondary';
                                @endphp
                                <span class="badge rounded-pill bg-{{ $badge }} px-3">{{ $status }}</span>
                            </td>

                            <td class="text-center">
                                @if(!empty($inv->id_supplier) && !empty($inv->id_invoice))
                                    <a href="{{ route('consolidate.show', ['id_supplier' => $inv->id_supplier, 'id_invoice' => $inv->id_invoice]) }}" 
                                       class="btn btn-sm btn-info text-white" 
                                       target="_blank">
                                       View
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled>Error</button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                @if($invoices->isEmpty())
                    <div class="text-center text-muted py-3">No consolidated invoices found.</div>
                @endif
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="button" class="btn btn-success" id="submitSelectedBtnBottom">Submit Selected</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {

    // ----------------------------------------------------------------
    // 1. INITIALIZE DATATABLE (Pagination & Sorting)
    // ----------------------------------------------------------------
    if ($('#invoiceTable').length > 0) {
        var table = $('#invoiceTable').DataTable({
            "destroy": true, 
            "pageLength": 10,
            "lengthMenu": [10, 25, 50, 100],
            "ordering": true,
            "order": [[ 4, "desc" ]], // Default Sort by Date (Column Index 4)
            "responsive": true,
            "autoWidth": false,
            "language": {
                "emptyTable": "No data available in table",
                "search": "Search:",
                "paginate": { "previous": "Prev", "next": "Next" }
            }
        });
    }

    // ----------------------------------------------------------------
    // 2. SELECT ALL LOGIC (Fixed)
    // ----------------------------------------------------------------
    // Using delegated event binding for ID '#selectAll' (matches HTML)
    $(document).on('click', '#selectAll', function() {
        var isChecked = this.checked;
        
        // Select all checkboxes with class 'select-item'
        $('.select-item').prop('checked', isChecked);
    });

    // Uncheck master if a single item is unchecked
    $(document).on('click', '.select-item', function() {
        if(!this.checked){
            $('#selectAll').prop('checked', false);
        }
    });

    // ----------------------------------------------------------------
    // 3. SUBMIT SELECTED FUNCTION
    // ----------------------------------------------------------------
    // Shared function for both Top and Bottom buttons
    function submitSelected() {
        let selected = [];
        let totalPrice = 0;

        $('.select-item:checked').each(function() {
            let row = $(this).closest('tr');
            let id = $(this).val();
            
            // Amount is in Column 3 (0:Checkbox, 1:InvNo, 2:Customer, 3:Amount)
            let amountText = row.find('td:eq(3)').text().trim().replace(/,/g, ''); 
            let amount = parseFloat(amountText) || 0;
            
            selected.push(id);
            totalPrice += amount;
        });

        if (selected.length === 0) {
            return Swal.fire({
                icon: "warning",
                title: "No Selection",
                text: "Please select at least one invoice."
            });
        }

        let connection = $("select[name='connection_integrate']").val();

        Swal.fire({
            title: "Confirm Submission",
            html: `
                <div class="text-start">
                    <p>Selected Invoices: <b>${selected.length}</b></p>
                    <p>Total Amount: <b>RM ${totalPrice.toFixed(2)}</b></p>
                </div>
            `,
            icon: "info",
            showCancelButton: true,
            confirmButtonText: "Yes, Submit",
            confirmButtonColor: "#198754",
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: "{{ route('consolidate.submitSelected') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        invoices: selected,
                        connection_integrate: connection
                    }
                }).catch(error => {
                    Swal.showValidationMessage(
                        `Error: ${error.responseJSON?.message || error.statusText}`
                    );
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                let response = result.value;
                Swal.fire({
                    title: response.success ? "Success" : "Warning",
                    text: response.message,
                    icon: response.success ? "success" : "warning",
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload(); 
                });
            }
        });
    }

    // Bind function to both buttons
    $('#submitSelectedBtnTop, #submitSelectedBtnBottom').on('click', function(e) {
        e.preventDefault();
        submitSelected();
    });

});
</script>
@endsection