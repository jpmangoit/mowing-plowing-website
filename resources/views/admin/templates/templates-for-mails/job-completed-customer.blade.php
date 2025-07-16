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
        .button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hi {{ $customerName }},</h2>
        
        <p>Great news! Your {{ $serviceType }} job is done, and your property is looking top-tier! 💪 We appreciate you choosing Mowing and Plowing to keep things in check.</p>
        
        <p>If you're happy with our service, we'd love for you to leave us a 5-star review on Google! Your feedback helps us grow and keeps us delivering top-notch work.</p>
        
        <p>👉 <a href="https://g.co/kgs/YZ77wWJ">Leave a review here</a></p>
        
        <p>If for any reason we didn't earn 5 stars, please reach out to us directly at 440-517-6763 or hello@mowingandplowing.com so we can make things right. Your satisfaction is our priority!</p>
        
        <p>Thanks again for trusting Mowing and Plowing! Let us know if you need anything else.</p>
        
        <div class="footer">
            <p>Best,<br>
            Mowing and Plowing Team 🚜💨<br>
            📍 MowingandPlowing.com<br>
            📞 440-517-6763</p>
        </div>
    </div>
</body>
</html>
