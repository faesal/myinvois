@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4 fw-bold">Create New Invoice</h4>

    <form id="invoiceForm">
        @csrf

        <!-- Invoice Details -->
        <div class="card mb-3">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label>Invoice Number</label>
                    <input type="text" name="invoice_no" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Issue Date</label>
                    <input type="date" name="issue_date" class="form-control" required>
                </div>
            </div>
        </div>

        <!-- Buyer Info -->
        <div class="card mb-3">
            <div class="card-body">
                <h5>Buyer Information</h5>

                <div class="form-check form-check-inline">
                    <input class="form-check-input buyerType" type="radio" name="buyer_type" value="existing" checked>
                    <label class="form-check-label">Existing Customer</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input buyerType" type="radio" name="buyer_type" value="new">
                    <label class="form-check-label">New Customer</label>
                </div>

                <div id="existingCustomerSection" class="mt-3">
                    <label>Select Customer</label>
                    <select name="customer_id" class="form-select select2">
                        <option value="">Select Customer</option>
                        @foreach ($customers as $cust)
                            <option value="{{ $cust->id_customer }}">{{ $cust->registration_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Buyer Info -->
<!-- New Customer Section (updated fields) -->
<div id="newCustomerSection" class="mt-3" style="display: none;">
    <div class="row g-3">
        <div class="col-md-6">
            <input name="company_name" class="form-control" placeholder="Company Name" required>
        </div>
        <div class="col-md-6">
            <input name="tin_number" class="form-control" placeholder="TIN Number" required>
        </div>
        <div class="col-md-6">
            <select name="identification_type" class="form-select select2 id_type" required>
                <option value="">Select Identification Type</option>
                <option value="NRIC">NRIC</option>
                <option value="BRN">BUSINESS REGISTRATION NUMBER</option>
            </select>
        </div>
        <div class="col-md-6">
            <input name="registration_number" class="form-control" placeholder="Registration Number" required>
        </div>
        <div class="col-md-6">
            <input name="email" type="email" class="form-control" placeholder="Email" required pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Enter a valid email address">
        </div>
        <div class="col-md-6">
            <input name="phone" type="number" class="form-control" placeholder="Phone" required pattern="^\d{8,}$" title="Phone number must be at least 8 digits">
        </div>
        <div class="col-12">
            <input type="text" name="address1" class="form-control" placeholder="Address Line 1" required>
        </div>
        <div class="col-12">
            <input type="text" name="address2" class="form-control" placeholder="Address Line 2">
        </div>
        <div class="col-12">
            <input type="text" name="address3" class="form-control" placeholder="Address Line 3">
        </div>
        <div class="col-md-6">
            <input name="city_name" class="form-control" placeholder="City" required>
        </div>
        <div class="col-md-6">
            <input name="postal_zone" type="number"  min="5" class="form-control" placeholder="Postal Code" required pattern="^\d{5}$" title="Postal code must be 5 digits">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">State Code <span class="text-danger">*</span></label>
            <select name="country_subentity_code" class="form-control w-auto" required>
                <option value="">-- Select State --</option>
                <option value="01">Johor</option>
                <option value="02">Kedah</option>
                <option value="03">Kelantan</option>
                <option value="04">Melaka</option>
                <option value="05">Negeri Sembilan</option>
                <option value="06">Pahang</option>
                <option value="07">Perak</option>
                <option value="08">Perlis</option>
                <option value="09">Pulau Pinang</option>
                <option value="10">Sabah</option>
                <option value="11">Sarawak</option>
                <option value="12">Selangor</option>
                <option value="13">Terengganu</option>
                <option value="14">Wilayah Persekutuan Kuala Lumpur</option>
                <option value="15">Wilayah Persekutuan Labuan</option>
                <option value="16">Wilayah Persekutuan Putrajaya</option>
            </select>
        </div>
    </div>
</div>

            </div>
        </div>

        <!-- Invoice Items -->
        <div class="card mb-3">
            <div class="card-body">
                <h5>Invoice Items</h5>
                <table class="table table-bordered" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Tax Rate (%)</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr>
                            <td><input type="text" name="items[0][description]" class="form-control" required></td>
                            <td><input type="number" name="items[0][qty]" class="form-control qty" required></td>
                            <td><input type="number" name="items[0][unit_price]" class="form-control price" required></td>
                            <td><input type="number" name="items[0][tax_rate]" class="form-control tax"></td>
                            <td><input type="text" class="form-control amount" readonly></td>
                            <td><button type="button" class="btn btn-danger removeRow">Remove</button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" id="addItem" class="btn btn-primary mt-2">Add Item</button>
            </div>
        </div>

        <!-- Summary -->
        <div class="card mb-3">
            <div class="card-body row g-3">
                <div class="col-md-4 offset-md-8">
                    <div class="d-flex justify-content-between">
                        <strong>Subtotal:</strong>
                        <span id="subtotal">RM 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong>Tax:</strong>
                        <span id="totalTax">RM 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <span id="grandTotal" class="text-danger fw-bold">RM 0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end">
            <button type="button" id="submitInvoice" class="btn btn-success">Create Invoice & Submit</button>
        </div>
    </form>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header text-dark">
        <h5 class="modal-title">Confirm Submission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to submit this invoice?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" id="modalYes" class="btn btn-danger">Yes, Submit</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let rowIndex = 1;

function calculateTotals() {
    let subtotal = 0, tax = 0;
    $('#itemsBody tr').each(function () {
        const qty = parseFloat($(this).find('.qty').val()) || 0;
        const price = parseFloat($(this).find('.price').val()) || 0;
        const taxRate = parseFloat($(this).find('.tax').val()) || 0;

        const amount = qty * price;
        const taxAmt = amount * (taxRate / 100);
        $(this).find('.amount').val((amount + taxAmt).toFixed(2));
        subtotal += amount;
        tax += taxAmt;
    });

    $('#subtotal').text('RM ' + subtotal.toFixed(2));
    $('#totalTax').text('RM ' + tax.toFixed(2));
    $('#grandTotal').text('RM ' + (subtotal + tax).toFixed(2));
}

$(document).on('input', '.qty, .price, .tax', calculateTotals);

$('#addItem').click(function () {
    const row = `
        <tr>
            <td><input type="text" name="items[${rowIndex}][description]" class="form-control" required></td>
            <td><input type="number" name="items[${rowIndex}][qty]" class="form-control qty" required></td>
            <td><input type="number" name="items[${rowIndex}][unit_price]" class="form-control price" required></td>
            <td><input type="number" name="items[${rowIndex}][tax_rate]" class="form-control tax"></td>
            <td><input type="text" class="form-control amount" readonly></td>
            <td><button type="button" class="btn btn-danger removeRow">Remove</button></td>
        </tr>`;
    $('#itemsBody').append(row);
    rowIndex++;
});

$(document).on('click', '.removeRow', function () {
    $(this).closest('tr').remove();
    calculateTotals();
});

$('.buyerType').change(function () {
    $('#existingCustomerSection').toggle($(this).val() === 'existing');
    $('#newCustomerSection').toggle($(this).val() === 'new');
});

$(document).ready(function () {
    $('.select2').select2({ theme: 'bootstrap-5', placeholder: 'Select Customer', allowClear: true });
    $('.id_type').select2({ theme: 'bootstrap-5', placeholder: 'Select Identification Type', allowClear: true });

    $('#submitInvoice').click(function () {
        let buyerType = $('input[name="buyer_type"]:checked').val();
        console.log('Selected buyer type:', buyerType);

        if (buyerType === 'new') {
            const form = document.getElementById('invoiceForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
        }

        $('#confirmSubmitModal').modal('show');

    });

        $('#modalYes').click(function () {
            $(this).prop('disabled', true).text('Submitting...');
            $('#submitInvoice').prop('disabled', true);
            const formData = $('#invoiceForm').serialize();

            $.ajax({
                url: "{{ route('invoice.store') }}",
                method: "POST",
                data: formData,
                success: function () {
                    $('#confirmSubmitModal').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Invoice created successfully!',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#22c55e',
                        timer: 3000,
                        timerProgressBar: true,
                    }).then(() => {
                        window.location.href = '{{ url('/listing_submission') }}';
                    });
                },
                error: function (xhr) {
                    $('#modalYes').prop('disabled', false).text('Yes, Submit');
                    $('#submitInvoice').prop('disabled', false);

                    let message = 'Submission failed:\n';
                    if (xhr.responseJSON?.errors) {
                        for (const err of Object.values(xhr.responseJSON.errors)) {
                            message += '- ' + err[0] + '\n';
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ef4444',
                    });
                }
            });
        });

});
</script>
@endsection
