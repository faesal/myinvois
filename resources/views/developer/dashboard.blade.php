@extends('layouts.developerLayout')

@section('title', 'Dashboard')

@section('content')

<div class="container-fluid">

    <!-- HEADER TITLE -->
    <h2 class="fw-bold mb-1">Welcome, {{ auth()->user()->name }}</h2>
    <p class="text-muted mb-4">Your Developer Dashboard Overview</p>

    <!-- ====================== KPI CARDS ====================== -->
    <div class="row g-3 mb-4">

        <div class="col-12 col-md-4">
            <div class="p-4 bg-white rounded-3 shadow-sm h-100">
                <h6 class="text-muted">Total Clients</h6>
                <h2 class="fw-bold mt-2 text-primary">{{ $totalClients }}</h2>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="p-4 bg-white rounded-3 shadow-sm h-100">
                <h6 class="text-muted">Active Integrations</h6>
                <h2 class="fw-bold mt-2 text-success">{{ $activeIntegrations }}</h2>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="p-4 bg-white rounded-3 shadow-sm h-100">
                <h6 class="text-muted">Total API Calls</h6>
                <h2 class="fw-bold mt-2 text-info">{{ $apiCallsToday }}</h2>
            </div>
        </div>

    </div>

    <!-- ====================== BAR CHART ====================== -->
    <div class="card shadow-sm p-4 mb-4">
    <h5 class="fw-semibold mb-3">Invoices Created (Last 30 Days)</h5>

    <div style="height: 300px;">
        <canvas id="invoiceBarChart"></canvas>
    </div>
</div>


    <!-- ====================== CLIENT SUMMARY TABLE ====================== -->
    <div class="card shadow-sm p-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
            <h5 class="fw-semibold mb-0">Client Summary</h5>

            <div class="d-flex gap-2">
                <a href="{{ route('developer.client.create') }}" class="btn btn-primary btn-sm">
                    + Add Client
                </a>

                <a href="javascript:void(0)" id="exportExcelBtn" class="btn btn-success btn-sm">
                    Export
                </a>


            </div>
        </div>

        <!-- Mobile-friendly table scroll -->
        <div class="table-responsive">
            <table id="clientTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Status</th>
                        <th>LHDN Keys</th>
                        <th>Expired In</th>
                        <th>Invoices</th>
                        <th>Last Sync</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($clients as $c)
                    <tr>
                        <td>
                            <strong>{{ $c->registration_name }}</strong><br>
                            <small>{{ $c->tin_no }} | {{ $c->unique_id }}</small>
                        </td>

                        <td>
                            @if($c->is_activation == 1)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-warning text-dark">Inactive</span>
                            @endif
                        </td>

                        <td>{{ $c->keyCount }}</td>
                        <td>
                            @if(str_contains($c->expires_in, 'Expired'))
                                <span class="badge bg-danger">{{ $c->expires_in }}</span>
                            @elseif($c->expires_in === 'Ends today')
                                <span class="badge bg-warning text-dark">{{ $c->expires_in }}</span>
                            @else
                                <span class="badge bg-success">{{ $c->expires_in }}</span>
                            @endif
                        </td>
                        <td>{{ $c->invoice_count }}</td>
                        <td>
                            {{ $c->last_sync ? \Carbon\Carbon::parse($c->last_sync)->format('d M Y H:i') : '—' }}
                        </td>

                        <td>
                            <a href="{{ route('developer.client.edit', $c->id_customer) }}" class="btn btn-sm btn-secondary">
                                Edit
                            </a>
                            <a href="#" class="btn btn-sm btn-warning mb-1">Pay</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

    </div>

</div>

<style>
    /* Hide the DataTables default Excel button */
    .export-excel-btn {
        display: none !important;
    }
</style>

@endsection

@section('scripts')
    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- DataTables (Bootstrap 5) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"/>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- DataTables Buttons (Excel export) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css"/>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    {{-- JSZip is required for Excel export --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(function () {

            var clientTable = $('#clientTable').DataTable({
                pageLength: 10,
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        title: 'mysynctax_clients_' + new Date().toISOString().slice(0,10),

                        // ⭐️ This is the button we call manually
                        className: 'export-excel-btn',

                        customizeData: function (data) {

                            // Overwrite Excel header
                            data.header = [
                                "Client Name",
                                "TIN No",
                                "Unique ID",
                                "Keys Count",
                                "Start Subscribe",
                                "End Subscribe",
                                "Expires In"
                            ];

                            // Export clean backend data
                            data.body = {!! json_encode($exportClients) !!}.map(c => [
                                c.registration_name,
                                c.tin_no,
                                c.unique_id,
                                c.keys_count,
                                c.start_subscribe || "",
                                c.end_subscribe || "",
                                c.expires_in
                            ]);
                        }
                    }
                ]
            });

            // ⭐️ Actual Export Button Trigger
            $('#exportExcelBtn').on('click', function (e) {
                e.preventDefault();
                console.log("Export button clicked!");

                clientTable.button('.export-excel-btn').trigger();
            });


            // ========== BAR CHART ==========
            const invoiceLabels  = {!! $invoiceLabels !!};
            const invoiceCounts  = {!! $invoiceCounts !!};

            const ctx = document.getElementById('invoiceBarChart').getContext('2d');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: invoiceLabels,
                    datasets: [{
                        label: 'Invoices per Day',
                        data: invoiceCounts,
                        borderWidth: 1,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            ticks: { maxRotation: 90, minRotation: 45, autoSkip: true }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });

        });
    </script>
@endsection

