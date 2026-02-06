<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Auto-Consolidation Completed</title><style>body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; }.container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #eee; }.header { margin-bottom: 40px; display: flex; align-items: center; gap: 12px; }.logo-box { background-color: #1a1c2d; color: #ffffff; width: 42px; height: 42px; border-radius: 6px; display: flex; align-items: center; justify-content: center; }.logo-text { font-size: 22px; font-weight: 700; color: #1a1c2d; margin-left: 10px; }.divider { border-bottom: 1px solid #efefef; margin-bottom: 40px; }.content { color: #333333; line-height: 1.6; font-size: 16px; }.h1 { font-size: 24px; font-weight: 700; color: #1a1a1a; margin-bottom: 25px; }.sub-text { color: #666; margin-bottom: 30px; }    /* Summary Box matching wireframe */
    .summary-box { background-color: #f9fafb; border: 1px solid #f1f3f5; border-radius: 12px; padding: 25px; margin: 30px 0; }
    .summary-header { display: flex; align-items: center; margin-bottom: 20px; }
    .icon-summary { background-color: #3e4455; color: white; border-radius: 4px; padding: 5px; font-size: 14px; margin-right: 12px; }
    .summary-title { font-size: 13px; font-weight: 700; color: #868e96; text-transform: uppercase; letter-spacing: 0.5px; }
    .summary-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e9ecef; align-items: center; }
    .summary-row:last-child { border-bottom: none; }
    .label { color: #868e96; font-size: 15px; }
    .value { color: #212529; font-weight: 500; font-size: 16px; }
    .total-amount { font-size: 22px; font-weight: 800; color: #1a1a1a; }
    
    /* Button matching blue style */
    .btn-container { margin: 30px 0; }
    .btn { display: inline-flex; align-items: center; background-color: #2196f3; color: #ffffff; text-decoration: none; padding: 14px 35px; border-radius: 8px; font-weight: 600; font-size: 16px; transition: background 0.2s; }
    .btn-arrow { margin-left: 10px; font-size: 18px; }
    
    .sign-off { margin-top: 40px; color: #333; }
    .sign-off strong { color: #1a1a1a; display: block; margin-top: 5px; }

    .footer { margin-top: 60px; padding-top: 30px; border-top: 1px solid #efefef; text-align: left; }
    .footer-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .copyright { font-size: 13px; color: #adb5bd; }
    .footer-links a { color: #868e96; text-decoration: none; font-size: 13px; margin-left: 20px; }
    .address { font-size: 13px; color: #adb5bd; text-align: center; margin-top: 20px; width: 100%; }
</style>
</head><body><div style="padding: 40px 0;"><div class="container"><!-- Header --><div class="header"><div class="logo-box"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><line x1="9" y1="22" x2="9" y2="22"></line><line x1="8" y1="6" x2="8" y2="6"></line><line x1="12" y1="6" x2="12" y2="6"></line><line x1="16" y1="6" x2="16" y2="6"></line><line x1="8" y1="10" x2="8" y2="10"></line><line x1="12" y1="10" x2="12" y2="10"></line><line x1="16" y1="10" x2="16" y2="10"></line><line x1="8" y1="14" x2="8" y2="14"></line><line x1="12" y1="14" x2="12" y2="14"></line><line x1="16" y1="14" x2="16" y2="14"></line><line x1="8" y1="18" x2="8" y2="18"></line><line x1="12" y1="18" x2="12" y2="18"></line><line x1="16" y1="18" x2="16" y2="18"></line></svg></div><span class="logo-text">MySyncTax</span></div>        <div class="divider"></div>

        <!-- Content -->
        <div class="content">
            <div class="h1">Hello {{ $name }},</div>
            
            <p class="sub-text">
                Your auto-consolidation for <strong>{{ $date }}</strong> has been completed.<br>
                The system has successfully processed your data and generated the necessary documentation.
            </p>

            <!-- Summary Box -->
            <div class="summary-box">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td colspan="2" style="padding-bottom: 15px;">
                            <div style="display: flex; align-items: center;">
                                <span style="background: #3e4455; color: white; border-radius: 4px; padding: 4px; margin-right: 10px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                </span>
                                <span class="summary-title">CONSOLIDATION SUMMARY</span>
                            </div>
                        </td>
                    </tr>
                    <tr class="summary-row">
                        <td class="label" style="padding: 12px 0; border-bottom: 1px solid #e9ecef;">Total Invoices Generated</td>
                        <td class="value" style="padding: 12px 0; text-align: right; border-bottom: 1px solid #e9ecef;">{{ $count }}</td>
                    </tr>
                    <tr class="summary-row">
                        <td class="label" style="padding: 15px 0 0 0;">Total Amount</td>
                        <td class="total-amount" style="padding: 15px 0 0 0; text-align: right;">RM {{ $amount }}</td>
                    </tr>
                </table>
            </div>

            <div class="btn-container">
                <a href="https://www.mysynctax.com/dashboard" class="btn">
                    View Detailed Report <span class="btn-arrow">&rarr;</span>
                </a>
            </div>
            
            <div class="sign-off">
                Regards,
                <strong>MySyncTax System</strong>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="copyright">&copy; {{ date('Y') }} MySyncTax. All rights reserved.</td>
                    <td align="right" class="footer-links">
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
</body></html>