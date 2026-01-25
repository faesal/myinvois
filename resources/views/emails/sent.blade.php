<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>[{{ $invoice->invoice_no }}] MyInvois</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
  background:#f5f6f8;
  font-family: 'Segoe UI', sans-serif;
}
.mail-wrapper {
  max-width: 600px;
  margin: 40px auto;
  background:#fff;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,.06);
  padding: 30px;
  text-align: center;
}
.download-box {
  border: 1px dashed #dcdcdc;
  border-radius: 8px;
  padding: 30px;
  margin: 25px 0;
}
.download-box a {
  text-decoration: none;
}
.summary {
  text-align: left;
  margin-top: 20px;
}
.summary div {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}
.footer {
  font-size: 12px;
  color:#777;
  margin-top: 30px;
}
</style>
</head>

<body>

<div class="mail-wrapper">

  <!-- HEADER -->
  <strong><h5 style="font-size:28px" class="text-muted mb-1">MySyncTax</h5></strong>
  <h4 class="fw-bold">
    [{{ $invoice->invoice_no }}]
  </h4>
  <p class="mb-3">
    A <strong>MyInvois</strong> has been shared with you
  </p>

  <p class="text-muted">
    PDF file via MySyncTax. You can view the document securely by clicking the button below.
  </p>

  <!-- DOWNLOAD CTA -->
  <div class="download-box">
    <a href="{{ url('/invoice/view') }}/{{ $invoice->unique_id}}" class="btn btn-primary btn-lg">
      ⬇ View & Download
    </a>
  </div>

  <!-- SUMMARY -->
  <div class="summary">
    <div>
      <span>Subtotal (Taxable Amount)</span>
      <strong>MYR {{ number_format($invoice->taxable_amount,2) }}</strong>
    </div>
    <div>
      <span>Tax Amount</span>
      <strong>MYR {{ number_format($invoice->tax_amount,2) }}</strong>
    </div>
    <hr>
    <div class="fs-5">
      <span><strong>Total Amount</strong></span>
      <span class="text-primary">
        <strong>
          MYR {{ number_format($invoice->taxable_amount + $invoice->tax_amount,2) }}
        </strong>
      </span>
    </div>
  </div>

  <!-- SECURITY NOTE -->
  <div class="alert alert-light mt-4 text-start">
    🔒 <strong>Secure Link</strong><br>
    This link is unique to you. Please do not forward it to others.
  </div>

  <!-- FOOTER -->
  <div class="footer">
    © {{ date('Y') }} MySyncTax. All rights reserved.
  </div>

</div>

</body>
</html>
