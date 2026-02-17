<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Consolidation Completed</title>
    <style>
        /* Base Resets */
        body { margin: 0; padding: 0; background-color: #f4f6f8; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        table { border-collapse: collapse; width: 100%; }
        
        /* Container */
        .wrapper { padding: 40px 0; background-color: #f4f6f8; width: 100%; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #eeeeee; overflow: hidden; }
        .inner-padding { padding: 40px; }
        
        /* Header */
        .logo-box { background-color: #1a1c2d; color: #ffffff; width: 42px; height: 42px; border-radius: 6px; display: block; text-align: center; line-height: 46px; }
        .logo-text { font-size: 22px; font-weight: 700; color: #1a1c2d; padding-left: 12px; }
        .divider { border-bottom: 1px solid #f0f0f0; margin: 0 40px; }
        
        /* Content */
        .h1 { font-size: 24px; font-weight: 700; color: #1a1a1a; margin: 0 0 20px 0; }
        .text { font-size: 16px; color: #555555; line-height: 1.6; margin: 0 0 10px 0; }
        .sub-text { color: #666666; }
        
        /* Summary Box */
        .summary-box { background-color: #f9fafb; border: 1px solid #f1f3f5; border-radius: 12px; padding: 25px; margin: 30px 0; }
        .summary-icon-bg { background-color: #3e4455; display: inline-block; border-radius: 4px; width: 24px; height: 24px; text-align: center; vertical-align: middle; line-height: 28px; }
        .summary-title { font-size: 12px; font-weight: 700; color: #868e96; text-transform: uppercase; letter-spacing: 1px; padding-left: 10px; vertical-align: middle; }
        
        .row-item td { padding: 12px 0; border-bottom: 1px solid #e9ecef; }
        .row-last td { border-bottom: none; }
        
        .label { color: #868e96; font-size: 15px; font-weight: 500; }
        .value { color: #212529; font-size: 16px; font-weight: 600; text-align: right; }
        .total-amount { color: #1a1a1a; font-size: 22px; font-weight: 800; text-align: right; letter-spacing: -0.5px; }
        
        /* Button */
        .btn-table { margin: 35px 0 10px 0; }
        .btn { background-color: #2196f3; color: #ffffff; text-decoration: none; padding: 14px 35px; border-radius: 6px; font-weight: 600; font-size: 16px; display: inline-block; mso-padding-alt: 0; }
        
        /* Sign Off */
        .sign-off { font-size: 16px; color: #333; margin-top: 30px; line-height: 1.5; }
        
        /* Footer */
        .footer { background-color: #ffffff; padding: 30px 40px 40px 40px; border-top: 1px solid #f0f0f0; }
        .copyright { font-size: 13px; color: #adb5bd; }
        .footer-links { text-align: right; font-size: 13px; }
        .footer-links a { color: #868e96; text-decoration: none; margin-left: 15px; }
        .address { font-size: 13px; color: #adb5bd; margin-top: 20px; text-align: center; display: block; }

    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header Table -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td class="inner-padding" style="padding-bottom: 30px;">
                        <table cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="42">
                                    <span class="logo-box">
                                        <!-- SVG Logo -->
                                        <img src="https://cdn-icons-png.flaticon.com/512/9320/9320573.png" width="24" height="24" style="vertical-align: middle; display:inline-block;" alt="Logo">
                                    </span>
                                </td>
                                <td class="logo-text">
                                    MySyncTax
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div class="divider"></div>

            <!-- Content Area -->
            <div class="inner-padding">
                <div class="h1">Hello {{ $name }},</div>
                
                <p class="text sub-text">
                    Your auto-consolidation for <strong>{{ $date }}</strong> has been completed.<br>
                    The system has successfully processed your data and generated the necessary documentation.
                </p>

                <!-- Summary Box -->
                <div class="summary-box">
                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                        <!-- Summary Header -->
                        <tr>
                            <td colspan="2" style="padding-bottom: 15px;">
                                <table cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td width="24" class="summary-icon-bg">
                                            <!-- Dollar Icon White -->
                                            <span style="color: #ffffff; font-weight: bold; font-size: 14px;">$</span>
                                        </td>
                                        <td class="summary-title">
                                            CONSOLIDATION SUMMARY
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Row 1 -->
                        <tr class="row-item">
                            <td class="label">Total Invoices Generated</td>
                            <td class="value">{{ $count }}</td>
                        </tr>
                        
                        <!-- Row 2 -->
                        <tr class="row-item row-last">
                            <td class="label" style="padding-top: 15px;">Total Amount</td>
                            <td class="total-amount" style="padding-top: 15px;">RM {{ $amount }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Action Button -->
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="btn-table">
                    <tr>
                        <td align="left">
                            <a href="https://www.mysynctax.com/dashboard" class="btn">
                                View Detailed Report &rarr;
                            </a>
                        </td>
                    </tr>
                </table>

                <div class="sign-off">
                    Regards,<br>
                    <strong>MySyncTax System</strong>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="copyright">
                            &copy; {{ date('Y') }} MySyncTax. All rights reserved.
                        </td>
                        <td class="footer-links">
                            <a href="#">Privacy Policy</a>
                            <a href="#">Unsubscribe</a>
                            <a href="#">Contact Support</a>
                        </td>
                    </tr>
                </table>
                <div class="address">
                    123 Tax Avenue, Financial District, KL 50000, Malaysia
                </div>
            </div>

        </div>
    </div>
</body>
</html>
```