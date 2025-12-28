<!DOCTYPE html>
<html lang="en">
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // UUID yang anda dapat dari sistem anda
        const uuid = '{{$invoice->uuid}}';

        // Panggil API anda untuk dapatkan link penuh
        fetch(`{{url('qr_link')}}/${uuid}`)
            .then(response => response.text())
            .then(generatedLink => {
               
                const canvas = document.getElementById('canvas');
                QRCode.toCanvas(canvas, generatedLink.trim(), {
                    width: 200,
                    errorCorrectionLevel: 'H'
                }, function (error) {
                    if (error) console.error(error);
                    else console.log('✅ QR code generated for:', generatedLink);
                });
            })
            .catch(error => {
                console.error('❌ Failed to fetch QR link:', error);
            });
    });
</script>

<head>
    <meta charset="UTF-8">
    <title>MySyncTax e-Invoice #{{ $invoice->invoice_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .header {
            background-color: #0056b3; /* Corporate color */
            color: white;
            padding: 20px;
            border-radius: 5px;
        }
        .table th {
            background-color: #0056b3; /* Corporate color */
            color: white;
        }
        .table-bordered {
            border: 2px solid #dee2e6;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .qr-logo-container {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }
        .qr-logo-container img {
            width: 200px;
            height: auto;

        }
    </style>
</head>

<body class="p-4">
    <div class="container border p-4">
        <div class="header text-center mb-4">
            <h4>MySyncTax e-Invoice</h4>
        </div>
        
        <div class="qr-logo-container mb-3">
            
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <img height="150px" width="150px" src="https://upload.wikimedia.org/wikipedia/commons/4/4e/LHDN_logo.png" alt="LHDN Logo">
                <canvas id="canvas" style="display: inline-block;"></canvas>
            </div>
        </div>

        <div class="mb-3">
            <strong>E-Invoice No.:</strong> {{ $invoice->invoice_no }}<br>
            <strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d-m-Y H:i:s') }}<br>
            <strong>Validation ID:</strong> {{ $invoice->submission_uuid }}
        </div>

        <hr>

        <div class="row mb-4">
        <div class="col-md-6">
                <h4>Supplier</h4>
                <p>
                <strong>Name:</strong> {{ $supplier->registration_name }}<br>
                <strong>TIN:</strong> {{ $supplier->tin_no }}<br>
                <strong>ID No.:</strong> {{ $supplier->identification_no }}<br>
                <strong>Email:</strong> {{ $supplier->email }}<br>
                <strong>Phone:</strong> {{ $supplier->phone }}<br>
                <strong>Address:</strong> {{ $supplier->address_line_1 }} {{ $supplier->address_line_2 }},
                {{ $supplier->city_name }}, {{ $supplier->postal_zone }}, {{ $supplier->country_code }}
                </p>
            </div>
            <div class="col-md-6">
                <h4>Buyer</h4>
                <p>
                <strong>Name:</strong> {{ $customer->registration_name }}<br>
                <strong>TIN:</strong> {{ $customer->tin_no }}<br>
                <strong>ID No.:</strong> {{ $customer->identification_no }}<br>
                <strong>Email:</strong> {{ $customer->email }}<br>
                <strong>Phone:</strong> {{ $customer->phone }}<br>
                <strong>Address:</strong> {{ $customer->address_line_1 }} {{ $customer->address_line_2 }},
                {{ $customer->city_name }}, {{ $customer->postal_zone }}, {{ $customer->country_code }}
                </p>
            </div>
        </div>

        <h5>Invoice Items</h5>
        <table class="table table-bordered" width="100%">
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
                    <td>{{ $item->item_description }}</td>
                    <td>{{ $item->invoiced_quantity }}</td>
                    <td>{{ number_format($item->price_amount, 2) }}</td>
                    <td>{{ number_format($item->price_discount, 2) }}</td>
                    <td>{{ number_format($item->line_extension_amount, 2) }}</td>
                </tr>
                @php $total += $item->line_extension_amount; @endphp
                @endforeach
            </tbody>
        </table>

        <div class="text-end">
        <p><strong>Total Amount:</strong> MYR {{ number_format($invoice->price, 2) }}</p>
            <p><strong>Taxable Amount:</strong> MYR {{ number_format($invoice->taxable_amount, 2) }}</p>
            <p><strong>Tax Amount:</strong> MYR {{ number_format($invoice->tax_amount, 2) }}</p>
        </div>
    </div>
</body>
</html>
