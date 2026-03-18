<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Enumerator Registration Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #78350f;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .content {
            background: #ffffff;
            padding: 40px;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 10px 10px;
        }
        .code-box {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .code {
            font-size: 48px;
            font-weight: bold;
            color: #78350f;
            letter-spacing: 5px;
            margin: 10px 0;
        }
        .info-box {
            background: #f9fafb;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .whatsapp-btn {
            display: inline-block;
            background: #25d366;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .details {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .details h3 {
            margin-top: 0;
            color: #78350f;
        }
        .details p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌟 Congratulations Enumerator! 🌟</h1>
        <p>Your registration has been successfully completed</p>
    </div>

    <div class="content">
        <p>Dear <strong>{{ $full_name }}</strong>,</p>
        
        <p>Thank you for registering as an enumerator with Accord Party. We are excited to have you join our team for the upcoming enumeration exercise.</p>

        <div class="code-box">
            <h2 style="margin: 0 0 10px 0; color: #78350f;">Your Unique Enumerator Code</h2>
            <div class="code">{{ $code }}</div>
            <p style="margin: 10px 0 0 0; color: #78350f; font-weight: bold;">Please keep this code safe and confidential</p>
        </div>

        <div class="details">
            <h3>Registration Details:</h3>
            <p><strong>Full Name:</strong> {{ $full_name }}</p>
            <p><strong>Email:</strong> {{ $email }}</p>
            <p><strong>WhatsApp:</strong> {{ $whatsapp }}</p>
            <p><strong>State:</strong> {{ $state }}</p>
            <p><strong>LGA:</strong> {{ $lga }}</p>
            <p><strong>Ward:</strong> {{ $ward }}</p>
            <p><strong>Polling Unit:</strong> {{ $polling_unit }}</p>
            <p><strong>Registration Date:</strong> {{ $registered_at->format('d M Y, h:i A') }}</p>
        </div>

        <div class="info-box">
            <h3 style="margin-top: 0; color: #78350f;">📋 Important Instructions:</h3>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Your enumerator code (<strong>{{ $code }}</strong>) is your unique identifier</li>
                <li>You will need this code for all enumeration activities</li>
                <li>Keep this code confidential and do not share it with unauthorized persons</li>
                <li>Join our WhatsApp group for important updates and coordination</li>
                <li>Report to your assigned polling unit on the scheduled enumeration date</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="https://chat.whatsapp.com/F2f6AeKVSDS2zRWhXcdAKH?mode=gi_t" class="whatsapp-btn">
                📱 Join WhatsApp Group
            </a>
        </div>

        <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

        <p>We look forward to working with you in this important democratic process!</p>

        <div class="footer">
            <p><strong>Accord Party Enumeration Team</strong></p>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>© {{ date('Y') }} Accord Party. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
