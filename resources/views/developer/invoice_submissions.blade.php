@extends('layouts.developerLayout')

@section('content')

<style>
    @media (max-width: 768px) {
        .filter-col {
            margin-bottom: 15px;
        }
        .badge {
            width: 100%;
            display: inline-block;
            padding: 8px !important;
            font-size: 0.75rem !important;
        }
        .btn-info {
            width: 100%;
        }
    }
</style>

<div class="container-fluid">

    <h3 class="mb-4">Invoice Submissions</h3>

    <!-- =======================
         FILTER BAR
    ======================= -->
    <div class="card mb-4">
        <div class="card-body">

            <form method="POST" action="{{ route('developer.invoices.index') }}">
                <div class="row g-3">
                @csrf
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
                            <option value="Submitted" {{ request('status') == 'Submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="Failed" {{ request('status') == 'Failed' ? 'selected' : '' }}>Failed</option>
                            <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-12 filter-col">
                        <label>Company</label>
                        <select name="connection_integrate" class="form-control">
                            <option value="">Please choose</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->connection_integrate }}"
                                    {{ request('connection_integrate') == $c->connection_integrate ? 'selected' : '' }}>
                                    {{ $c->registration_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            Search
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <!-- =======================
         SUBMIT SELECTED BUTTON
    ======================= -->
    <div class="mb-3">
        <button class="btn btn-success" id="submitSelectedBtn" type="button">
            Submit Selected
        </button>
    </div>

    <!-- =======================
         INVOICE TABLE
    ======================= -->
    <div class="card">
        <div class="card-body">

            <div class="table-responsive">
                <table id="invoiceTable" class="table table-bordered table-striped" width="100%">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="select-all">
                            </th>
                            <th>Invoice ID</th>
                            <th>Sale ID</th>
                            <th>Customer</th>
                            <th>Amount (RM)</th>
                            <th>Date</th>
                            <th>LHDN Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                       

                            @if(request()->filled('connection_integrate') && $invoices->isNotEmpty())
                                @foreach ($invoices as $inv)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" class="select-item" value="{{ $inv->id_invoice }}">
                                        </td>

                                        <td>{{ $inv->invoice_no }}</td>
                                        <td>{{ $inv->sale_id }}</td>
                                        <td>{{ $inv->registration_name }}</td>
                                        <td>{{ number_format($inv->price ?? 0, 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($inv->issue_date)->format('d-m-Y') }}</td>

                                        <td class="text-center">
                                            @php
                                                $rawStatus = strtoupper(trim($inv->submission_status));
                                                $status = $rawStatus !== '' ? $rawStatus : 'PENDING';

                                                $colors = [
                                                    'SUBMITTED' => 'primary',
                                                    'FAILED'    => 'danger',
                                                    'PENDING'   => 'warning',
                                                ];

                                                $color = $colors[$status] ?? 'warning';
                                            @endphp

                                            <span class="badge rounded-pill bg-{{ $color }}" style="padding:6px 12px;">
                                                {{ $status }}
                                            </span>
                                        </td>

                                        <td class="text-center">
                                    
                                    
                                      @php
                                      if(@$inv->id_supplier)
                                      $invoice=$inv->id_supplier.'/'.$inv->id_invoice;
                                      else
                                      $invoice='';

                                      @endphp
                                      <a target="_blank" href="{{url('/invoice/'. $invoice)}}"
                                        class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>

                </table>
                @if(!request()->filled('connection_integrate'))
                    <div class="text-center text-muted py-2">
                        <i>Please choose a customer and click Search to display invoices.</i>
                    </div>
                @endif

                @if(request()->filled('connection_integrate') && $invoices->isEmpty())
                    <div class="text-center text-muted py-2">
                        <i>No invoices found for the selected customer.</i>
                    </div>
                @endif

            </div>

        </div>
    </div>

</div>

@endsection


@section('scripts')

<!-- DATATABLES -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- SWEETALERT -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {

    // DATATABLE INIT — load only after customer is selected
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
        // Destroy DataTable if it exists and user has not filtered yet
        if ($.fn.DataTable.isDataTable('#invoiceTable')) {
            $('#invoiceTable').DataTable().clear().destroy();
        }
    @endif


    // SELECT ALL
    $("#select-all").on("click", function() {
        $(".select-item").prop('checked', this.checked);
    });

    // ================================
    //   SUBMIT SELECTED (AJAX)
    // ================================
    $("#submitSelectedBtn").on("click", function() {

        let selected = [];
        let totalPrice = 0;
        let supplierCheck = null;
        let supplierMismatch = false;

        $(".select-item:checked").each(function() {

            let row = $(this).closest("tr");

            let id = $(this).val();
            let amount = parseFloat(row.find("td:nth-child(5)").text().replace(/,/g, ''));
            let supplierId = row.find(".supplier-id").val();

            if (supplierCheck === null) {
                supplierCheck = supplierId;
            } else if (supplierCheck !== supplierId) {
                supplierMismatch = true;
            }

            selected.push(id);
            totalPrice += amount;
        });

        if (selected.length === 0) {
            return Swal.fire({
                icon: "warning",
                title: "No invoices selected",
                text: "Please select at least one invoice."
            });
        }

        if (supplierMismatch) {
            return Swal.fire({
                icon: "error",
                title: "Supplier mismatch",
                text: "Only invoices from the same supplier can be submitted together."
            });
        }

        let connection = $("select[name='connection_integrate']").val();

        // Summary popup
        Swal.fire({
            icon: "info",
            title: "Confirm Submission",
            html: `
                <div style="text-align:left;">
                    <p><strong>Total Invoices:</strong> ${selected.length}</p>
                    <p><strong>Total Amount:</strong> RM ${totalPrice.toFixed(2)}</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "Submit Now",
            confirmButtonColor: "#22c55e",
        }).then((res) => {

            if (!res.isConfirmed) return;

            // AJAX submission
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
                    Swal.fire({
                        title: "Processing...",
                        text: "Please wait.",
                        didOpen: () => Swal.showLoading(),
                        allowOutsideClick: false
                    });
                },
                success: function(response) {
                    Swal.fire({
                        icon: "success",
                        title: "Success!",
                        text: response.message ?? "Invoices submitted.",
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // 🔥 Automatically reload invoice list
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: "error",
                        title: "Submission Failed",
                        text: xhr.responseJSON?.message || "Unexpected error."
                    });
                }
            });

        });

    });

});
</script>
@endsection
