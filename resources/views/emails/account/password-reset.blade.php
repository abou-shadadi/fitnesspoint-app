
@extends('beautymail::templates.ark')

@section('content')

@include('beautymail::templates.ark.heading', [
'heading' => 'Password changed successfully',
'level' => 'h1'
])

@include('beautymail::templates.ark.contentStart')

<p>Hi {{ $user->first_name }},</p>

<p>Your password has been successfully reset.</p>

<p>If you did not request this change, please contact us immediately at <a href="mailto:support@FITNESS.com">support@FITNESS.com</a>.</p>

<p>Thank you,<br>
{{ config('app.name') }} Team</p>

@include('beautymail::templates.ark.contentEnd')
@stop
