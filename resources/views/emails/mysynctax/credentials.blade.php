<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MySyncTax Account Created</title>
</head>

<body style="margin:0;padding:0;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:20px 0;">
<tr>
<td align="center">

<!-- ================= CONTAINER ================= -->
<table width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px;background:#ffffff;
              border-radius:14px;
              box-shadow:0 10px 30px rgba(0,0,0,.08);
              overflow:hidden;">

<!-- ================= HEADER ================= -->
<tr>
<td style="background:#1e293b;color:#ffffff;padding:22px 24px;">
    <strong style="font-size:16px;">MySyncTax</strong>
    <span style="float:right;font-size:12px;opacity:.85;">
        e-Invoice Platform
    </span>
</td>
</tr>

<!-- ================= BODY ================= -->
<tr>
<td style="padding:26px 20px;color:#1f2937;font-size:14px;line-height:1.6;">

<p style="margin-top:0;">
✅ <strong>Account Successfully Created!</strong>
</p>

<p>
Dear <strong>{{ $customer->registration_name }}</strong>,
</p>

<p>
Your MySyncTax account has been successfully created and activated.
Below are your login credentials and system access details:
</p>

<!-- ===== ACCOUNT CREDENTIALS ===== -->
<div style="background:#f8fafc;border-radius:10px;padding:16px;margin:18px 0;">
    <strong>🔐 Account Credentials</strong>

    <div style="margin-top:10px;background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:8px;padding:10px;">
        <strong>Username</strong><br>
        {{ $customer->email }}
    </div>

    <div style="margin-top:10px;background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:8px;padding:10px;">
        <strong>Password</strong><br>
        {{ $password }}
    </div>
</div>

<!-- ===== SYSTEM ACCESS ===== -->
<div style="background:#f8fafc;border-radius:10px;padding:16px;margin:18px 0;">
    <strong>🌐 System Access</strong>


    <div style="margin-top:10px;background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:8px;padding:10px;">
        <strong>Production Environment (Link to LHDN Production)</strong><br>
        <a href="https://mysynctax.com/einvoice/login"
           target="_blank"
           style="display:inline-block;
                  color:#2563eb;
                  text-decoration:none;
                  word-break:break-all;
                  padding:6px 0;">
            https://mysynctax.com/einvoice/login
        </a>
    </div>



    <div style="margin-top:10px;background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:8px;padding:10px;">
        <strong>Sandbox Environment (Link to LHDN SandBox)</strong><br>
        <a href="https://mysynctax.com/v5/login"
           target="_blank"
           style="display:inline-block;
                  color:#2563eb;
                  text-decoration:none;
                  word-break:break-all;
                  padding:6px 0;">
            https://mysynctax.com/v5/login
        </a>
    </div>
  

    <div style="margin-top:10px;background:#ffffff;
                border:1px solid #e5e7eb;
                border-radius:8px;padding:10px;">
        <strong>API Documentation</strong><br>
        <a href="https://mysynctax.com/v5/developer/documentation"
           target="_blank"
           style="display:inline-block;
                  color:#2563eb;
                  text-decoration:none;
                  word-break:break-all;
                  padding:6px 0;">
            https://mysynctax.com/v5/developer/documentation
        </a>
    </div>
</div>


<!-- ===== ADDITIONAL SERVICES ===== -->
<div style="background:#eff6ff;border-radius:10px;padding:16px;margin:18px 0;">
    <strong>🛠 Additional Services Available</strong>
    <p style="margin:8px 0 0;">
        • API Integration<br>
        • Credit Note Setup<br>
        • Debit Note Setup<br>
        • Self-billed Invoice<br>
        • Add Customer<br>
        • Add Supplier (Self-Bill)<br>
    </p>
</div>

<p style="text-align:center;margin-top:30px;">
Thank you for choosing <strong>MySyncTax</strong> as your e-Invoice integration platform.<br>
We’re excited to partner with you!
</p>

</td>
</tr>

<!-- ================= FOOTER ================= -->
<tr>
<td style="background:#1e293b;
           color:#cbd5f5;
           text-align:center;
           font-size:12px;
           padding:16px;">
    Best regards,<br>
    <strong>MySyncTax Technical Team</strong><br>
    <a href="https://mysynctax.com"
       target="_blank"
       style="color:#93c5fd;text-decoration:none;">
        https://mysynctax.com
    </a>
</td>
</tr>

</table>
</td>
</tr>
</table>

</body>
</html>
