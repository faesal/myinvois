<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MySyncTax Account Access</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">

<p>Dear <strong>{{ $customer->registration_name }}</strong>,</p>

<p>
Your MySyncTax account has been successfully created and activated.
</p>

<p><strong>🔐 Account Credentials</strong></p>
<ul>
    <li>Username: <strong>{{ $customer->email }}</strong></li>
    <li>Password: <strong>{{ $password }}</strong></li>
</ul>

@if($sendProduction || $sendSandbox)
<p><strong>🌐 System Access</strong></p>
@endif

@if($sendProduction)
<p>
<strong>Production Environment</strong><br>
<a href="https://mysynctax.com/einvoice/" target="_blank">
https://mysynctax.com/einvoice/
</a>
</p>
@endif

@if($sendSandbox)
<p>
<strong>Sandbox Environment</strong><br>
<a href="https://mysynctax.com/v5/" target="_blank">
https://mysynctax.com/v5/
</a>
</p>
@endif

<p><strong>📅 Subscription Period</strong></p>
<ul>
    <li>Start Date: {{ $customer->start_subscribe }}</li>
    <li>End Date: {{ $customer->end_subscribe }}</li>
</ul>

<p>
Please keep your login credentials confidential.<br>
We strongly recommend changing your password after the first login.
</p>

<p>
If you require API integration, credit note, debit note, or self-billed invoice setup,
our technical team is ready to assist.
</p>

<p>
Thank you for choosing <strong>MySyncTax</strong> as your e-Invoice integration platform.
</p>

<p>
Best regards,<br>
<strong>MySyncTax Technical Team</strong><br>
<a href="https://mysynctax.com">https://mysynctax.com</a>
</p>

</body>
</html>
