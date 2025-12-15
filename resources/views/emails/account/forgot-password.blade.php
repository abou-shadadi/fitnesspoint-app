@extends('beautymail::templates.ark')

@section('content')

@include('beautymail::templates.ark.heading', [
'heading' => 'Reset Password',
'level' => 'h1'
])

@include('beautymail::templates.ark.contentStart')

<p>Hi {{ $user->first_name }},</p>

<p>We received a request to reset your password. If you did not make this request, you can safely ignore this email.</p>

<p>To reset your password, click the link below:</p>


<br>

<table class="button success float-center">
    <tr>
        <td>
            <table>
                <tr>
                    <td>
                        <a href="{{ 'https://intellect.fitnesspoint.rw/reset-password?token=' . $token }}" target="_blank
                        ">Reset Password</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<p>If you're having trouble clicking the link, you can copy and paste the following URL into your browser's address bar:</p>
<p>{{ 'https://intellect.fitnesspoint.rw/reset-password?token=' . $token }}</p>

<p>This link will expire in 60 minutes for security reasons.</p>

<p>Thank you!<br>
{{ config('app.name') }} Team</p>

@include('beautymail::templates.ark.contentEnd')
@stop
