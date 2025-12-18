@extends('layouts.developerLayout')

@section('content')

<style>
    .invoice-container {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }

    /* HEADER */
    .invoice-header-wrapper {
        position: relative;
        margin-bottom: 20px;
    }

    .invoice-header {
    
        padding: 20px;
        height:150px;
        border-radius: 6px;
        text-align: center;
    }

    /* QR TOP RIGHT (DESKTOP) */
    .invoice-qr-top {
        position: absolute;
        top: 15px;
        right: 15px;
        background: white;
        padding: 6px;
        border-radius: 6px;
    }

    .invoice-qr-top canvas {
        width: 120px !important;
        height: auto !important;
    }

    .section-title {
        font-weight: bold;
        font-size: 20px;
        margin-bottom: 10px;
    }

    .table th {
        background-color: #0056b3 !important;
        color: white !important;
        white-space: nowrap;
    }

    /* MOBILE RESPONSIVE */
    @media(max-width: 768px) {
        .invoice-header {
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .invoice-header h4 {
            font-size: 20px;
        }

        .invoice-header h6 {
            font-size: 14px;
        }

        .section-title {
            font-size: 18px;
            margin-top: 15px;
        }

        /* QR CENTERED ON MOBILE */
        .invoice-qr-top {
            position: static;
            text-align: center;
            margin-bottom: 15px;
        }

        .invoice-qr-top canvas {
            width: 150px !important;
        }
    }
</style>

<div class="invoice-container">

    <!-- HEADER + QR -->
    <div class="invoice-header-wrapper">

        <div class="invoice-qr-top">
            <canvas id="invoiceQR"></canvas>
        </div>

        <div class="invoice-header">
            <h4>MySyncTax e-Invoice</h4>
            <h6>#{{ $invoice->invoice_no }}</h6>
     
            @if ( $invoice->uuid!='')
       
            <h6>LHDN Validation ID - {{ $invoice->uuid }}</h6>
           
            @endif
        </div>

    </div>

    <hr>

    <!-- SUPPLIER & BUYER -->
    <div class="row mb-4">
        <div class="col-md-6 col-12 mb-3">
            <div class="section-title">Supplier</div>
            <p style="font-size:14px;">
                <strong>Name:</strong> {{ $supplier->registration_name }}<br>
                <strong>TIN:</strong> {{ $supplier->tin_no }}<br>
                <strong>ID No:</strong> {{ $supplier->identification_no }}<br>
                <strong>Email:</strong> {{ $supplier->email }}<br>
                <strong>Phone:</strong> {{ $supplier->phone }}<br>
                <strong>Address:</strong>
                {{ $supplier->address_line_1 }} {{ $supplier->address_line_2 }},
                {{ $supplier->city_name }},
                {{ $supplier->postal_zone }},
                {{ $supplier->country_code }}
            </p>
        </div>

        <div class="col-md-6 col-12 mb-3">
            <div class="section-title">Buyer</div>
            <p style="font-size:14px;">
                <strong>Name:</strong> {{ $customer->registration_name }}<br>
                <strong>TIN:</strong> {{ $customer->tin_no }}<br>
                <strong>ID No:</strong> {{ $customer->identification_no }}<br>
                <strong>Email:</strong> {{ $customer->email }}<br>
                <strong>Phone:</strong> {{ $customer->phone }}<br>
                <strong>Address:</strong>
                {{ $customer->address_line_1 }} {{ $customer->address_line_2 }},
                {{ $customer->city_name }},
                {{ $customer->postal_zone }},
                {{ $customer->country_code }}
            </p>
        </div>
    </div>

    <!-- ITEMS -->
    <div class="section-title">Invoice Items</div>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Discount</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                @php $i = 1; $total = 0; @endphp
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td style="white-space: normal;">{{ $item->item_description }}</td>
                        <td>{{ $item->invoiced_quantity }}</td>
                        <td>{{ number_format($item->price_amount, 2) }}</td>
                        <td>{{ number_format($item->price_discount, 2) }}</td>
                        <td>{{ number_format($item->line_extension_amount, 2) }}</td>
                    </tr>
                    @php $total += $item->line_extension_amount; @endphp
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- TOTAL -->
    <div class="text-end mt-3">
        <p><strong>Total Amount:</strong> MYR {{ number_format($total, 2) }}</p>
        <p><strong>Taxable Amount:</strong> MYR {{ number_format($invoice->taxable_amount, 2) }}</p>
        <p><strong>Tax Amount:</strong> MYR {{ number_format($invoice->tax_amount, 2) }}</p>
    </div>

    <div class="mt-4">
        <a href="{{ route('developer.invoices.index') }}" class="btn btn-secondary">
            ← Back
        </a>
    </div>

</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>

<script>
    const uuid = "{{ $invoice->uuid }}";

    fetch(`{{ url('qr_link') }}/${uuid}`)
        .then(res => res.text())
        .then(link => {
            QRCode.toCanvas(
                document.getElementById('invoiceQR'),
                link.trim(),
                {
                    width: 200,
                    errorCorrectionLevel: 'H'
                }
            );
        });
</script>
@endsection
