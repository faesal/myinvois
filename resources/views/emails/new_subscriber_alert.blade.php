<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* General Resets */
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            background-color: #f3f4f6; 
            margin: 0; 
            padding: 0; 
            -webkit-font-smoothing: antialiased;
        }

        /* Container */
        .email-container { 
            max-width: 800px; 
            margin: 30px auto; 
            background-color: #ffffff; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        
        /* Header */
        .header { 
            background-color: #111827; 
            color: #ffffff; 
            padding: 25px 30px; 
        }
        .header h2 { 
            margin: 0; 
            font-size: 20px; 
            font-weight: 700; 
            letter-spacing: 0.5px;
        }
        
        /* Summary Section */
        .summary { 
            padding: 30px; 
            border-bottom: 1px solid #e5e7eb; 
            background-color: #f9fafb; 
        }
        .summary h1 { 
            margin: 0 0 10px 0; 
            font-size: 24px; 
            color: #1f2937; 
            font-weight: 700;
        }
        .summary p { 
            margin: 0; 
            color: #6b7280; 
            font-size: 15px; 
            line-height: 1.5;
        }
        .stats { 
            margin-top: 15px; 
            font-size: 14px; 
            font-weight: 600; 
            color: #374151; 
        }
        .stats span { 
            margin-right: 20px; 
            display: inline-block;
        }

        /* Content Area */
        .user-list { 
            padding: 30px; 
            background-color: #ffffff;
        }
        .user-card { 
            border: 1px solid #e5e7eb; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 15px; 
            display: table; 
            width: 100%; 
            background-color: #ffffff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }

        /* Table Cell Layout */
        .user-avatar {
            display: table-cell;
            vertical-align: middle;
            width: 50px;
            padding-right: 15px;
        }
        .avatar-circle {
            background-color: #e5e7eb;
            color: #6b7280;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            font-weight: bold;
            font-size: 16px;
        }

        .user-info { 
            display: table-cell; 
            vertical-align: middle; 
        }
        .user-name { 
            font-weight: 700; 
            font-size: 16px; 
            color: #111827; 
            margin: 0; 
        }
        .user-email { 
            color: #6b7280; 
            font-size: 14px; 
            margin: 4px 0 0 0; 
        }
        
        .user-meta { 
            display: table-cell; 
            vertical-align: middle; 
            text-align: right; 
            padding-right: 30px; 
            min-width: 150px;
        }
        .expire-label { 
            font-size: 12px; 
            color: #6b7280; 
            margin-bottom: 4px;
        }
        .developer-badge { 
            font-size: 13px; 
            color: #4b5563; 
            font-weight: 600; 
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .user-action { 
            display: table-cell; 
            vertical-align: middle; 
            width: 120px; 
            text-align: right; 
        }
        
        /* Activate Button */
        .btn-activate { 
            background-color: #25d4eb; 
            color: #000000; 
            text-decoration: none; 
            padding: 10px 20px; 
            border-radius: 6px; 
            font-size: 14px; 
            font-weight: 600; 
            display: inline-block;
            white-space: nowrap;
        }
        .btn-activate:hover { 
            background-color: #1fb9cd; 
        }

        .footer { 
            background-color: #111827; 
            color: #9ca3af; 
            text-align: center; 
            padding: 25px; 
            font-size: 13px; 
            line-height: 1.6;
        }
        
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; margin: 0 !important; border-radius: 0 !important; }
            .user-card { display: block; text-align: center; }
            .user-avatar, .user-info, .user-meta, .user-action { display: block; width: 100%; text-align: center; padding: 5px 0; }
            .user-meta { padding-bottom: 15px; }
            .user-action { width: 100%; }
            .btn-activate { display: block; width: 100%; box-sizing: border-box; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h2>MySynctax Notification</h2>
        </div>

        <div class="summary">
            <h1>New LHDN Account Alert</h1>
            <p>A developer has registered a new LHDN Account (Supplier). This account requires manual review and activation before it can access services.</p>
            <div class="stats">
                <span>👤 Added By: {{ $developer->name }}</span>
                <span>📅 Date: {{ now()->format('M d, Y H:i') }}</span>
            </div>
        </div>

        <div class="user-list">
            <div class="user-card">
                <div class="user-avatar">
                    <div class="avatar-circle">
                        {{ strtoupper(substr($subscriber->registration_name, 0, 1)) }}
                    </div>
                </div>

                <div class="user-info">
                    <p class="user-name">{{ $subscriber->registration_name }}</p>
                    <p class="user-email">{{ $subscriber->email }}</p>
                </div>

                <div class="user-meta">
                    <div class="expire-label">TIN Number</div>
                    <div class="developer-badge">{{ $subscriber->tin_no }}</div>
                </div>

                <div class="user-action">
                    <a href="{{ route('admin.subscribers.activation_form', $subscriber->id_customer) }}" class="btn-activate">
                        &#10003; Review & Activate
                    </a>
                </div>
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} MySynctax. All rights reserved.<br>
            This is an automated administrative alert.
        </div>
    </div>
</body>
</html>