@extends('layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<div class="container">
    <h2 id="pageTitle" class="text-center mb-4">Note Form</h2>

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

    <form id="noteForm">
        @csrf
        <input type="hidden" name="original_invoice_id" id="original_invoice_id">
        <input type="hidden" name="total_note" id="total_note">

        <div class="card">
            <div class="card-body">
                <table class="table table-hover table-bordered">
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
                            <td colspan="6" class="text-end"><strong>Total:</strong></td>
                            <td colspan="2" id="totalAmount">MYR 0.00</td>
                        </tr>
                    </tfoot>
                </table>

                <button type="button" class="btn btn-success mt-3" id="submitNoteBtn">Submit Note</button>
            </div>
        </div>
    </form>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Confirm Submission</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <p id="modalText">Are you sure you want to submit this note?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
            <button type="button" class="btn btn-primary" id="confirmSubmit">Yes, Submit</button>
        </div>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="submitModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Submission Status</h5></div>
        <div class="modal-body" id="modalMessage">Submitting...</div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    let noteType = 'note';
    const path = window.location.pathname;
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const submitModal = new bootstrap.Modal(document.getElementById('submitModal'));

    if (path.includes('/credit_note/create')) {
        noteType = 'credit';
        $('#pageTitle').text('Create Credit Note');
        $('#submitNoteBtn').text('Submit Credit Note');
        $('#modalTitle').text('Confirm Credit Note');
        $('#modalText').text('Are you sure you want to submit this Credit Note?');
    } else if (path.includes('/debit_note/create')) {
        noteType = 'debit';
        $('#pageTitle').text('Create Debit Note');
        $('#submitNoteBtn').text('Submit Debit Note');
        $('#modalTitle').text('Confirm Debit Note');
        $('#modalText').text('Are you sure you want to submit this Debit Note?');
    }

    $('.select2').select2({ theme: 'bootstrap-5', placeholder: 'Choose Invoice', allowClear: true });

    $('#searchInvoice').click(function () {
        const invoiceId = $('#invoiceSelect').val();
        if (!invoiceId) return;

        $.get(`/credit_note/fetchInvoiceItems/${invoiceId}`, function (data) {
            $('#original_invoice_id').val(data.invoice.id_invoice);
            $('#supplier_name').text(data.supplier?.registration_name || '-');
            $('#supplier_ssm').text(data.supplier?.tin_no || '-');
            $('#supplier_address').text(`${data.supplier?.address_line_1 || ''}, ${data.supplier?.city_name || ''}, ${data.supplier?.postal_zone || ''}`);
            $('#buyer_name').text(data.customer?.registration_name || '-');
            $('#buyer_ic').text(data.customer?.identification_no || '-');
            $('#buyer_address').text(`${data.customer?.address_line_1 || ''}, ${data.customer?.city_name || ''}, ${data.customer?.postal_zone || ''}`);

            let tbody = '';
            let total = 0;
            data.items.forEach((item, index) => {
                total += parseFloat(item.line_extension_amount);
                tbody += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.item_description}</td>
                        <td><input type="number" name="items[${index}][qty]" class="form-control" value="${item.invoiced_quantity}" disabled></td>
                        <td><input type="number" name="items[${index}][price]" class="form-control" value="${item.price_amount}" disabled></td>
                        <td><input type="number" name="items[${index}][discount]" class="form-control" value="${item.price_discount}" disabled></td>
                        <td><input type="number" name="items[${index}][tax]" class="form-control" value="0" disabled></td>
                        <td><input type="number" name="items[${index}][total]" class="form-control" value="${item.line_extension_amount}" disabled></td>
                        <td>
                            <input type="checkbox" class="select-item" data-index="${index}">
                            <input type="hidden" name="items[${index}][id_invoice_item]" value="${item.id_invoice_item}">
                            <input type="hidden" name="items[${index}][description]" value="${item.item_description}">
                        </td>
                    </tr>
                `;
            });

            $('#itemsTableBody').html(tbody);
            $('#totalAmount').text(`MYR ${total.toFixed(2)}`);
            $('#total_note').val(total.toFixed(2));
        });
    });

    $(document).on('change', '.select-item', function () {
        const row = $(this).closest('tr');
        row.find('input[type=number]').prop('disabled', !this.checked);
        updateTotal();
    });

    function updateTotal() {
        let total = 0;
        $('#itemsTableBody tr').each(function () {
            const row = $(this);
            if (row.find('.select-item').is(':checked')) {
                const qty = parseFloat(row.find('input[name*="[qty]"]').val()) || 0;
                const price = parseFloat(row.find('input[name*="[price]"]').val()) || 0;
                const discount = parseFloat(row.find('input[name*="[discount]"]').val()) || 0;
                const lineTotal = (qty * price) - discount;
                row.find('input[name*="[total]"]').val(lineTotal.toFixed(2));
                total += lineTotal;
            }
        });
        $('#totalAmount').text(`MYR ${total.toFixed(2)}`);
        $('#total_note').val(total.toFixed(2));
    }

    $('#submitNoteBtn').click(() => confirmModal.show());

    $('#confirmSubmit').click(function () {
        const formData = $('#noteForm').serialize();
        let route = '';

        if (noteType === 'credit') route = "{{ route('credit-note.store') }}";
        else if (noteType === 'debit') route = "{{ route('debit-note.store') }}";

        confirmModal.hide();
        $('#modalMessage').text('Submitting...');
        submitModal.show();

        $.post(route, formData, function () {
            $('#modalMessage').text(`${noteType.charAt(0).toUpperCase() + noteType.slice(1)} Note submitted successfully.`);
        }).fail(function (xhr) {
            $('#modalMessage').text('Submission failed: ' + xhr.responseText);
        });
    });
});
</script>
@endsection
