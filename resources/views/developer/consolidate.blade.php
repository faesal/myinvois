@extends('layouts.developerLayout')



@section('content')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>



<style>

.dt-buttons { margin-bottom: 10px; }

.dt-button { border: none; padding: 6px 12px; border-radius: 4px; margin-right: 5px; }

.dataTables_wrapper .dataTables_paginate .paginate_button {

    padding: 6px 12px; margin-left: 2px; border: 1px solid #dee2e6; background-color: white;

    color: #0d6efd !important; border-radius: 0.25rem; font-weight: 500;

}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {

    background-color: #0d6efd !important; color: white !important;

}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {

    background-color: #e2e6ea; color: #0d6efd !important;

}

.dataTables_filter { float: right !important; margin-bottom: 10px; }

</style>



<div class="container-fluid py-4">

    <h2>✅ Select Consolidated Items to Convert into Invoice</h2>



    <form method="POST" action="{{ url('developer/consolidate') }}" class="mb-3">

    @csrf

        <div class="row">

            <div class="col-md-3">

                <label>Start Date:</label>

                <input type="date" name="start_date" value="{{ $start }}" class="form-control">

            </div>

            <div class="col-md-3">

                <label>End Date:</label>

                <input type="date" name="end_date" value="{{ $end }}" class="form-control">

            </div>

            <div class="col-md-3">

                <label>LHDN Account:</label>

                <select name="connection" class="form-select" id="selected_connection" required>

                    <option value="">-- Select Connection --</option>

                    @foreach($availableConnections as $conn)

                        <option value="{{ $conn->connection_integrate }}" 

                            {{ $selectedConnection == $conn->connection_integrate ? 'selected' : '' }}>

                            {{ strtoupper($conn->registration_name) }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="col-md-3 align-self-end">

                <button class="btn btn-secondary">🔍 Filter</button>

            </div>

        </div>

    </form>



   

    <div class="card">

        <div class="card-body">

            <div class="table-responsive">

                <form id="consolidateForm">

                    @csrf

                    <input type="hidden" name="connection" value="{{ $selectedConnection }}">

                    <table id="datatable-items" class="table table-bordered align-middle">

                        <thead class="table-light">

                            <tr>

                                <th><input type="checkbox" id="checkAll"> ✔</th>

                                <th>Sale ID</th>

                                <th>Item Name</th>

                                <th>Quantity</th>

                                <th>Total (RM)</th>

                                <th>Connection</th>

                                <th>Date</th>

                            </tr>

                        </thead>

                        @if($selectedConnection)

                        <tbody>

                            @if($items->isNotEmpty())

                                @foreach($items as $item)

                                    <tr>

                                        <td><input type="checkbox" class="item-checkbox" name="selected_items[]" value="{{ $item->id_invoice_item }}"></td>

                                        <td>{{ $item->sale_id_integrate }}</td>

                                        <td>{{ $item->item_description }}</td>

                                        <td>{{ $item->invoiced_quantity }}</td>

                                        <td>{{ number_format($item->line_extension_amount, 2) }}</td>

                                        <td>{{ $item->connection_integrate }}</td>

                                        <td>{{ \Carbon\Carbon::parse($item->issue_date)->format('d/m/Y') }}</td>

                                    </tr>

                                @endforeach

                            @else

                                <tr>

                                    <td colspan="7" class="text-center text-muted">

                                        No items found for selected connection and date range.

                                    </td>

                                </tr>

                            @endif

                        </tbody>

                        @endif

                    </table>

                    @if($items->isNotEmpty())

                        <button type="button" class="btn btn-primary mt-3" id="openConfirmModal">🚀 Save to Invoice</button>

                    @endif

                </form>

            </div>

        </div>

    </div>



</div>



<!-- ✅ SweetAlert-Themed Confirmation Modal -->

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">

  <div class="modal-dialog modal-dialog-centered">

    <div class="modal-content" style="border-radius: 14px; border: none;">



      <!-- HEADER -->

      <div class="modal-header border-0 d-block text-center mt-2">

        <h5 class="modal-title fw-bold" id="confirmModalLabel" style="font-size: 1.25rem;">

          Confirm Submission

        </h5>

        <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>

      </div>



      <!-- BODY -->

      <div class="modal-body text-center px-4">

        <p>You are about to submit <strong><span id="selectedCount">0</span></strong> items.</p>

        <p>Total amount: <strong>RM <span id="totalAmount">0.00</span></strong></p>

        <p>Are you sure you want to proceed?</p>

      </div>



      <!-- FOOTER -->

      <div class="modal-footer border-0 justify-content-center pb-4">



        <!-- YES BUTTON: SweetAlert Green -->

        <button type="button" 

                id="confirmSubmit"

                class="btn px-4 py-2"

                style="background-color:#22c55e; color:white; border-radius:8px; font-weight:600;">

          Yes, Submit

        </button>



        <!-- CANCEL BUTTON: SweetAlert Grey -->

        <button type="button"

                class="btn px-4 py-2"

                data-bs-dismiss="modal"

                style="background-color:#6b7280; color:white; border-radius:8px; font-weight:600;">

          Cancel

        </button>



      </div>



    </div>

  </div>

</div>





<script>





/*if ($('#datatable-items tbody tr').length > 0 && $('#datatable-items tbody tr').first().find('td').length > 1) {

        $('#datatable-items').DataTable({

            dom: '<"d-flex justify-content-between mb-2"<"dt-buttons"B><"dataTables_filter"f>>rt<"d-flex justify-content-between mt-3"<"dataTables_info"i><"dataTables_paginate"p>>',

            buttons: [

                { extend: 'excelHtml5', text: 'Export Excel', className: 'buttons-excel', title: 'Consolidated_Items' },

                { extend: 'csvHtml5', text: 'Export CSV', className: 'buttons-csv', title: 'Consolidated_Items' },

                { extend: 'print', text: 'Print', className: 'buttons-print' }

            ],

            paging: true,

            searching: true,

            ordering: true,

            pageLength: 30

        });

    }

*/



$(document).ready(function () {



    // CHECK ALL

    $(document).on('click', '#checkAll', function () {

        let checked = $(this).is(':checked');

        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checked);

    });



    // OPEN CONFIRMATION MODAL

    $('#openConfirmModal').click(function () {



        let selected = [...document.querySelectorAll('.item-checkbox:checked')];



        if (selected.length === 0) {

            Swal.fire({

                icon: 'warning',

                title: 'No Items Selected',

                text: 'Please select at least one item.',

                confirmButtonColor: '#facc15'

            });

            return;

        }



        let total = 0;

        selected.forEach(function (cb) {

            let amount = parseFloat($(cb).closest('tr').find('td').eq(4).text().replace(/,/g, '')) || 0;

            total += amount;

        });



        $('#selectedCount').text(selected.length);

        $('#totalAmount').text(total.toFixed(2));

        $('#confirmModal').modal('show');

    });



    // SUBMIT SELECTED ITEMS

    $('#confirmSubmit').click(function () {



        let ids = [...document.querySelectorAll('.item-checkbox:checked')].map(cb => cb.value);

        console.log("Submitting IDs:", ids);



        if (ids.length === 0) {

            Swal.fire({

                icon: 'warning',

                title: 'No Items Selected',

                text: 'Please select at least one item.',

                confirmButtonColor: '#facc15'

            });

            return;

        }



        $.ajax({

            url: "{{ url('/developer/ConsolidateSelected') }}",

            method: "POST",

            data: {

                _token: "{{ csrf_token() }}",

                selected_items: ids,

                connection: $('#selected_connection').val()

            },



            // ⭐ SUCCESS HANDLER

            success: function (response) {



                $('#confirmModal').modal('hide'); // Close modal



                Swal.fire({

                    icon: 'success',

                    title: 'Success!',

                    text: response.message ?? 'Successfully submitted.',

                    confirmButtonText: 'OK',

                    confirmButtonColor: '#22c55e',

                    timer: 2500,

                    timerProgressBar: true,

                }).then(() => {

                    location.reload();

                });

            },



            // ⭐ ERROR HANDLER — treat as success (your requirement)

            error: function (xhr) {



                console.log("AJAX ERROR (but backend updated DB):", xhr.responseText);



                $('#confirmModal').modal('hide'); // Close modal anyway



                Swal.fire({

                    icon: 'success',  // ← treat as SUCCESS as requested

                    title: 'Success!',

                    text: 'Items submitted successfully.',

                    confirmButtonText: 'OK',

                    confirmButtonColor: '#22c55e',

                    timer: 2500,

                    timerProgressBar: true,

                }).then(() => {

                    location.reload();

                });

            }

        });

    });



});





</script>

@endsection

