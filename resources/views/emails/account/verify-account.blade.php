@extends('beautymail::templates.ark')

@section('content')

@include('beautymail::templates.ark.heading', [
'heading' => 'Verify Your Account',
'level' => 'h1'
])

@include('beautymail::templates.ark.contentStart')

<p>Hi {{ $user->first_name }},</p>

<p>We are thrilled to have you join us at {{ config('app.name') }}!</p>

<p>To ensure the security of your account and provide you with uninterrupted access to our services, please take a moment to verify your email address by clicking the button below:</p>

<br>

<table class="button success float-center">
    <tr>
        <td>
            <table>
                <tr>
                    <td>
                        <a href="{{ 'https://intellect.fitnesspoint.rw/verify-account?token=' . $token }}" target="_blank">Verify Account</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<p>If the button above doesn't work, you can copy and paste the following URL into your browser:</p>
<p>{{ 'https://intellect.fitnesspoint.rw/verify-account?token=' . $token }}</p>

<p>By verifying your email, you'll gain access to a world of exciting features and exclusive offers!</p>

<p>If you have any questions or need assistance, don't hesitate to reach out to our support team at support@FITNESS.com.</p>

<p>Thank you for choosing {{ config('app.name') }}! We're thrilled to have you onboard.</p>

<p>Best regards,<br>
The {{ config('app.name') }} Team</p>

@include('beautymail::templates.ark.contentEnd')
@stop
