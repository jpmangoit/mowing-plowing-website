<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .content {
            padding: 20px 0;
        }
        .otp-container {
            text-align: center;
            margin: 30px 0;
        }
        .otp {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 5px;
            padding: 10px 20px;
            background-color: #f5f5f5;
            border-radius: 5px;
            display: inline-block;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Password Reset Request</h2>
        </div>
        
        <div class="content">
            <p>Hello {{ $name }},</p>
            
            <p>We received a request to reset your password for your Mowing and Plowing account. Click the button below to reset your password:</p>
            
            <div class="reset-button">
                <a href="{{ $resetLink }}" class="button">Reset Your Password</a>
            </div>
            
            <p>If you did not request a password reset, please ignore this email or contact our support team if you have concerns.</p>
            
            <p>Thank you,<br>The Mowing and Plowing Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email, please do not reply.</p>
            <p>&copy; {{ date('Y') }} Mowing and Plowing. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
