<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Failed Invoice Alert</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    <table width="100%" bgcolor="#f4f5f7" cellpadding="0" cellspacing="0" border="0" style="padding: 40px 20px;">
        <tr>
            <td align="center">
                
                <table width="600" bgcolor="#ffffff" cellpadding="0" cellspacing="0" border="0" style="border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; text-align: left; max-width: 600px; width: 100%;">
                    
                    <tr>
                        <td style="padding: 30px; border-bottom: 2px solid #f0f2f5;">
                            <table cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="36" height="36" style="background-color: #1e293b; border-radius: 6px; text-align: center; vertical-align: middle;">
                                        <img src="https://cdn-icons-png.flaticon.com/512/2830/2830305.png" width="20" height="20" style="display: inline-block; filter: brightness(0) invert(1); margin-top: 2px;" alt="Logo">
                                    </td>
                                    <td style="padding-left: 15px;">
                                        <strong style="font-size: 22px; color: #1e293b; letter-spacing: -0.5px;">MySyncTax</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 40px 30px;">
                            
                            <h1 style="font-size: 24px; color: #1e293b; margin-top: 0; margin-bottom: 20px;">Hello {{ $supplier_name ?? 'Admin' }},</h1>
                            
                            <p style="color: #475569; font-size: 16px; line-height: 1.6; margin-bottom: 35px;">
                                URGENT: The system has failed to submit <strong>{{ $total_failed }} invoice(s)</strong> to LHDN after reaching the maximum number of automated retries. Manual intervention is required to fix the errors below and resubmit.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="24" valign="top">
                                                    <img src="https://cdn-icons-png.flaticon.com/512/6897/6897018.png" width="24" height="24" alt="Alert" style="display:block; opacity: 0.6;">
                                                </td>
                                                <td style="padding-left: 15px;">
                                                    <div style="font-size: 12px; font-weight: bold; color: #64748b; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 20px;">Failure Summary ({{ $total_failed }} Items)</div>

                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="table-layout: fixed;">
                                                        <tr>
                                                            <td style="padding: 0 0 10px 0; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 13px; font-weight: bold; width: 35%;">Invoice Number</td>
                                                            <td align="right" style="padding: 0 0 10px 0; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 13px; font-weight: bold; width: 65%;">Error Reason</td>
                                                        </tr>
                                                        
                                                        {{-- Loop through the consolidated invoices --}}
                                                        @foreach($failed_invoices as $inv)
                                                        <tr>
                                                            <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; color: #1e293b; font-size: 14px; font-weight: bold; vertical-align: top;">
                                                                {{ $inv->invoice_no }}
                                                            </td>
                                                            <td align="right" style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; color: #ef4444; font-size: 13px; line-height: 1.4; word-wrap: break-word; vertical-align: top;">
                                                                {{ $inv->error_message ?? 'System Block / LHDN Timeout' }}
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                        
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #475569; font-size: 16px; line-height: 1.5; margin-top: 45px; margin-bottom: 5px;">Regards,</p>
                            <strong style="color: #1e293b; font-size: 16px;">MySyncTax System</strong>
                            
                        </td>
                    </tr>
                </table>

                <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; margin-top: 20px;">
                    <tr>
                        <td align="center" style="padding: 0 20px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="left" style="color: #94a3b8; font-size: 12px; padding-bottom: 15px;">
                                        &copy; {{ date('Y') }} MySyncTax. All rights reserved.
                                    </td>
                                    <td align="right" style="padding-bottom: 15px;">
                                        <a href="#" style="color: #94a3b8; font-size: 12px; text-decoration: none; margin-left: 15px;">Contact Support</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center" style="border-top: 1px solid #e2e8f0; padding-top: 15px; color: #94a3b8; font-size: 12px;">
                                        123 Tax Avenue, Financial District, KL 50000, Malaysia
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