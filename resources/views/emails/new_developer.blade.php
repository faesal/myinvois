<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Developer Registration Notification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }
        .email-container {
            max-width: 650px;
            margin: auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
        }
        .header {
            background: #059669;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        .section {
            padding: 26px;
        }
        .alert-box {
            background: #ecfdf5;
            border-left: 5px solid #10b981;
            padding: 18px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .user-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }
        .user-box table {
            width: 100%;
            font-size: 15px;
        }
        .user-box td {
            padding: 7px 0;
        }
        .badge {
            background: #ddd6fe;
            color: #5b21b6;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            margin: 22px 0 10px;
            color: #6b7280;
        }
    </style>
</head>

<body>

<div class="email-container">

    <div class="header">
        <h2>New Developer Registration Notification</h2>
    </div>

    <div class="section">

        <div class="alert-box">
            <strong>New User Registration!</strong><br>
            A new developer has just joined the MySyncTax platform.
        </div>

        <!-- User Details -->
        <div class="user-box">
            <h4 style="margin-bottom: 15px;">User Information</h4>

            <table>
                <tr>
                    <td><strong>Full Name:</strong></td>
                    <td>{{ $name }}</td>
                </tr>
                <tr>
                    <td><strong>Email Address:</strong></td>
                    <td>{{ $email }}</td>
                </tr>
                <tr>
                    <td><strong>Registration Time:</strong></td>
                    <td>{{ now()->format('M d, Y - h:i A') }}</td>
                </tr>
                <tr>
                    <td><strong>Account Type:</strong></td>
                    <td><span class="badge">Developer</span></td>
                </tr>
            </table>
        </div>

    </div>

    <div class="footer">
        © {{ date('Y') }} MySyncTax. All rights reserved.
    </div>

</div>

</body>
</html>
