<!DOCTYPE html>
<html>
<head>
    <title>Your e-Invoice</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">

    <h2>Hello {{ $customer->name ?? 'Customer' }},</h2>

    <p>Thank you for your business with {{ $supplier->name ?? 'us' }}.</p>
    
    <p>Your official LHDN-validated e-Invoice (<strong>#{{ $invoice->invoice_no }}</strong>) has been generated successfully.</p>

    <p style="margin-top: 20px;">
        <a href="{{ $invoiceLink }}" target="_blank" style="background-color: #0056b3; color: #ffffff; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
            View / Download e-Invoice PDF
        </a>
    </p>

    <p style="margin-top: 20px;">
        If the button above does not work, copy and paste the following link into your browser:<br>
        <a href="{{ $invoiceLink }}">{{ $invoiceLink }}</a>
    </p>

    <p>Best regards,<br>
    {{ $supplier->name ?? 'Your Company' }}</p>

</body>
</html>