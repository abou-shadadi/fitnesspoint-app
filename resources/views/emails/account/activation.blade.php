@extends('beautymail::templates.ark')

@section('content')

@include('beautymail::templates.ark.heading', [
'heading' => 'Account Activated!',
'level' => 'h1'
])

@include('beautymail::templates.ark.contentStart')

<p>Hi {{ $user->first_name }},</p>

<p>We're excited to inform you that your account with {{ config('app.name') }} has been successfully activated!</p>

<p>You can now log in and start exploring our platform. Click the button below to proceed:</p>

<br>

<table class="button success float-center">
    <tr>
        <td>
            <table>
                <tr>
                    <td>
                        <a href="{{ 'https://intellect.fitnesspoint.rw/login' }}" target="_blank">Log In</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<p>If you have any questions or need assistance, please don't hesitate to contact our support team at support.fitnesspoint.rw.</p>

<p>Thank you for choosing {{ config('app.name') }}. We're thrilled to have you onboard!</p>

<p>Best regards,<br>
The {{ config('app.name') }} Team</p>

@include('beautymail::templates.ark.contentEnd')
@stop
