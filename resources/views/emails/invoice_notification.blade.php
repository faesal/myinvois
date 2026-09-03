<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New e-Invoice Received</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    <table width="100%" bgcolor="#f4f5f7" cellpadding="0" cellspacing="0" border="0" style="padding: 40px 20px;">
        <tr>
            <td align="center">
                
                <table width="600" bgcolor="#ffffff" cellpadding="0" cellspacing="0" border="0" style="border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; text-align: left; max-width: 600px; width: 100%;">
                    
                    <tr>
                        <td style="padding: 30px; border-bottom: 1px solid #f0f2f5;">
                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td width="36" height="36" style="background-color: #0ea5e9; border-radius: 6px; text-align: center; vertical-align: middle;">
                                       
                                    </td>
                                    <td style="padding-left: 15px;">
                                        <strong style="font-size: 22px; color: #0f172a; letter-spacing: -0.5px;">MySyncTax</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 40px 30px 20px 30px;">
                            <h1 style="font-size: 22px; color: #0f172a; margin-top: 0; margin-bottom: 20px;">Hello {{ $customer->registration_name ?? 'Valued Customer' }},</h1>
                            
                            <p style="color: #475569; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                                You have received a new verified e-Invoice from <strong>{{ $supplier->registration_name ?? 'ABC Trading SDN BHD' }}</strong>. 
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 35px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="padding-bottom: 12px; color: #64748b; font-size: 14px;">Invoice Number</td>
                                                <td align="right" style="padding-bottom: 12px; color: #0f172a; font-size: 15px; font-weight: bold;">
                                                    {{ $invoice->invoice_no ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-bottom: 12px; border-top: 1px solid #e2e8f0; padding-top: 12px; color: #64748b; font-size: 14px;">Issue Date</td>
                                                <td align="right" style="padding-bottom: 12px; border-top: 1px solid #e2e8f0; padding-top: 12px; color: #0f172a; font-size: 15px;">
                                                    {{ isset($invoice->issue_date) ? \Carbon\Carbon::parse($invoice->issue_date)->format('d M Y') : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="border-top: 1px solid #e2e8f0; padding-top: 12px; color: #64748b; font-size: 14px;">Subtotal</td>
                                                <td align="right" style="border-top: 1px solid #e2e8f0; padding-top: 12px; color: #0ea5e9; font-size: 18px; font-weight: bold;">
                                                    RM {{ number_format($invoice->price ?? 0, 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="border-top: 1px solid #e2e8f0; padding-top: 12px; color: #64748b; font-size: 14px;">Tax</td>
                                                <td align="right" style="border-top: 1px solid #e2e8f0; padding-top: 12px; color: #0ea5e9; font-size: 18px; font-weight: bold;">
                                                    RM {{ number_format($invoice->tax_amount ?? 0, 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="border-top: 1px solid #e2e8f0; padding-top: 12px; color: #64748b; font-size: 14px;">Total</td>
                                                <td align="right" style="border-top: 1px solid #e2e8f0; padding-top: 12px; color: #0ea5e9; font-size: 18px; font-weight: bold;">
                                                    RM {{ number_format($invoice->price+$invoice->tax_amount ?? 0, 2) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 35px;">
                                <tr>
                                    <td align="center">
                                       <a href="{{ url('/invoice/view/' . $invoice->unique_id) }}">Click here to view your invoice</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #475569; font-size: 16px; line-height: 1.5; margin-bottom: 5px;">Thank you,</p>
                            <strong style="color: #0f172a; font-size: 16px;">{{ $supplier->registration_name ?? 'Waja Global Services' }}</strong>
                        </td>
                    </tr>
                </table>

                <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; margin-top: 20px;">
                    <tr>
                        <td align="center" style="padding: 0 20px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="color: #94a3b8; font-size: 12px; padding-bottom: 10px;">
                                        This is an automated message sent via the MySyncTax LHDN Integration platform. Please do not reply directly to this email.
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="border-top: 1px solid #e2e8f0; padding-top: 15px; color: #94a3b8; font-size: 12px;">
                                        &copy; {{ date('Y') }} MySyncTax. All rights reserved.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>