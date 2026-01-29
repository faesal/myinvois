<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_no }}</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { background:#f4f6f9; }

        .invoice-container {
            background:#fff;
            padding:20px;
            border-radius:6px;
            box-shadow:0 4px 10px rgba(0,0,0,.08);
            max-width: 900px;
            margin: 0 auto;
        }

        /* ===== HEADER ===== */
        .invoice-header table { width:100%; border:1px solid #000; border-collapse:collapse; }
        .invoice-header td { padding:6px; vertical-align:top; }
        
        .invoice-title { font-size:14px; font-weight:bold; margin:0; }
        .invoice-sub { font-size:10px; margin:2px 0; }

        /* LOGO & QR ALIGNMENT */
        .header-right-container {
            display: flex; align-items: center; justify-content: flex-end; gap: 10px;
        }
        .lhdn-logo { width: 160px; height: auto; }

        /* VALIDATION STRIP */
        .validation-strip {
            border:1px solid #000; margin-top:4px; font-size:9px; padding:4px; word-break: break-all;
        }

        /* SECTION */
        .section-title { font-size:10px; font-weight:bold; margin:8px 0 4px; }

        /* TABLE */
        table.data-table { width:100%; border-collapse:collapse; }
        table.data-table th, table.data-table td { border:1px solid #000; padding:3px; font-size:9px; }
        table.data-table th { background:#eee; font-weight:bold; }
        .right { text-align:right; }

        /* ==========================================
           MOBILE SCREEN VIEW
           ========================================== */
        @media screen and (max-width:768px){
            .invoice-container { padding:12px; margin: 10px; }
            .invoice-header td { padding: 4px; }
            
            .header-right-container {
                display: flex !important;
                flex-direction: row !important;
                justify-content: flex-end !important;
                align-items: center !important;
                gap: 8px !important;
            }
            .lhdn-logo { width: 80px !important; }
            #invoiceQR { width: 70px !important; height: 70px !important; }

            .table-responsive-custom {
                display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;
            }
            .data-table { min-width: 450px; }
            .data-table tr td[width="50%"] { display: table-cell; width: 50% !important; }
        }

        /* ==========================================
           PRINT / PDF VIEW
           ========================================== */
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: #fff; -webkit-print-color-adjust: exact; }
            .invoice-container {
                box-shadow: none; margin: 0; padding: 0; max-width: 100%; width: 100%; border: none;
            }
            .table-responsive-custom { overflow: visible !important; display: block !important; }
            .data-table { min-width: 100% !important; width: 100% !important; }
            .lhdn-logo { width: 140px !important; }
            #invoiceQR { width: 100px !important; height: 100px !important; }
            .no-print, #btnGeneratePDF, .btn { display: none !important; }
        }
    </style>
</head>
<body>

    @php
        function getStateName($code) {
            if (!$code) return '';
            $state = \Illuminate\Support\Facades\DB::table('lookup_state')
                        ->where('state_code', $code)
                        ->first();
            // FIX: Force Uppercase
            return $state ? strtoupper($state->state_name) : $code;
        }

        $supplierState = getStateName($supplier->country_subentity_code);
        $customerState = getStateName($customer->country_subentity_code);
    @endphp

    <div class="container mt-3 mb-5">

        <div id="invoicePDF" class="invoice-container">

            <div class="invoice-header">
                <table>
                    <tr>
                        <td width="60%">
                            <div class="invoice-title">e-Invoice</div>
                            <div class="invoice-sub">Invoice No.: {{ $invoice->invoice_no }}</div>
                        </td>
                        <td width="40%" align="right">
                            <div class="header-right-container">
                                <img src="{{url('/assets/images/')}}/LHDN_logo.png" class="lhdn-logo" alt="LHDN Logo" crossorigin="anonymous">
                                <canvas id="invoiceQR"></canvas>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="validation-strip">
                <strong>LHDN UUID:</strong> {{ $invoice->uuid }}<br>
                <strong>MYSYNCTAX UUID:</strong> {{ $invoice->unique_id }}<br>
                <strong>Date and Time of Validation:</strong>
                {{ \Carbon\Carbon::parse($invoice->created_at)->format('d-m-Y H:i:s') }}
            </div>

            <div class="table-responsive-custom">
                <table class="data-table" style="margin-top:8px;">
                    <tr>
                        @if ($invoice->invoice_type_code=='01' || $invoice->invoice_type_code=='02' || $invoice->invoice_type_code=='03' || $invoice->invoice_type_code=='04')
                        
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
                            {{ $supplierState }},
                            {{ $supplier->postal_zone }},
                            {{ str_replace('MYS', 'MALAYSIA', $supplier->country_code) }}
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
                            {{ $customerState }},
                            {{ $customer->postal_zone }},
                            {{ str_replace('MYS', 'MALAYSIA', $customer->country_code) }}
                        </td>
                        @else

                        <td width="50%">
                            <div class="section-title">Supplier Details</div>
                            Name: {{ $customer->registration_name }}<br>
                            TIN: {{ $customer->tin_no }}<br>
                            Identification No.: {{ $customer->identification_no }}<br>
                            Email: {{ $customer->email }}<br>
                            Contact Number: {{ $customer->phone }}<br>
                            Address:
                            {{ $customer->address_line_1 }} {{ $customer->address_line_2 }},
                            {{ $customer->city_name }},
                            {{ $customerState }},
                            {{ $customer->postal_zone }},
                            {{ str_replace('MYS', 'MALAYSIA', $customer->country_code) }}
                        </td>

                        <td width="50%">
                            <div class="section-title">Buyer Details</div>
                            Name: {{ $supplier->registration_name }}<br>
                            TIN: {{ $supplier->tin_no }}<br>
                            Identification No.: {{ $supplier->identification_no }}<br>
                            Email: {{ $supplier->email }}<br>
                            Contact Number: {{ $supplier->phone }}<br>
                            Address:
                            {{ $supplier->address_line_1 }} {{ $supplier->address_line_2 }},
                            {{ $supplier->city_name }},
                            {{ $supplierState }},
                            {{ $supplier->postal_zone }},
                            {{ str_replace('MYS', 'MALAYSIA', $supplier->country_code) }}
                        </td>
                        
                        @endif
                    </tr>
                </table>
            </div>

            <div class="section-title">Invoice Items</div>

            <div class="table-responsive-custom">
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
                        @php 
                            $i = 1; 
                            $calculatedTotal = 0; 
                            $totalDiscount = 0; 
                        @endphp

                        @foreach($items as $item)
                        <tr>
                            <td>{{ $i++ }}</td>
                            <td>{{ $item->item_description }}</td>
                            <td class="right">{{ $item->invoiced_quantity }}</td>
                            <td class="right">{{ number_format($item->price_amount,2) }}</td>
                            <td class="right">{{ number_format($item->price_discount,2) }}</td>
                            <td class="right">{{ number_format($item->price_extension_amount,2) }}</td>
                        </tr>
                        @php 
                            $calculatedTotal += $item->line_extension_amount; 
                            $totalDiscount += $item->price_discount;
                        @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>

            @php
                $finalPayable = $invoice->taxable_amount + $invoice->tax_amount;
            @endphp

            <div class="table-responsive-custom">
                <table class="data-table" style="margin-top:6px;">
                    <tr>
                        <td class="right">Total Discount</td>
                        <td class="right">MYR {{ number_format($totalDiscount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="right">Total Excluding Tax</td>
                        <td class="right" width="20%">MYR {{ number_format($invoice->taxable_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="right">Total Tax Amount</td>
                        <td class="right">MYR {{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="right"><strong>Total Payable Amount</strong></td>
                        <td class="right"><strong>MYR {{ number_format($finalPayable, 2) }}</strong></td>
                    </tr>
                </table>
            </div>

        </div>

        <div class="mt-4 text-center">
            <button id="btnGeneratePDF" class="btn btn-primary btn-lg w-100 mb-3" style="max-width: 300px;">
                📄 Download / Print PDF
            </button>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>

    <script>
        const uuid = "{{ $invoice->unique_id }}";

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

        // NATIVE PRINT (Fix for Mobile)
        $('#btnGeneratePDF').on('click', function () {
            window.print();
        });
    </script>
</body>
</html>