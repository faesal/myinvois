<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>[{{ $invoice->invoice_no }}] MyInvois</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body {
  margin:0;
  padding:0;
  font-family: 'Segoe UI', Arial, sans-serif;
  background: linear-gradient(135deg, #1f4b7a, #2bbec3);
}

.wrapper {
  padding:40px 15px;
}

.card {
  max-width:640px;
  margin:auto;
  background:#ffffff;
  border-radius:12px;
  box-shadow:0 12px 40px rgba(0,0,0,.15);
  overflow:hidden;
}

.header {ce
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:18px 24px;
  border-bottom:1px solid #eee;
}

.logo {
  font-weight:600;
  font-size:25px;
  color:#1f4b7a;
}

.secure {
  font-size:12px;
  color:#999;
  letter-spacing:.5px;
}

.content {
  padding:40px 30px;
  text-align:center;
}

.invoice-no {
  font-weight:600;
  font-size:20px;
  color:#333;
  letter-spacing:1px;
}

.title {
  font-size:22px;
  margin:10px 0;
  color:#333;
}

.highlight {
  color:#f2994a;
  font-weight:600;
}

.desc {
  font-size:14px;
  color:#777;
  margin-top:8px;
}

.download-btn {
  margin:35px auto 25px;
  display:inline-block;
  padding:16px 36px;
  border-radius:8px;
  color:#fff;
  text-decoration:none;
  font-size:16px;
  background: linear-gradient(90deg, #2d5f9a, #2bbec3);
  box-shadow:0 6px 16px rgba(0,0,0,.2);
}

.wave {
  height:80px;
  background: linear-gradient(90deg, #2d5f9a, #2bbec3, #f2994a);
  border-radius:100% 100% 0 0;
  margin-top:40px;
}

.secure-box {
     text-align:center;
  margin: -40px 30px 30px;
  background:#f8f9fb;
  border-radius:6px;
  padding:12px 16px;
  font-size:12px;
  color:#555;
  box-shadow:0 4px 10px rgba(0,0,0,.08);
}

.footer {
  text-align:center;
  font-size:12px;
  color:#ddd;
  margin-top:25px;
}

.footer a {
  color:#ddd;
  text-decoration:none;
  margin:0 6px;
}
</style>
</head>

<body>

<div class="wrapper">
<br>
<br>
  <div class="card">

    <!-- HEADER -->
  


    <!-- CONTENT -->
    <div class="content">
   
   
    <div class="invoice-no" style="font-size:25px;color:#1f4b7a;">
    ☁ MySyncTax
      </div>
      <hr>
      <div class="invoice-no">
        [{{ $invoice->invoice_no }}]
      </div>

      <div class="title">
        A <strong>MyInvois</strong><br>
        <span class="highlight">has been shared with you</span>
      </div>

      <div class="desc">
        PDF file via MySyncTax. You can view the document securely
        by clicking the button below.
      </div>

      <a href="{{ url('/invoice/view') }}/{{ $invoice->unique_id }}"
         class="download-btn">
        ⬇ Download
      </a>

      <div class="wave"></div>

    </div>

    <!-- SECURE NOTE -->
    <div class="secure-box">
      🔒 <strong>Secure Link</strong><br>
      This link is unique to you. Please do not forward it to others.
    </div>

  </div>

  <!-- FOOTER -->
  <div class="footer">
    © {{ date('Y') }} MySyncTax ·
    <a href="#">Privacy Policy</a> ·
    <a href="#">Terms of Service</a>
  </div>
<br>
</div>

</body>
</html>
