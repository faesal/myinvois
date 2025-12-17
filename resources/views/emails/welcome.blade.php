<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to MySyncTax Developer Network</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            background: #f2f4f7;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }
        .email-container {
            max-width: 650px;
            margin: auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(90deg, #7c3aed, #4f46e5);
            padding: 28px;
            text-align: center;
            color: white;
        }
        .header h2 {
            margin: 0;
            font-size: 26px;    /* increased */
            font-weight: 700;
        }

        .section { padding: 30px; }

        .welcome-box {
            background: #f8f9ff;
            border-radius: 14px;
            padding: 35px;
            text-align: center;
            margin-bottom: 25px;
        }
        .welcome-box h3 {
            font-size: 28px;   /* bigger font */
            font-weight: 700;
            margin-bottom: 10px;
        }
        .welcome-box p {
            font-size: 17px;   /* bigger font */
        }

        .details-box {
            background: #fbfcfe;
            border: 1px solid #e0e7ff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 24px;
            font-size: 16px;    /* increased */
        }

        .details-box h4 {
            font-size: 20px;   /* increased */
            margin-bottom: 18px;
        }

        .details-box table {
            width: 100%;
            font-size: 16px;   /* increased */
        }

        .details-box td {
            padding: 10px 0;
        }

        /* CTA buttons */
        .cta-row {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-primary {
            flex: 1;
            background: linear-gradient(90deg, #4f46e5, #7c3aed);
            padding: 14px;
            border-radius: 10px;
            color: #ffffff !important;   /* FORCE WHITE TEXT */
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
        }


        .btn-secondary {
            flex: 1;
            background: #ffffff;
            padding: 14px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            text-align: center;
            font-size: 16px;             /* increased */
            font-weight: 600;
            color: #374151;
            text-decoration: none;
        }

        .support-btn {
            flex: 1;
            background: #ecfdf5;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
            color: #065f46;
            font-size: 16px;             /* increased */
            font-weight: 600;
            text-decoration: none;
            border: 2px solid #bbf7d0;
        }

        .footer {
            text-align: center;
            font-size: 13px;  /* slightly increased */
            padding: 22px 0 10px;
            color: #6b7280;
        }
    </style>
</head>

<body>

<div class="email-container">

    <div class="header">
        <h2>Welcome to MySyncTax Developer Network</h2>
    </div>

    <div class="section">

        <div class="welcome-box">
            <h3>Welcome, {{ $name }}!</h3>
            <p>Your developer account has been successfully created.</p>
        </div>

        <div class="details-box">
            <h4>Your Account Details</h4>

            <table>
                <tr>
                    <td><strong>Name:</strong></td>
                    <td>{{ $name }}</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td><a href="mailto:{{ $email }}">{{ $email }}</a></td>
                </tr>
                <tr>
                    <td><strong>Registration Date:</strong></td>
                    <td>{{ now()->format('M d, Y h:i A') }}</td>
                </tr>
                <tr>
                    <td><strong>Account Type:</strong></td>
                    <td><span style="background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:6px;font-size:13px;">Developer</span></td>
                </tr>
            </table>
        </div>

        <div class="cta-row">
            <a href="{{ url('/login') }}" class="btn-primary" style="margin-right:12px;">Sign In to Your Account</a>
            <a href="#" class="btn-secondary">Developer API</a>
        </div>


        <div class="cta-row">
            <a href="#" class="support-btn">Get Support</a>
        </div>

    </div>

    <div class="footer">
        Need help? Contact our support team<br>
        © {{ date('Y') }} MySyncTax. All rights reserved.
    </div>

</div>

</body>
</html>
