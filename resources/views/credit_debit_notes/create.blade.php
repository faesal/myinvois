@extends('layouts.app')

@section('content')
@php
    $noteType = $noteType ?? 'credit';

    $title = ucfirst($noteType) . ' Note';

    $routePrefix = match($noteType) {
        'credit' => 'credit_note',
        'debit' => 'debit_note',
        'refund' => 'refund_note',
        default => 'credit_note'
    };

    $store = route('note.store', ['note_type' => $routePrefix]);
    $redirect = url("/{$routePrefix}/listing");
    $fetchRoute = url("/{$routePrefix}/fetchInvoiceItems");
@endphp

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<div class="container">
    <h2 class="text-center mb-4">MySyncTax e-Invoice - {{ $title }}</h2>

    <div class="mb-4">
        <select id="invoiceSelect" class="form-select select2" style="width: 100%;">
            <option value="">Choose Invoice</option>
            @foreach ($invoices as $invoice)
                <option value="{{ $invoice->id_invoice }}">{{ $invoice->invoice_no }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary mt-2" id="searchInvoice">Search Invoice</button>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="alert alert-success">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>E-Invoice No:</strong> <span id="einvoice_no"></span></div>
                    <div class="col-md-4"><strong>Date:</strong> <span id="invoice_date"></span></div>
                    <div class="col-md-4"><strong>UUID:</strong> <span id="invoice_uuid"></span></div>
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

    <div class="card">
        <div class="card-body">
            <form id="creditNoteForm">
                @csrf
                <input type="hidden" name="original_invoice_id" id="original_invoice_id">
                <input type="hidden" name="total_credit_note" id="total_credit_note">
                <input type="hidden" name="note_type" value="{{ $noteType }}">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Discount</th>
                                <th>Tax</th>
                                <th>Total</th>
                                <th>Select</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end"><strong>Total {{ $title }}:</strong></td>
                                <td colspan="2" id="totalAmount">MYR 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <button type="button" class="btn btn-success mt-3" id="submitCreditNote">Submit {{ $title }}</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="submitModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Submission Status</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="modalMessage">Submitting your {{ $title }}...</div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div></div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    $('.select2').select2({ theme: 'bootstrap-5', placeholder: 'Choose Invoice', allowClear: true });

    $('#searchInvoice').click(function () {
        const invoiceId = $('#invoiceSelect').val();
        if (!invoiceId) return;

        $.get(`{{ $fetchRoute }}/${invoiceId}`, function (data) {
            $('#einvoice_no').text(data.invoice.invoice_no);
            $('#invoice_uuid').text(data.invoice.uuid);
            $('#original_invoice_id').val(data.invoice.id_invoice);
            $('#invoice_date').text(new Date(data.invoice.issue_date).toLocaleString());

            $('#supplier_name').text(data.supplier?.registration_name || '-');
            $('#supplier_ssm').text(data.supplier?.tin_no || '-');
            $('#supplier_address').text(`${data.supplier?.address_line_1 || ''}, ${data.supplier?.city_name || ''}, ${data.supplier?.postal_zone || ''}`);

            $('#buyer_name').text(data.customer?.registration_name || '-');
            $('#buyer_ic').text(data.customer?.identification_no || '-');
            $('#buyer_address').text(`${data.customer?.address_line_1 || ''}, ${data.customer?.city_name || ''}, ${data.customer?.postal_zone || ''}`);

            let tbody = '', total = 0;
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
                            <input type="number" name="items[${i}][total]" class="form-control" value="${item.line_extension_amount}" disabled>
                        </td>
                        <td>
                            <input type="checkbox" class="select-item" data-index="${i}">
                            <input type="hidden" name="items[${i}][id_invoice_item]" value="${item.id_invoice_item}">
                            <input type="hidden" name="items[${i}][description]" value="${item.item_description}">
                        </td>
                    </tr>`;
            });

            $('#itemsTableBody').html(tbody);
            $('#totalAmount').text(`MYR ${total.toFixed(2)}`);
            $('#total_credit_note').val(total.toFixed(2));
        });
    });

    $(document).on('change', '.select-item', function () {
        const row = $(this).closest('tr');
        row.find('input[type=number]').prop('disabled', !this.checked);
        updateTotal();
    });

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

$('#submitCreditNote').click(function () {
    const formData = $('#creditNoteForm').serialize();

    // Optional: show a loading indicator
    Swal.fire({
        title: 'Submitting...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    $.post(`{{ $store }}`, formData, function (response) {

        // Close loading popup
        Swal.close();

        // Success popup
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

        // Close loading popup
        Swal.close();

        let msg = 'Submission failed.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
        }

        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: msg,
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
