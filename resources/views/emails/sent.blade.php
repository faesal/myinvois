<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MySyncTax e-Invoice #{{ $invoice->invoice_no }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', sans-serif;
      padding: 20px;
    }
    .container {
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      padding: 30px;
      max-width: 900px;
      margin: auto;
    }
    .header {
      background-color: #0056b3;
      color: #fff;
      padding: 20px;
      border-radius: 6px;
      text-align: center;
      margin-bottom: 25px;
    }
    .alert-info {
      background-color: #e8f0fe;
      border: 1px solid #c9defd;
      color: #333;
    }
    .qr-logo-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 20px;
    }
    .qr-logo-row img {
      height: 100px;
    }
    .table th {
      background-color: #0056b3;
      color: #fff;
    }
    .text-end {
      text-align: right;
    }
    @media only screen and (max-width: 600px) {
      .qr-logo-row {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="header">
      <h2>MySyncTax e-Invoice</h2>
    </div>

    <div class="alert alert-info text-center mb-4">
      MyInvois information has been emailed to you for your reference.
    </div>

    <div class="qr-logo-row">
      <img src="https://upload.wikimedia.org/wikipedia/commons/4/4e/LHDN_logo.png" alt="LHDN Logo">
      <canvas id="canvas"></canvas>
    </div>

    <div class="mb-4">
      <strong>E-Invoice No.:</strong> {{ $invoice->invoice_no }}<br>
      <strong>Date:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d-m-Y H:i:s') }}<br>
      <strong>Validation ID:</strong> {{ $invoice->submission_uuid }}
    </div>

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

    <h4 class="mb-3">Invoice Items</h4>
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

    <div class="text-end mt-4">
      <p><strong>Taxable Amount:</strong> MYR {{ number_format($invoice->taxable_amount, 2) }}</p>
      <p><strong>Tax Amount:</strong> MYR {{ number_format($invoice->tax_amount, 2) }}</p>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const canvas = document.getElementById('canvas');
      QRCode.toCanvas(canvas, 'https://xideasoft.com', {
        width: 100,
        errorCorrectionLevel: 'H'
      }, function (error) {
        if (error) console.error(error);
      });
    });
  </script>
</body>
</html>
