@extends('layouts.developerLayout')

@section('content')

<style>
@media (max-width: 768px) {
    .filter-col {
        margin-bottom: 15px;
    }
    .badge {
        width: 100%;
        padding: 8px !important;
        font-size: 0.75rem !important;
    }
    .btn-info, .btn-warning, .btn-danger {
        width: 100%;
    }
}
</style>

<div class="container-fluid">

<h3 class="mb-4">Invoice Submissions</h3>

<div class="card mb-4">
    <div class="card-body">

        <form method="POST" action="{{ route('developer.invoices.index') }}">
            @csrf
            <div class="row g-3">

                <div class="col-md-2 col-6 filter-col">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                </div>

                <div class="col-md-2 col-6 filter-col">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                </div>

                <div class="col-md-2 col-6 filter-col">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="ALL">All</option>
                        <option value="Submitted" {{ request('status')=='Submitted'?'selected':'' }}>Submitted</option>
                        <option value="Failed" {{ request('status')=='Failed'?'selected':'' }}>Failed</option>
                        <option value="Pending" {{ request('status')=='Pending'?'selected':'' }}>Pending</option>
                    </select>
                </div>

                <div class="col-md-2 col-6 filter-col">
                    <label>Invoice Type</label>
                    <select name="invoice_type" class="form-control">
                        <option value="ALL">All</option>
                        @foreach ($invoiceTypes as $type)
                            <option value="{{ $type->code }}"
                                {{ request('invoice_type') == $type->code ? 'selected' : '' }}>
                                {{ $type->description }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-12 filter-col">
                    <label>LHDN Account</label>
                    <select name="connection_integrate" class="form-control">
                        <option value="">Please choose</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->connection_integrate }}"
                                {{ request('connection_integrate')==$c->connection_integrate?'selected':'' }}>
                                {{ $c->registration_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-12 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Search</button>
                </div>

            </div>
        </form>

    </div>
</div>

<div class="mb-3">
    <button class="btn btn-success" id="submitSelectedBtn">Submit Selected</button>
</div>

<div class="card">
<div class="card-body">
<div class="table-responsive">

<table id="invoiceTable" class="table table-bordered table-striped">
<thead>
<tr>
    <th><input type="checkbox" id="select-all"></th>
    <th>Invoice ID</th>
    <th>Sale ID</th>
    <th>Invoice Type</th>
    <th>Customer</th>
    <th>Amount (RM)</th>
    <th>Date</th>
    <th>LHDN Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
@if(request()->filled('connection_integrate') && $invoices->isNotEmpty())
    @foreach($invoices as $inv)
    <tr>
        <td class="text-center">
            <input type="checkbox" class="select-item" value="{{ $inv->id_invoice }}">
            <input type="hidden" class="supplier-id" value="{{ $inv->id_supplier }}">
        </td>

        <td>{{ $inv->invoice_no }}</td>
        <td>{{ $inv->sale_id }}</td>

        <td>{{ $inv->invoice_type_name ?? '-' }}</td>

        <td>{{ $inv->registration_name }}</td>
        @php
         $total = $inv->taxable_amount + $inv->tax_amount;
         $status = strtoupper(trim($inv->submission_status ?: 'PENDING'));
         $map = ['SUBMITTED'=>'primary','FAILED'=>'danger','PENDING'=>'warning'];
        @endphp
        <td>{{ number_format($total ?? 0,2) }}</td>
        <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('d-m-Y H:i:s') }}</td>

        <td class="text-center">
            <span class="badge rounded-pill bg-{{ $map[$status] ?? 'secondary' }}">
                {{ $status }}
            </span>
        </td>

        <td class="text-center">
            <div class="btn-group" role="group">
                {{-- View Button --}}
                <a href="{{ route('invoice.view.public', $inv->unique_id) }}" target="_blank" class="btn btn-sm btn-info text-white">
                    View
                </a>
                
                {{-- Cancel Button: Only for SUBMITTED invoices --}}
                @if($status === 'SUBMITTED')
                    <button type="button" 
                            class="btn btn-sm btn-warning text-white" 
                            onclick="cancelDocument('{{ $inv->unique_id }}')">
                        Cancel
                    </button>
                @endif
                
                {{-- Delete Button: Only if NOT Submitted --}}
                @if($status !== 'SUBMITTED')
                    <button type="button" 
                            class="btn btn-sm btn-danger" 
                            onclick="confirmDelete('{{ $inv->id_invoice }}')">
                        Delete
                    </button>
                @endif
            </div>
        </td>
    </tr>
    @endforeach
@endif
</tbody>
</table>

@if(!request()->filled('connection_integrate'))
<div class="text-center text-muted py-2">
    <i>Please choose a customer and click Search.</i>
</div>
@endif

@if(request()->filled('connection_integrate') && $invoices->isEmpty())
<div class="text-center text-muted py-2">
    <i>No invoices found.</i>
