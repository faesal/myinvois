@extends('layouts.developerLayout')



@section('content')



<style>

    .export-excel-btn {

        display: none !important;

    }

</style>



<!-- SweetAlert2 -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



<div class="container-fluid">



    {{-- ===================== SUCCESS / ERROR ALERTS ===================== --}}

    @if(session('success'))

        <script>

            document.addEventListener("DOMContentLoaded", function () {

                Swal.fire({

                    icon: 'success',

                    title: 'Success',

                    text: '{{ session('success') }}',

                    confirmButtonColor: '#3b82f6',

                });

            });

        </script>

    @endif



    @if(session('error'))

        <script>

            document.addEventListener("DOMContentLoaded", function () {

                Swal.fire({

                    icon: 'error',

                    title: 'Error',

                    text: '{{ session('error') }}',

                    confirmButtonColor: '#ef4444',

                });

            });

        </script>

    @endif



    <!-- PAGE HEADER -->

    <div class="mb-4">

        <h3 class="fw-bold mb-1">Customer Companies</h3>

        <p class="text-muted">Manage developer customer companies and LHDN client keys</p>

    </div>



    <!-- CLIENT SUMMARY TABLE -->

    <div class="card shadow-sm p-4">



        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">

            <h5 class="fw-semibold mb-0">Client Summary</h5>



            <div class="d-flex gap-2">

                <a href="{{ route('developer.companies.add') }}" class="btn btn-primary btn-sm">

                    + Add Client

                </a>



                <a href="javascript:void(0)" id="exportExcelBtn" class="btn btn-success btn-sm">

                    Export

                </a>

            </div>

        </div>



        <div class="table-responsive">

            <table id="companyTable" class="table table-bordered table-striped w-100">

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

                    @foreach ($companies as $c)



                        @php

                            $end = \Carbon\Carbon::parse($c->end_subscribe);

                            $now = \Carbon\Carbon::now();

                            $daysLeft = $now->diffInDays($end, false);

                            

                            if ($daysLeft < 0) {

                                $expiresIn = 'Expired ' . abs($daysLeft) . ' days ago';

                            } elseif ($daysLeft == 0) {

                                $expiresIn = 'Ends today';

                            } else {

                                $expiresIn = $daysLeft . ' days left';

                            }



                            $statusText = $c->is_activation ? 'Active' : 'Inactive';

                            $invoiceCount = DB::table('invoice')->where('id_customer', $c->id_customer)->count();

                            $keysCount = collect([$c->secret_key1,$c->secret_key2,$c->secret_key3])->filter()->count();

                        @endphp



                        <tr>

                            <td>

                                <strong>{{ $c->registration_name }}</strong><br>

                                <small>{{ $c->tin_no }} | CLT-{{ $c->id_customer }}</small>

                            </td>



                            <td>

                                @if($c->is_activation == 1)

                                    <span class="badge bg-success">Active</span>

                                @else

                                    <span class="badge bg-warning text-dark">Inactive</span>

                                @endif

                            </td>



                            <td>{{ $keysCount }}</td>



                            <td>

                                @if(str_contains($expiresIn, 'Expired'))

                                    <span class="badge bg-danger">{{ $expiresIn }}</span>

                                @elseif($expiresIn === 'Ends today')

                                    <span class="badge bg-warning text-dark">{{ $expiresIn }}</span>

                                @else

                                    <span class="badge bg-success">{{ $expiresIn }}</span>

                                @endif

                            </td>



                            <td>{{ $invoiceCount }}</td>

                            <td>
                                {{ $c->last_sync ? \Carbon\Carbon::parse($c->last_sync)->format('d M Y H:i') : '—' }}
                            </td>

                                <td>
                                    <a href="{{ route('developer.companies.show', $c->id_customer) }}" class="btn btn-sm btn-secondary">
                                        View
                                    </a>

                                    <a href="#" class="btn btn-sm btn-warning">Pay</a>

                                    <!-- ⭐ Login Button -->
                                    <button class="btn btn-sm btn-info loginBtn"
                                            data-url="v5/subscriberLogin/{{ $c->user_uuid }}">
                                        Login
                                    </button>
                                </td>

                        </tr>



                    @endforeach

                </tbody>



            </table>

        </div>

    </div>



</div>



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



    <script>

        $(function () {



            var companyTable = $('#companyTable').DataTable({

                pageLength: 10,

                responsive: true,

                dom: 'Bfrtip',

                buttons: [

                    {

                        extend: 'excelHtml5',

                        title: 'mysynctax_companies_' + new Date().toISOString().slice(0,10),



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

                            data.body = {!! json_encode($exportCompanies) !!}.map(c => [

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



                companyTable.button('.export-excel-btn').trigger();

            });



        });

        $(document).on('click', '.loginBtn', function (e) {
            e.preventDefault();

            let url = $(this).data('url');

            Swal.fire({
                icon: 'info',
                title: 'Redirecting',
                text: 'You are being redirected to the subscriber login page.',
                confirmButtonText: 'Proceed',
                confirmButtonColor: '#3b82f6',
                showCancelButton: true,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.open(url, '_blank'); // Open in new tab
                }
            });
        });


    </script>

@endsection