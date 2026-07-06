<x-mail::message>
# Password Reset Verification Code

You requested to reset the password for your account on **RaDiCe Workflow Management System**.

Please use the 6-digit verification code below to complete your password reset:

<x-mail::panel>
<div style="font-size: 28px; font-weight: bold; letter-spacing: 4px; text-align: center; color: #1e1b4b;">
{{ $otp }}
</div>
</x-mail::panel>

This verification code is valid for **15 minutes**. If you did not request a password reset, please ignore this email.

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
