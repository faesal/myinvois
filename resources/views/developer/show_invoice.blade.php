@extends('layouts.developerLayout')

@section('content')

<style>
    body { background:#f4f6f9; }

    .invoice-container {
        background:#fff;
        padding:20px;
        border-radius:6px;
        box-shadow:0 4px 10px rgba(0,0,0,.08);
    }

    /* ===== HEADER ===== */
    .invoice-header table {
        width:100%;
        border:1px solid #000;
        border-collapse:collapse;
    }

    .invoice-header td {
        padding:6px;
        vertical-align:top;
    }

    .invoice-title {
        font-size:14px;
        font-weight:bold;
        margin:0;
    }

    .invoice-sub {
        font-size:10px;
        margin:2px 0;
    }

    /* LOGO & QR ALIGNMENT */
    .header-right-container {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .lhdn-logo {
        width: 160px;
        height: auto;
    }

    /* VALIDATION STRIP */
    .validation-strip {
        border:1px solid #000;
        margin-top:4px;
        font-size:9px;
        padding:4px;
    }

    /* SECTION */
    .section-title {
        font-size:10px;
        font-weight:bold;
        margin:8px 0 4px;
    }

    /* TABLE */
    table.data-table {
        width:100%;
        border-collapse:collapse;
    }

    table.data-table th,
    table.data-table td {
        border:1px solid #000;
        padding:3px;
        font-size:9px;
    }

    table.data-table th {
        background:#eee;
        font-weight:bold;
    }

    .right { text-align:right; }

    /* PDF MODE */
    .pdf-mode {
        font-size:9px;
    }

    .pdf-mode canvas {
        width:75px !important;
        height:auto !important;
        border:1px solid #000;
        padding:2px;
    }

    /* Ensure logo stays small in PDF */
    .pdf-mode .lhdn-logo {
        width: 80px !important;
    }

    @media(max-width:768px){
        .invoice-container { padding:12px; }
    }
</style>

<div class="container mt-3">

<div id="invoicePDF" class="invoice-container">

    <div class="invoice-header">
        <table>
            <tr>
                <td width="65%">
                    <div class="invoice-title">e-Invoice</div>
                    <div class="invoice-sub">Invoice No.: {{ $invoice->invoice_no }}</div>
                </td>
                <td width="35%" align="right">
                    <div class="header-right-container">
                        <img src="https://www.mysynctax.com/dev/assets/images/LHDN_logo.png" class="lhdn-logo" alt="LHDN Logo">
                        <canvas id="invoiceQR"></canvas>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="validation-strip">
        <strong>IRBM Unique Identifier Number:</strong> {{ $invoice->uuid }}<br>
        <strong>Date and Time of Validation:</strong>
        {{ \Carbon\Carbon::parse($invoice->created_at)->format('d-m-Y H:i:s') }}
    </div>

    <table class="data-table" style="margin-top:8px;">
        <tr>
            <td width="50%">
                <div class="section-title">Supplier Details</div>
                Name: {{ $supplier->registration_name }}<br>
                TIN: {{ $supplier->tin_no }}<br>
                Identification No.: {{ $supplier->identification_no }}<br>
                Email: {{ $supplier->email }}<br>
                Contact Number: {{ $supplier->phone }}<br>
                Address:
                {{ $supplier->address_line_1 }} {{ $supplier->address_line_2 }},
                {{ $supplier->city_name }},
                {{ $supplier->postal_zone }},
                {{ $supplier->country_code }}
            </td>
            <td width="50%">
                <div class="section-title">Buyer Details</div>
                Name: {{ $customer->registration_name }}<br>
                TIN: {{ $customer->tin_no }}<br>
                Identification No.: {{ $customer->identification_no }}<br>
                Email: {{ $customer->email }}<br>
                Contact Number: {{ $customer->phone }}<br>
                Address:
                {{ $customer->address_line_1 }} {{ $customer->address_line_2 }},
                {{ $customer->city_name }},
                {{ $customer->postal_zone }},
                {{ $customer->country_code }}
            </td>
        </tr>
    </table>

    <div class="section-title">Invoice Items</div>

    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Discount</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $i=1; $total=0; @endphp
            @foreach($items as $item)
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $item->item_description }}</td>
                <td class="right">{{ $item->invoiced_quantity }}</td>
                <td class="right">{{ number_format($item->price_amount,2) }}</td>
                <td class="right">{{ number_format($item->price_discount,2) }}</td>
                <td class="right">{{ number_format($item->line_extension_amount,2) }}</td>
            </tr>
            @php $total += $item->line_extension_amount; @endphp
            @endforeach
        </tbody>
    </table>

    <table class="data-table" style="margin-top:6px;">
        <tr>
            <td class="right">Total Excluding Tax</td>
            <td class="right" width="20%">MYR {{ number_format($total,2) }}</td>
        </tr>
        <tr>
            <td class="right">Total Tax Amount</td>
            <td class="right">MYR {{ number_format($invoice->tax_amount,2) }}</td>
        </tr>
        <tr>
            <td class="right"><strong>Total Payable Amount</strong></td>
            <td class="right"><strong>MYR {{ number_format($invoice->price,2) }}</strong></td>
        </tr>
    </table>

</div>

<div class="mt-3">
    <button id="btnGeneratePDF" class="btn btn-primary">
        📄 Download PDF
    </button>
</div>

</div>

@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    const uuid = "{{ $invoice->uuid }}";

    fetch(`{{ url('qr_link') }}/${uuid}`)
        .then(res => res.text())
        .then(link => {
            QRCode.toCanvas(
                document.getElementById('invoiceQR'),
                link.trim(),
                {
                    width: 150,
                    errorCorrectionLevel: 'H'
                }
            );
        });


    // JS PDF
    $('#btnGeneratePDF').on('click', function () {

        const el = document.getElementById('invoicePDF');
        el.classList.add('pdf-mode');

        html2pdf().set({
            margin: [5,5,5,5],
            filename: 'Invoice-{{ $invoice->invoice_no }}.pdf',
            image: { type:'jpeg', quality:0.98 },
            html2canvas: { 
                scale:2, 
                scrollY:0,
                useCORS: true // Added to allow the LHDN logo to load in PDF
            },
            jsPDF: { unit:'mm', format:'a4', orientation:'portrait' }
        }).from(el).save().then(() => {
            el.classList.remove('pdf-mode');
        });
    });
</script>
@endsection