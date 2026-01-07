@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-4 text-center text-md-start">
            <h4 class="fw-bold mb-0">Edit Consolidate #{{ $invoice->invoice_no }}</h4>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Invoice Items</h5>
                <button onclick="addNewRow()" class="btn btn-dark btn-sm px-3">
                    <i class="ph-plus me-1"></i> Add Item
                </button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="text-muted small">
                        <tr>
                            <th style="width: 45%;">Item Description</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="invoice-items-body">
                        @foreach($items as $item)
                        <tr id="row-{{ $item->id_invoice_item }}">
                            <td>
                                <input type="text" class="form-control" value="{{ $item->item_description }}" id="desc-{{ $item->id_invoice_item }}">
                            </td>
                            <td>
                                <input type="number" class="form-control text-center" value="{{ $item->invoiced_quantity }}" id="qty-{{ $item->id_invoice_item }}">
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control text-center" value="{{ $item->price_amount }}" id="price-{{ $item->id_invoice_item }}">
                            </td>
                            <td class="fw-bold">
                                RM <span id="line-total-{{ $item->id_invoice_item }}">{{ number_format($item->line_extension_amount, 2) }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="javascript:void(0)" onclick="saveRow({{ $item->id_invoice_item }})" class="text-primary fs-4" title="Save">
                                        <i class="ph-floppy-disk"></i>
                                    </a>
                                    <a href="javascript:void(0)" onclick="deleteRow({{ $item->id_invoice_item }})" class="text-danger fs-4" title="Delete">
                                        <i class="ph-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row justify-content-end">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span class="fw-bold">RM <span id="display-subtotal">{{ number_format($invoice->consolidate_total_amount_before, 2) }}</span></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Tax (10%):</span>
                        <span class="fw-bold">RM <span id="display-tax">{{ number_format($invoice->tax_amount, 2) }}</span></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Total:</h5>
                        <h4 class="fw-bold text-dark mb-0">RM <span id="display-total">{{ number_format($invoice->consolidate_complete_total, 2) }}</span></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
// 1. Function to Add a New Row
function addNewRow() {
    fetch("{{ route('consolidate.item.add', $invoice->id_invoice) }}", {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(response => response.json())
    .then(result => {
        if(result.success) {
            const tbody = document.getElementById('invoice-items-body');
            const newRow = `
                <tr id="row-${result.id}" style="background-color: #fff9db;">
                    <td><input type="text" class="form-control" id="desc-${result.id}"></td>
                    <td><input type="number" class="form-control text-center" id="qty-${result.id}"></td>
                    <td><input type="number" step="0.01" class="form-control text-center" id="price-${result.id}"></td>
                    <td class="fw-bold">RM <span id="line-total-${result.id}">0.00</span></td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-3">
                            <a href="javascript:void(0)" onclick="saveRow(${result.id})" class="text-primary fs-4"><i class="ph-floppy-disk"></i></a>
                            <a href="javascript:void(0)" onclick="deleteRow(${result.id})" class="text-danger fs-4"><i class="ph-trash"></i></a>
                        </div>
                    </td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', newRow);
        }
    })
    .catch(error => console.error('Error adding row:', error));
}

// 2. Function to Save Row
function saveRow(id) {
    const qtyInput = document.getElementById('qty-' + id);
    const priceInput = document.getElementById('price-' + id);
    const descInput = document.getElementById('desc-' + id);

    const qty = qtyInput ? qtyInput.value : 0;
    const price = priceInput ? priceInput.value : 0;
    const description = descInput ? descInput.value : '';
    
    const data = {
        _token: '{{ csrf_token() }}',
        description: description,
        qty: qty,
        price: price
    };

    fetch("{{ url('/consolidate/item/update') }}/" + id, {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if(result.success) {
            // Update line total and grand totals
            document.getElementById('line-total-' + id).innerText = (qty * price).toFixed(2);
            document.getElementById('display-subtotal').innerText = result.new_subtotal;
            document.getElementById('display-tax').innerText = result.new_tax;
            document.getElementById('display-total').innerText = result.new_total;

            // Feedback
            const row = document.getElementById('row-' + id);
            row.style.transition = "background-color 0.5s";
            row.style.backgroundColor = "#d4edda";
            setTimeout(() => { row.style.backgroundColor = "transparent"; }, 1000);
        } else {
            alert('Update failed: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Save Error: Check your console (F12)');
    });
}

// 3. Function to Delete Row
function deleteRow(id) {
    if (confirm('Delete this item? Total will recalculate.')) {
        fetch("{{ url('/consolidate/item/delete-record') }}/" + id, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                document.getElementById('row-' + id).remove();
                document.getElementById('display-subtotal').innerText = result.new_subtotal;
                document.getElementById('display-tax').innerText = result.new_tax;
                document.getElementById('display-total').innerText = result.new_total;
            } else {
                alert('Delete failed: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Server error. Row was not deleted.');
        });
    }
}
</script>