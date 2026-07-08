<x-mail::message>
# Password Reset Request

You requested to reset the password for your account on **RaDiCe Workflow Management System**.

Please click the button below to reset your password. This link is valid for **15 minutes**.

<x-mail::button :url="$resetLink">
Reset Password
</x-mail::button>

If you cannot click the button, copy and paste the URL below into your web browser:

[{{ $resetLink }}]({{ $resetLink }})

This link is valid for **15 minutes**. If you did not request a password reset, please ignore this email.

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
