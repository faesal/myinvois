@extends('layouts.app')

@section('content')

<!-- ✅ DataTables & Buttons CDN -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<!-- ✅ Custom Style -->
<style>
.dt-buttons {
  margin-bottom: 10px;
}

.dataTables_wrapper .dataTables_filter {
  float: right;
  text-align: right;
  margin-bottom: 10px;
}

.dataTables_wrapper .dataTables_paginate {
  float: right;
  text-align: right;
}

.table-responsive {
  overflow-x: auto;
}

/* Pagination styling */
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 6px 12px;
    margin-left: 2px;
    border: 1px solid #dee2e6;
    background-color: white;
    color: #0d6efd !important;
    border-radius: 0.25rem;
    font-weight: 500;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
    color: #0d6efd !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #0d6efd !important;
    color: white !important;
    border: 1px solid #0d6efd;
}
</style>

<!-- ✅ DataTable Init -->
<script>
$(document).ready(function () {
  $('#invoice-table').DataTable({
    dom: '<"d-flex justify-content-between mb-2"<"dt-buttons"B><"dataTables_filter"f>>rt<"d-flex justify-content-between mt-3"<"dataTables_info"i><"dataTables_paginate"p>>',
    buttons: [
      {
        extend: 'excelHtml5',
        text: 'Export Excel',
        className: 'btn btn-success btn-sm me-2',
        title: 'Invoice_List'
      },
      {
        extend: 'csvHtml5',
        text: 'Export CSV',
        className: 'btn btn-primary btn-sm me-2',
        title: 'Invoice_List'
      },
      {
        extend: 'print',
        text: 'Print',
        className: 'btn btn-secondary btn-sm'
      }
    ],
    pageLength: 30,
    ordering: true
  });
});
</script>

<!-- ✅ Page Content -->
<div class="container-fluid py-4">
  <div class="alert alert-success alert-dismissible">
    <div class="alert-heading fw-semibold">Note</div>
    List of e-Invoices generated to LHDN through MySyncTax.
  </div>

  <!-- ✅ Filter Form -->
  <form method="POST" action="">
    @csrf
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
          </div>
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">Select</option>
              <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
              <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
          </div>
          <div class="col-md-1">
            <button type="submit" class="btn btn-primary w-100">Search</button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <!-- ✅ Table Card -->
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table id="invoice-table" class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Invoice ID</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($invoices as $invoice)
              @php $customer = $invoice->id_customer ?: '6'; @endphp
              <tr>
                <td>{{ $invoice->invoice_no }}</td>
                <td>{{ $invoice->customer_name ?? '-' }}</td>
                <td>RM {{ number_format($invoice->price, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d-m-Y H:i:s') }}</td>
                <td>
                  @if ($invoice->submission_status == 'submitted')
                    <span class="badge bg-success bg-opacity-20 text-success">SUBMITTED</span>
                  @else
                    <span class="badge bg-danger bg-opacity-20 text-danger">FAILED</span>
                  @endif
                </td>
                <td>
                  <a target="_blank" href="{{url('/show_invoice')}}/{{$invoice->id_supplier}}/{{$customer}}/{{$invoice->id_invoice}}" class="text-primary">View</a>
                  @if ($invoice->uuid)
                    <a href="{{ url('/cncelDocument') }}/{{ $invoice->uuid }}"
                       class="cancel-link text-danger"
                       >Cancel</a>
                  @endif
                  @if ($invoice->invoice_status == 'failed')
                    <a href="#" class="text-warning">Resubmit</a>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

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
            cancelButtonColor: '#6b7280',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
});
</script>

@endsection
