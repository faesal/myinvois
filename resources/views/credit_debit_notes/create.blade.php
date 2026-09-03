@extends('layouts.app')

@section('content')
@php
    $noteType = $noteType ?? 'credit';
    $title = ucfirst($noteType) . ' Note';

    // Original logic to determine prefix (e.g., 'credit_note')
    $routePrefix = match($noteType) {
        'credit' => 'credit_note',
        'debit' => 'debit_note',
        'refund' => 'refund_note',
        default => 'credit_note'
    };

    // Use custom routes if passed from controller (for Self-Bill), else use default
    $store = $customStoreRoute ?? route('note.store', ['note_type' => $routePrefix]);
    $redirect = $customRedirectRoute ?? url("/{$routePrefix}/listing");
    
    // Make sure we have a clean base URL for the JS
    $fetchRoute = $customFetchRoute ?? url("/{$routePrefix}/fetchInvoiceItems");
@endphp

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<div class="container">
    <h2 class="text-center mb-4">MySyncTax e-Invoice - {{ $title }}</h2>

    <div class="mb-4">
        <select id="invoiceSelect" class="form-select select2" style="width: 100%;">
            <option value="">Choose Invoice</option>
            @foreach ($invoices as $invoice)
                {{-- FILTER: Only show invoices that have been submitted (have a UUID) --}}
                @if(!empty($invoice->uuid))
                    <option value="{{ $invoice->id_invoice }}">
                        {{ $invoice->invoice_no }} ({{ $invoice->issue_date }})
                    </option>
                @endif
            @endforeach
        </select>
        <button class="btn btn-primary mt-3" id="searchInvoice">
            <i class="ph ph-magnifying-glass me-1"></i> Search Invoice
        </button>
    </div>

    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <div class="alert alert-success">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>E-Invoice No:</strong> <span id="einvoice_no"></span></div>
                    <div class="col-md-4"><strong>Date:</strong> <span id="invoice_date"></span></div>
                    <div class="col-md-4"><strong>UUID:</strong> <span id="invoice_uuid_display"></span></div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Supplier Info</h5>
                    <p><strong>Name:</strong> <span id="supplier_name"></span></p>
                    <p><strong>TIN:</strong> <span id="supplier_ssm"></span></p>
                    <p><strong>Address:</strong> <span id="supplier_address"></span></p>
                </div>
                <div class="col-md-6">
                    <h5>Buyer Info</h5>
                    <p><strong>Name:</strong> <span id="buyer_name"></span></p>
                    <p><strong>TIN:</strong> <span id="buyer_ic"></span></p>
                    <p><strong>Address:</strong> <span id="buyer_address"></span></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form id="creditNoteForm">
                @csrf
                <input type="hidden" name="original_invoice_id" id="original_invoice_id">
                {{-- Passed to Controller for LHDN Document Reference --}}
                <input type="hidden" name="original_uuid" id="original_uuid"> 
                <input type="hidden" name="total_credit_note" id="total_credit_note">
                <input type="hidden" name="note_type" value="{{ $noteType }}">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle" style="min-width: 800px;">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit Price (RM)</th>
                                <th>Discount (RM)</th>
                                <th>Tax (RM)</th>
                                <th>Total (RM)</th>
                                <th>Select</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Search for an invoice to load items.</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end fs-5"><strong>Total {{ $title }}:</strong></td>
                                <td colspan="2" id="totalAmount" class="fs-5 fw-bold text-primary">MYR 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-grid d-md-flex justify-content-md-end mt-4">
                    <button type="button" class="btn btn-success btn-lg px-5 shadow" id="submitCreditNote">
                        Submit {{ $title }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    // Initialize Select2
    $('.select2').select2({ theme: 'bootstrap-5', placeholder: 'Choose Invoice', allowClear: true });

    // Handle Search Button Click
    $('#searchInvoice').click(function (e) {
        e.preventDefault(); // FIX: Prevent default button behavior

        const invoiceId = $('#invoiceSelect').val();
        if (!invoiceId) {
            Swal.fire('Warning', 'Please select an invoice first.', 'warning');
            return;
        }

        let btn = $(this);
        let originalText = btn.html();
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span> Loading...').prop('disabled', true);

        // Ensure smooth URL construction without double slashes
        let baseUrl = "{{ $fetchRoute }}";
        let fetchUrl = baseUrl.endsWith('/') ? baseUrl + invoiceId : baseUrl + '/' + invoiceId;

        $.get(fetchUrl, function (data) {
            
            if(data.error) {
                Swal.fire('Error', data.error, 'error');
                return;
            }

            // Populate Header Data
            $('#einvoice_no').text(data.invoice.invoice_no);
            $('#invoice_uuid_display').text(data.invoice.uuid);
            $('#invoice_date').text(new Date(data.invoice.issue_date).toLocaleString());
            
            // Critical hidden fields for the controller
            $('#original_invoice_id').val(data.invoice.id_invoice);
            $('#original_uuid').val(data.invoice.uuid); 

            // Populate Customer/Supplier Data
            $('#supplier_name').text(data.supplier?.registration_name || '-');
            $('#supplier_ssm').text(data.supplier?.tin_no || '-');
            $('#supplier_address').text(`${data.supplier?.address_line_1 || ''}, ${data.supplier?.city_name || ''}, ${data.supplier?.postal_zone || ''}`);

            $('#buyer_name').text(data.customer?.registration_name || '-');
            $('#buyer_ic').text(data.customer?.identification_no || '-');
            $('#buyer_address').text(`${data.customer?.address_line_1 || ''}, ${data.customer?.city_name || ''}, ${data.customer?.postal_zone || ''}`);

            // Populate Items Table
            let tbody = '', total = 0;
            if(data.items && data.items.length > 0) {
                data.items.forEach((item, i) => {
                    total += parseFloat(item.line_extension_amount);
                    tbody += `
                        <tr>
                            <td>${i + 1}</td>
                            <td>${item.item_description}</td>
                            <td><input type="number" name="items[${i}][qty]" class="form-control" value="${item.invoiced_quantity}" disabled></td>
                            <td><input type="number" name="items[${i}][price]" class="form-control" value="${item.price_amount}" disabled></td>
                            <td><input type="number" name="items[${i}][discount]" class="form-control" value="${item.price_discount}" disabled></td>
                            <td><input type="number" name="items[${i}][tax]" class="form-control" value="0" disabled></td>
                            <td>
                                <input type="hidden" name="items[${i}][item_clasification_value]" value="${item.item_clasification_value}">
                                <input type="number" name="items[${i}][total]" class="form-control bg-light" value="${item.line_extension_amount}" readonly disabled>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input select-item" style="transform: scale(1.3);" data-index="${i}">
                                <input type="hidden" name="items[${i}][id_invoice_item]" value="${item.id_invoice_item}">
                                <input type="hidden" name="items[${i}][description]" value="${item.item_description}">
                            </td>
                        </tr>`;
                });
            } else {
                tbody = '<tr><td colspan="8" class="text-center text-muted">No items found for this invoice.</td></tr>';
            }

            $('#itemsTableBody').html(tbody);
            $('#totalAmount').text(`MYR ${total.toFixed(2)}`);
            $('#total_credit_note').val(total.toFixed(2));
            
        }).fail(function() {
            Swal.fire('Error', 'Failed to fetch invoice details. Please check the network tab.', 'error');
        }).always(function() {
            btn.html(originalText).prop('disabled', false);
        });
    });

    // Handle Item Selection (Enable/Disable Row)
    $(document).on('change', '.select-item', function () {
        const row = $(this).closest('tr');
        // Enable or disable inputs based on checkbox (except readonly total field)
        row.find('input[type=number]:not([readonly])').prop('disabled', !this.checked);
        updateTotal();
    });

    // Recalculate if numbers change
    $(document).on('input', 'input[type=number]', updateTotal);

    function updateTotal() {
        let total = 0;
        $('#itemsTableBody tr').each(function () {
            const row = $(this);
            const checked = row.find('.select-item').is(':checked');
            if (checked) {
                const qty = parseFloat(row.find('input[name*="[qty]"]').val()) || 0;
                const price = parseFloat(row.find('input[name*="[price]"]').val()) || 0;
                const discount = parseFloat(row.find('input[name*="[discount]"]').val()) || 0;
                const lineTotal = (qty * price) - discount;
                
                row.find('input[name*="[total]"]').val(lineTotal.toFixed(2));
                total += lineTotal;
            }
        });
        $('#totalAmount').text(`MYR ${total.toFixed(2)}`);
        $('#total_credit_note').val(total.toFixed(2));
    }

    // Handle Form Submission
    $('#submitCreditNote').click(function () {
        if ($('.select-item:checked').length === 0) {
            Swal.fire('Warning', 'Please select at least one item by checking the box on the right.', 'warning');
            return;
        }

        const formData = $('#creditNoteForm').serialize();

        Swal.fire({
            title: 'Submitting...',
            text: 'Please wait while we submit the document to LHDN.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.post(`{{ $store }}`, formData, function (response) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message ?? '{{ $title }} submitted successfully.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#22c55e',
                timer: 3000,
                timerProgressBar: true,
            }).then(() => {
                window.location = '{{ $redirect }}';
            });

        }).fail(function (xhr) {
            let msg = 'Submission failed.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            }
            
            // Format nested LHDN errors if present
            try {
                const nested = JSON.parse(msg);
                if (nested.error?.details) {
                    msg = '<ul class="text-start mb-0 mt-2">';
                    nested.error.details.forEach(d => {
                        msg += `<li>[${d.target}]: ${d.message}</li>`;
                    });
                    msg += '</ul>';
                }
            } catch (e) {}

            Swal.fire({
                icon: 'error',
                title: 'Submission Failed',
                html: msg,
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444',
            });
        });
    });
});
</script>

<style>
.table th, .table td {
    vertical-align: middle !important;
}
.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #f9fafb;
}
.table-hover tbody tr:hover {
    background-color: #e0f7fa;
}
</style>
@endsection