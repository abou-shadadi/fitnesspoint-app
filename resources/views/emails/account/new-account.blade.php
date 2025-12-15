@extends('beautymail::templates.ark')

@section('content')

@include('beautymail::templates.ark.heading', [
'heading' => 'New account creation - Verification and default password',
'level' => 'h1'
])

@include('beautymail::templates.ark.contentStart')

<p>Dear {{ $user->first_name }},</p>

<p>We are pleased to inform you that a new account has been created for you in our system. This account will grant
    you access to various functions and privileges within our organization's digital platform. To ensure
    security and compliance, we have implemented a verification process and assigned a default password for your initial
    login.</p>

<p><strong>Please follow these steps to complete your account setup:</strong></p>

<ol>
    <li><strong>Initial Login:</strong> Once your identity is verified, you can log in to your account using the
        following credentials:
        <ul>
            <li>Username: {{ $user->email }}</li>
            <li>Default Password: [{{ $default_password }} - Please change this immediately upon login]</li>
        </ul>
    </li>
    <li><strong>Password Update:</strong> For security reasons, we strongly recommend changing your default password
        upon your first login. Choose a strong and unique password that includes a combination of uppercase and
        lowercase letters, numbers, and special characters.</li>
    <li><strong>Account Access:</strong> Your account will provide you with access to various tools
        and resources. Please make sure to familiarize yourself with the platform and its features to effectively
        perform your duties.</li>
</ol>

<p>We are excited to have you join our administrative team and look forward to your valuable contributions. Thank you
    for your cooperation in completing the verification process promptly.</p>

<p>Best regards,<br>
    The {{ config('app.name') }} Team</p>

@include('beautymail::templates.ark.contentEnd')
@stop
