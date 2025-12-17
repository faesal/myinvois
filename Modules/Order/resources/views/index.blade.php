@extends('admin.master_layout')

@section('title')
  <title>{{ __('Order Transaction Report') }}</title>
@endsection

@section('body-header')
  <h3 class="crancy-header__title m-0">{{ __('Order Transaction Report') }}</h3>
  <p class="crancy-header__text">{{ __('Reports') }} >> {{ __('Orders') }}</p>
@endsection

@push('css_section')
  {{-- DataTables + Buttons (Bootstrap 5) --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
  {{-- Bootstrap Icons (for Excel icon) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    /* make Excel button small, inline */
    #btnExportExcel{
      display:inline-flex !important;
      align-items:center;
      gap:.5rem;
      width:auto !important;
      white-space:nowrap;
      padding:.375rem .75rem;
      font-size:.875rem;
      line-height:1.5;
      border-radius:.375rem;
    }
    .dt-buttons{display:none !important;} /* hide DT's native buttons */
  </style>
@endpush

@section('body-content')
<section class="crancy-adashboard crancy-show">
  <div class="container container__bscreen">
    <div class="row">
      <div class="col-12">
        <div class="crancy-body">
          <div class="crancy-dsinner">

            {{-- Filters --}}
            <br>
            <div class="card shadow-sm border-0">
              <div class="card-body">
                <form method="POST" action="" class="row g-3 align-items-end">
                  @csrf

                  <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('Date Range') }}</label>
                    <div class="d-flex gap-2">
                      <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                  </div>

                  <div class="col-12 col-md-3">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                  </div>

                  <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('Payment Status') }}</label>
                    <select name="payment_status" class="form-select">
                      <option value="">{{ __('All') }}</option>
                      <option value="success" @selected(request('payment_status')==='success')>{{ __('Paid') }}</option>
                      <option value="unpaid"  @selected(request('payment_status')==='unpaid')>{{ __('Unpaid') }}</option>
                      <option value="pending" @selected(request('payment_status')==='pending')>{{ __('Pending') }}</option>
                      <option value="failed"  @selected(request('payment_status')==='failed')>{{ __('Failed') }}</option>
                    </select>
                  </div>

                  <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('Print Status') }}</label>
                    <select name="printed" class="form-select">
                      <option value="">{{ __('All') }}</option>
                      <option value="1" @selected(request('printed')==='1')>{{ __('Printed') }}</option>
                      <option value="0" @selected(request('printed')==='0')>{{ __('Pending') }}</option>
                    </select>
                  </div>

                  <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('Search') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="{{ __('Search by Order ID / Billcode / Transaction') }}">
                  </div>

                  <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('Location') }}</label>
                    <select name="restaurant_id" class="form-select">
                      <option value="">{{ __('Select') }}</option>
                      @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(request('restaurant_id')==$loc->id)>{{ $loc->restaurant_name }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-12 col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                  </div>
                </form>
              </div>
            </div>

            {{-- KPIs --}}
            <div class="row mt-3">
              <div class="col-md-4">
                <div class="crancy-ecom-card crancy-ecom-card__v2">
                  <div class="crancy-ecom-card__heading">
                    <h4 class="crancy-ecom-card__title">{{ __('Total Transactions') }}</h4>
                  </div>
                  <div class="crancy-ecom-card__content">
                    <div class="crancy-ecom-card__camount__inside">
                      <h3 class="crancy-ecom-card__amount">{{ number_format($totalTransactions) }}</h3>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="crancy-ecom-card crancy-ecom-card__v2">
                  <div class="crancy-ecom-card__heading">
                    <h4 class="crancy-ecom-card__title">{{ __('Total Paid') }}</h4>
                  </div>
                  <div class="crancy-ecom-card__content">
                    <div class="crancy-ecom-card__camount__inside">
                      <h3 class="crancy-ecom-card__amount text-success">{{ currency($totalPaid) }}</h3>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="crancy-ecom-card crancy-ecom-card__v2">
                  <div class="crancy-ecom-card__heading">
                    <h4 class="crancy-ecom-card__title">{{ __('Total Unpaid') }}</h4>
                  </div>
                  <div class="crancy-ecom-card__content">
                    <div class="crancy-ecom-card__camount__inside">
                      <h3 class="crancy-ecom-card__amount text-danger">{{ currency($totalUnpaid) }}</h3>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- Table --}}
            <div class="card mt-3 shadow-sm border-0">
              <div class="card-body p-0">

                {{-- Excel export button --}}
                <div class="d-flex justify-content-end align-items-center px-3 pt-3">
                  <button type="button" id="btnExportExcel" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i> {{ __('Download Excel') }}
                  </button>
                </div>

                <div class="table-responsive">
                  <table id="ordersTable" class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>{{ __('ORDER ID') }}</th>
                        <th>{{ __('DATE') }}</th>
                        <th>{{ __('CUSTOMER') }}</th>
                        <th>{{ __('AMOUNT') }}</th>
                        <th>{{ __('LOCATION') }}</th>
                        <th>{{ __('PRINT STATUS') }}</th>
                        <th>{{ __('STATUS') }}</th>
                        <th class="text-end">{{ __('ACTIONS') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @php $tz = config('app.timezone','Asia/Kuala_Lumpur'); @endphp
                      @forelse ($orders as $o)
                        <tr>
                          <td>#ORD-{{ str_pad($o->id,3,'0',STR_PAD_LEFT) }}</td>
                          <td>{{ optional($o->created_at)->timezone($tz)->format('Y-m-d') }}</td>
                          <td>{{ $o->user->name ?? '-' }}</td>
                          <td>{{ currency($o->grand_total) }}</td>
                          <td class="fw-semibold">{{ $o->restaurant->restaurant_name ?? '—' }}</td>
                          <td>
                            @if(($o->printed ?? 0) > 0)
                              <span class="badge bg-success">{{ __('Printed') }}</span>
                            @else
                              <span class="badge bg-warning text-dark">{{ __('Pending') }}</span>
                            @endif
                          </td>
                          <td>
                            @switch($o->payment_status)
                              @case('success') <span class="badge bg-success">{{ __('Paid') }}</span> @break
                              @case('pending') <span class="badge bg-warning text-dark">{{ __('Pending') }}</span> @break
                              @case('failed')  <span class="badge bg-danger">{{ __('Failed') }}</span> @break
                              @default         <span class="badge bg-secondary">{{ __('Unpaid') }}</span>
                            @endswitch
                          </td>
                          <td class="text-end">
                            <a target='_blank' href="{{url('admin/order-details/'.$o->id)}}" class="link-primary">{{ __('View Details') }}</a>
                          </td>
                        </tr>
                      @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">— {{ __('No results') }} —</td></tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

              </div>
            </div>

          </div> {{-- .crancy-dsinner --}}
        </div>
      </div>
    </div>
  </div>
</section>
@endsection


<style>
.btn-success {
    color: #fff;
    background-color: black;
    border-color: #198754;
    width: 200px;
}
#ordersTable td:nth-child(2),
#ordersTable th:nth-child(2) {
  white-space: nowrap;
}


</style>
<style>
  #btnExportExcel {
    display:inline-flex !important;
    align-items:center;
    gap:.5rem;
    width:auto !important;
    white-space:nowrap;
  }
  /* remove DT’s default buttons row */
  .dt-buttons { display:none !important; }
  /* don’t wrap date column */
  #ordersTable td:nth-child(2),
  #ordersTable th:nth-child(2) {
    white-space: nowrap;
  }
</style>
@push('js_section')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

  <script>
   (function () {
  "use strict";
  const $tbl = $('#ordersTable');
  if ($.fn.DataTable.isDataTable($tbl)) {
    $tbl.DataTable().destroy();
  }

  const dt = $tbl.DataTable({
    responsive: true,
    stateSave: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[0, 'desc']],
    columnDefs: [
      { targets: [7], orderable: false, searchable: false }
    ],
    buttons: [
      {
        extend: 'excelHtml5',
        text: '<i class="bi bi-file-earmark-excel-fill me-1"></i> {{ __("Download Excel") }}',
        className: 'btn btn-success btn-sm',
        exportOptions: { columns: [0,1,2,3,4,5,6] }
      }
    ],
    // ✅ enable search bar at the top right
    dom: "<'row px-3 pt-3 mb-2'<'col-md-6'B><'col-md-6 text-end'f>>" +
     "t" +
     "<'row align-items-center mt-3'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>"
  });

  // Hook Excel button
  $('#btnExportExcel').on('click', function () {
    dt.button('.buttons-excel').trigger();
  });
})();

  </script>
@endpush
