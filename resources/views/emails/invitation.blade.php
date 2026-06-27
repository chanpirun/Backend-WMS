<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Project Team Invitation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .logo {
            font-size: 24px;
            font-weight: 800;
            color: #312e81;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            color: #1e1b4b;
            margin-top: 12px;
        }
        .content {
            line-height: 1.6;
            font-size: 15px;
        }
        .credentials-box {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }
        .credentials-box p {
            margin: 6px 0;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background-color: #312e81;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            font-weight: 600;
            border-radius: 8px;
            margin-top: 16px;
            text-align: center;
        }
        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">RaDiCe WMS</div>
            <div class="title">You've been invited!</div>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p><strong>{{ $inviter->name }}</strong> has invited you to join their project team on <strong>RaDiCe WMS</strong>.</p>
            <p>You have been added to their existing project submissions so you can collaborate and access them in the Group Hub.</p>
            
            @if ($tempPassword)
                <p>Since you don't have an account yet, a new account has been created for you with the following login details:</p>
                <div class="credentials-box">
                    <p><strong>Email:</strong> {{ $invitee->email }}</p>
                    <p><strong>Temporary Password:</strong> {{ $tempPassword }}</p>
                </div>
                <p>Please log in and change your password as soon as possible.</p>
            @else
                <p>You can log in to your existing account to view the projects immediately.</p>
            @endif

            <div style="text-align: center;">
                <a href="{{ config('app.url') ?? 'http://localhost:3000' }}/login" class="btn">Log In to Group Hub</a>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated email from RaDiCe WMS. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
