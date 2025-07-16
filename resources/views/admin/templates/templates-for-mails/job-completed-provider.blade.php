<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Job Completed</title>
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
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hi {{ $providerName }},</h2>
        
        <p>Great work out there!</p>
        
        <p>We've marked your recent {{ $serviceType }} job as complete in our system. There's nothing else you need to do on your end — you're all set.</p>
        
        <p>Your payment is now being processed and will be automatically sent to the account we have on file. You'll receive a notification once it's been successfully deposited.</p>
        
        <p>Thanks for keeping things running smoothly and delivering quality service!</p>
        
        <p>If you have any questions or need support, feel free to reach out.</p>
        
        <div class="footer">
            <p>Best,<br>
            Mowing and Plowing Team<br>
            hello@mowingandplowing.com<br>
            www.mowingandplowing.com</p>
        </div>
    </div>
</body>
</html>
