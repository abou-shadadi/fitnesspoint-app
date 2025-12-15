@extends('beautymail::templates.ark')

@section('content')

@include('beautymail::templates.ark.heading', [
'heading' => 'Account Deactivated',
'level' => 'h1'
])

@include('beautymail::templates.ark.contentStart')

<p>Hi {{ $user->first_name }},</p>

<p>We regret to inform you that your account with {{ config('app.name') }} has been deactivated.</p>

<p>If you believe this deactivation is in error or if you wish to reactivate your account, please contact our support team at support.fitnesspoint.rw.</p>

<p>Thank you for your understanding.</p>

<p>Best regards,<br>
The {{ config('app.name') }} Team</p>

@include('beautymail::templates.ark.contentEnd')
@stop