</div>
@endif

</div>
</div>
</div>

</div>
@endsection


@section('scripts')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ============================================
//  GLOBAL FUNCTIONS (MUST BE OUTSIDE .ready)
// ============================================

// 1. Delete Confirmation
window.confirmDelete = function(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will remove the invoice record",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "{{ route('developer.invoices.delete', '') }}/" + id;
        }
    });
};

// 2. Cancel Document Logic (using IntegrationInvoiceController2 route)
window.cancelDocument = function(uniqueId) {
    Swal.fire({
        title: 'Cancel Document?',
        text: "This request will be sent to LHDN. Valid only within 72 hours of submission.",
        icon: 'warning',
        input: 'select',
        inputOptions: {
            'wrong_data': 'Incorrect Data / Error in Content',
            'duplicate': 'Duplicate Document',
            'order_cancelled': 'Transaction Cancelled by Customer',
            'other': 'Other'
        },
        inputPlaceholder: 'Select a reason',
        showCancelButton: true,
        confirmButtonColor: '#f1c40f',
        confirmButtonText: 'Confirm Cancellation',
        inputValidator: (value) => {
            if (!value) return 'A reason is required by LHDN!'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                // 🔥 THE FIX: Added '/api' prefix because the route is in api.php
                url: "{{ url('/api/myinvois/cancelDocument') }}/" + uniqueId,
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}", // Required because of middleware('web')
                    reason: result.value
                },
                beforeSend: function() {
                    Swal.fire({
                        title: 'Connecting to MyInvois...',
                        text: 'Verifying cancellation with LHDN servers.',
                        didOpen: () => Swal.showLoading(),
                        allowOutsideClick: false
                    });
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message || 'Invoice cancelled successfully.',
                    }).then(() => location.reload());
                },
                error: function(xhr) {
                    // This handles the 72-hour rejection or session timeout
                    let errorMsg = xhr.responseJSON?.message || "LHDN rejected the cancellation. (Note: limit is 72 hours).";
                    Swal.fire('Cancellation Failed', errorMsg, 'error');
                }
            });
        }
    });
};

$(document).ready(function() {

    @if(request()->filled('connection_integrate') && $invoices->isNotEmpty())
    $('#invoiceTable').DataTable({
            pageLength: 10,
            ordering: true,
            searching: true,
            lengthChange: true,
            responsive: true,
            autoWidth: false,
        });
    @else
        if ($.fn.DataTable.isDataTable('#invoiceTable')) {
            $('#invoiceTable').DataTable().clear().destroy();
        }
    @endif

    $("#select-all").on("click", function() {
        $(".select-item").prop('checked', this.checked);
    });

    $("#submitSelectedBtn").on("click", function() {
        let selected = [];
        let totalPrice = 0;
        let supplierCheck = null;
        let supplierMismatch = false;

        $(".select-item:checked").each(function() {
            let row = $(this).closest("tr");
            let id = $(this).val();
            let amountText = row.find("td:nth-child(6)").text().trim(); 
            let amount = parseFloat(amountText.replace(/,/g, '')) || 0;
            let supplierId = row.find(".supplier-id").val();

            if (supplierCheck === null) {
                supplierCheck = supplierId;
            } else if (supplierId && supplierCheck !== supplierId) {
                supplierMismatch = true;
            }

            selected.push(id);
            totalPrice += amount;
        });

        if (selected.length === 0) {
            return Swal.fire({ icon: "warning", title: "No invoices selected" });
        }

        if (supplierMismatch) {
            return Swal.fire({ icon: "error", title: "Supplier mismatch", text: "Invoices must be from the same supplier." });
        }

        let connection = $("select[name='connection_integrate']").val();

        Swal.fire({
            icon: "info",
            title: "Confirm Submission",
            html: `<b>Total Invoices:</b> ${selected.length}<br><b>Total Amount:</b> RM ${totalPrice.toFixed(2)}`,
            showCancelButton: true,
            confirmButtonText: "Submit Now",
            confirmButtonColor: "#22c55e",
        }).then((res) => {
            if (!res.isConfirmed) return;

            $.ajax({
                url: "{{ route('developer.invoices.submitSelected') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    invoices: selected,
                    connection_integrate: connection,
                    id_supplier: supplierCheck
                },
                beforeSend: function() {
                    Swal.fire({ title: "Processing...", didOpen: () => Swal.showLoading(), allowOutsideClick: false });
                },
                success: function(response) {
                    Swal.fire({ icon: "success", title: "Success!", timer: 1800, showConfirmButton: false }).then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire({ icon: "error", title: "Submission Failed", text: xhr.responseJSON?.message });
                }
            });
        });
    });

    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Success', text: "{{ session('success') }}", timer: 3000, showConfirmButton: false });
    @endif

    @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Error', text: "{{ session('error') }}" });
    @endif

});
</script>
@endsection