@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Select Consolidated Items</h3>
    <div>
      <a href="#" class="btn btn-outline-secondary">Export</a>
      <a href="#" class="btn btn-primary">Submit Selected</a>
    </div>
  </div>

  <!-- Filter -->
  <form method="GET" action="{{ route('consolidate.select') }}">
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="{{ $start }}">
          </div>
          <div class="col-md-4">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ $end }}">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
          </div>
        </div>
      </div>
    </div>
  </form>

  <form method="POST" action="{{ route('consolidate.submit') }}">
    @csrf

    <!-- Table -->
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table id="datatable-items" class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th><input type="checkbox" class="form-check-input" id="selectAll"></th>
                <th>Sale ID</th>
                <th>Item Name</th>
                <th>Qty</th>
                <th>Amount (RM)</th>
                <th>Connection</th>
                <th>Issue Date</th>
              </tr>
            </thead>
            <tbody>
              @foreach($items as $item)
              <tr>
                <td><input type="checkbox" name="selected_items[]" value="{{ $item->id_invoice_item }}" class="form-check-input"></td>
                <td>{{ $item->sale_id_integrate }}</td>
                <td>{{ $item->item_description }}</td>
                <td>{{ $item->invoiced_quantity }}</td>
                <td>{{ number_format($item->line_extension_amount, 2) }}</td>
                <td>{{ $item->connection_integrate }}</td>
                <td>{{ \Carbon\Carbon::parse($item->issue_date)->format('d-m-Y') }}</td>
              </tr>
              @endforeach
              @if($items->isEmpty())
              <tr>
                <td colspan="7" class="text-center">No consolidated items found.</td>
              </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer text-end">
        <button type="submit" class="btn btn-success">Submit Selected</button>
      </div>
    </div>
  </form>
</div>
@endsection

