@extends($layout ?? 'layouts.app')

@section('content')
@php
    // 1. Detect Mode from URL
    if(request()->has('type')) {
        $isSelfBill = request()->query('type') == 'self_bill';
    } else {
        $isSelfBill = $isSelfBill ?? false;
    }

    // 2. Set Dynamic Labels
    $title = $isSelfBill ? 'Create New Self-Bill Invoice' : 'Create New Invoice';
    $entityLabel = $isSelfBill ? 'Supplier' : 'Buyer';
    $primaryIdName = $isSelfBill ? 'id_supplier' : 'customer_id';

    // 3. URLs for the Toggle
    $urlStandard = url('invoice/create');
    $urlSelfBill = url('invoice/create?type=self_bill');
@endphp

<div class="container px-3 px-md-4">
    
    {{-- ✅ TOGGLE SWITCH --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body p-2 text-center">
            <span class="fw-bold me-3 text-muted">Invoice Mode:</span>
            <div class="btn-group" role="group">
                <a href="{{ $urlStandard }}" class="btn {{ !$isSelfBill ? 'btn-primary' : 'btn-outline-primary' }} btn-sm px-4">
                    <i class="ph-receipt me-1"></i> Standard Invoice
                </a>
                <a href="{{ $urlSelfBill }}" class="btn {{ $isSelfBill ? 'btn-primary' : 'btn-outline-primary' }} btn-sm px-4">
                    <i class="ph-file-text me-1"></i> Self-Bill Invoice
                </a>
            </div>
        </div>
    </div>

    <h4 class="mb-4 fw-bold text-center text-md-start">{{ $title }}</h4>

    <form id="invoiceForm">
        @csrf
        <input type="hidden" id="isSelfBillFlag" name="is_self_bill" value="{{ $isSelfBill ? '1' : '0' }}">
        <input type="hidden" name="redirect_type" value="{{ $isSelfBill ? 'self_bill' : 'standard' }}">
        <input type="hidden" name="price" id="total_price_input" value="0">

        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold small text-muted">Invoice Number</label>
                    <input type="text" name="invoice_no" class="form-control" placeholder="e.g. INV-2026-001" required>
                </div>
                
                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold small text-muted">Invoice Type</label>
                    @if($isSelfBill)
                        <select name="invoice_type_code" class="form-select fw-bold text-primary">
                            <option value="11" selected>11 - Self-Billed Invoice</option>
                            <option value="12">12 - Self-Billed Credit Note</option>
                            <option value="13">13 - Self-Billed Debit Note</option>
                            <option value="14">14 - Self-Billed Refund</option>
                        </select>
                    @else
                        <div class="form-control bg-light border-0">Standard Invoice</div>
                        <input type="hidden" name="invoice_type_code" value="01">
                    @endif
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label fw-bold small text-muted">Issue Date</label>
                    <input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
        </div>

        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3 fw-bold">{{ $entityLabel }} Information</h5>

                <div class="mb-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input buyerType" type="radio" name="buyer_type" value="existing" checked>
                        <label class="form-check-label">Existing {{ $entityLabel }}</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input buyerType" type="radio" name="buyer_type" value="new">
                        <label class="form-check-label">New {{ $entityLabel }}</label>
                    </div>
                </div>

                <div id="existingCustomerSection" class="mt-3">
                    <label class="form-label small fw-bold">Select Registered {{ $entityLabel }}</label>
                    <select name="{{ $primaryIdName }}" id="main_entity_id" class="form-select select2 w-100" required>
                        <option value="">Select {{ $entityLabel }}</option>
                        @foreach ($customers as $cust)
                            @php
                                if ($isSelfBill) {
                                    $isMatch = (isset($cust->is_selfbill_supplier) && $cust->is_selfbill_supplier == 1);
                                } else {
                                    $isMatch = (!isset($cust->is_selfbill_supplier) || $cust->is_selfbill_supplier == 0);
                                }
                            @endphp
                            @if($isMatch)
                                <option value="{{ $cust->id_customer }}">
                                    {{ strtoupper($cust->registration_name) }} ({{ $cust->tin_no ?? 'No TIN' }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div id="newCustomerSection" class="mt-3" style="display: none;">
                    <div class="row g-3">
                        <div class="col-12 col-md-6"><input name="company_name" class="form-control" placeholder="{{ $entityLabel }} Name" disabled></div>
                        <div class="col-12 col-md-6"><input name="tin_number" class="form-control" placeholder="TIN Number" disabled></div>
                        <div class="col-12 col-md-6">
                            <select name="identification_type" class="form-select select2 id_type w-100" disabled>
                                <option value="">Select Identification Type</option>
                                <option value="BRN">BUSINESS REGISTRATION NUMBER</option>
                                <option value="NRIC">NRIC</option>
                                <option value="PASSPORT">PASSPORT</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6"><input name="registration_number" class="form-control" placeholder="Registration Number" disabled></div>
                        <div class="col-12 col-md-6"><input name="email" type="email" class="form-control" placeholder="Email" disabled></div>
                        <div class="col-12 col-md-6"><input name="phone" type="number" class="form-control" placeholder="Phone" disabled></div>
                        <div class="col-12"><input type="text" name="address1" class="form-control" placeholder="Address Line 1" disabled></div>
                        <div class="col-12 col-md-4"><input name="city_name" class="form-control" placeholder="City" disabled></div>
                        <div class="col-12 col-md-4"><input name="postal_zone" type="number" class="form-control" placeholder="Postal Code" disabled></div>
                        <div class="col-12 col-md-4">
                            <select name="country_subentity_code" class="form-select" disabled>
                                <option value="">-- Select State --</option>
                               
                                <option value="01">Johor</option>
                                <option value="02">Kedah</option>
                                <option value="03">Kelantan</option>
                                <option value="04">Melaka</option>
                                <option value="05">Negeri Sembilan</option>
                                <option value="06">Pahang</option>
                                <option value="07">Pulau Pinang</option>
                                <option value="08">Perak</option>
                                <option value="09">Perlis</option>
                                <option value="10">Selangor</option>
                                <option value="11">Terengganu</option>
                                <option value="12">Sabah</option>
                                <option value="13">Sarawak</option>
                                <option value="14">W.P. Kuala Lumpur</option>
                                <option value="15">Labuan</option>
                                <option value="16">Putrajaya</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title fw-bold">Invoice Items</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="itemsTable" style="min-width: 800px;">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th style="width: 35%;">Description</th>
                                <th style="width: 10%;">Qty</th>
                                <th style="width: 15%;">Unit Price (RM)</th>
                                <th style="width: 15%;">Tax Rate (%)</th>
                                <th style="width: 15%;">Amount (RM)</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr>
                                <td><input type="text" name="items[0][description]" class="form-control" required></td>
                                <td><input type="number" name="items[0][qty]" class="form-control qty" value="1" required></td>
                                <td><input type="number" step="0.01" name="items[0][unit_price]" class="form-control price" required></td>
                                <td><input type="number" name="items[0][tax_rate]" class="form-control tax" value="0"></td>
                                <td><input type="text" class="form-control amount bg-light" readonly></td>
                                <td><button type="button" class="btn btn-outline-danger btn-sm w-100 removeRow">Remove</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="button" id="addItem" class="btn btn-primary btn-sm"><i class="ph ph-plus-circle me-1"></i> Add Item</button>
                </div>
            </div>
        </div>

        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body row g-3">
                <div class="col-12 col-md-5 col-lg-4 ms-auto">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal:</span><span id="subtotal" class="fw-bold">RM 0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Tax Amount:</span><span id="totalTax" class="fw-bold">RM 0.00</span></div>
                    <div class="d-flex justify-content-between pt-2 border-top"><strong class="fs-5">Total:</strong><span id="grandTotal" class="text-primary fw-bold fs-5">RM 0.00</span></div>
                </div>
            </div>
        </div>

        <div class="d-grid d-md-flex justify-content-md-end mb-5">
            <button type="button" id="submitInvoice" class="btn btn-success btn-lg px-5 shadow">
                {{ $isSelfBill ? 'Create Self-Bill & Submit' : 'Create Invoice & Submit' }}
            </button>
        </div>
    </form>
</div>

{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmSubmitModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Confirm Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Are you sure you want to submit this {{ $isSelfBill ? 'self-billed' : '' }} invoice?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">No</button>
                <button type="button" id="modalYes" class="btn btn-primary">Yes, Submit</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    const total = subtotal + tax;
    $('#subtotal').text('RM ' + subtotal.toFixed(2));
    $('#totalTax').text('RM ' + tax.toFixed(2));
    $('#grandTotal').text('RM ' + total.toFixed(2));
    $('#total_price_input').val(total.toFixed(2));
}

$(document).on('input', '.qty, .price, .tax', calculateTotals);

$('#addItem').click(function () {
    const row = `<tr>
        <td><input type="text" name="items[${rowIndex}][description]" class="form-control" required></td>
        <td><input type="number" name="items[${rowIndex}][qty]" class="form-control qty" value="1" required></td>
        <td><input type="number" step="0.01" name="items[${rowIndex}][unit_price]" class="form-control price" required></td>
        <td><input type="number" name="items[${rowIndex}][tax_rate]" class="form-control tax" value="0"></td>
        <td><input type="text" class="form-control amount bg-light" readonly></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm w-100 removeRow">Remove</button></td>
    </tr>`;
    $('#itemsBody').append(row);
    rowIndex++;
});

$(document).on('click', '.removeRow', function () { $(this).closest('tr').remove(); calculateTotals(); });

$('.buyerType').change(function () {
    const isNew = $(this).val() === 'new';
    $('#existingCustomerSection').toggle(!isNew);
    $('#newCustomerSection').toggle(isNew);
    $('#newCustomerSection').find("input, select").prop('disabled', !isNew).prop('required', isNew);
    $('#main_entity_id').prop('disabled', isNew).prop('required', !isNew);
});

$(document).ready(function () {
    $('.select2').select2({ theme: 'bootstrap-5', placeholder: 'Search...', allowClear: true });
    
    $('#submitInvoice').click(function () {
        const form = document.getElementById('invoiceForm');
        if (form.checkValidity()) { $('#confirmSubmitModal').modal('show'); } 
        else { form.reportValidity(); }
    });

    $('#modalYes').click(function () {
        const storeUrl = "{{ route('invoice.store_create') }}";
        const redirectUrl = $('#isSelfBillFlag').val() === '1' ? "{{ url('/self_bill/listing') }}" : "{{ url('/listing_submission') }}";
        const btn = $(this);
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Submitting...');
        
        $.ajax({
            url: storeUrl,
            method: "POST",
            data: $('#invoiceForm').serialize(),
            success: function (res) {
                $('#confirmSubmitModal').modal('hide');
                Swal.fire({ icon: 'success', title: 'Success!', text: 'Invoice submitted successfully to LHDN.' })
                .then(() => { window.location.href = redirectUrl; });
            },
            error: function (xhr) {
                btn.prop('disabled', false).text('Yes, Submit');
                
                let message = 'Submission failed.';
                const response = xhr.responseJSON;

                // 1. Process specific message from Controller (handles LHDN JSON)
                if (response?.message) {
                    message = response.message;
                    
                    // Attempt to parse nested LHDN JSON for visual clarity
                    try {
                        const nested = JSON.parse(message);
                        if (nested.error?.details) {
                            message = '<strong>LHDN Rejected Document:</strong><br><ul class="text-start mt-2 mb-0">';
                            nested.error.details.forEach(d => {
                                message += `<li>[${d.target}]: ${d.message}</li>`;
                            });
                            message += '</ul>';
                        }
                    } catch (e) {
                        // Not JSON, convert newlines to list if present from Controller manual string
                        if (message.includes('\n')) {
                           let listItems = message.split('\n').map(m => `<li>${m}</li>`).join('');
                           message = `<ul class="text-start mb-0">${listItems}</ul>`;
                        }
                    }
                } 
                // 2. Process Laravel Validation Errors
                else if (response?.errors) {
                    message = '<ul class="text-start mb-0">';
                    Object.values(response.errors).flat().forEach(err => {
                        message += `<li>${err}</li>`;
                    });
                    message += '</ul>';
                }

                Swal.fire({ 
                    icon: 'error', 
                    title: 'Submission Failed', 
                    html: `<div class="p-2 border rounded bg-light small text-secondary">${message}</div>`,
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    });
});
</script>
@endsection