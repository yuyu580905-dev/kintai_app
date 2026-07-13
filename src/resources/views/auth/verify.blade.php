@extends('layouts.app')

@php
    $hideHeaderNav = true;
@endphp

@section('title', 'メール認証')

@section('main-class', 'main--auth')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')

    <div class="auth">

        <div class="verify">

            <h1 class="verify__message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </h1>

            <a href="https://mailtrap.io/home" target="_blank" rel="noopener noreferrer" class="verify__link-button">
                認証はこちらから
            </a>

            <form action="/email/verification-notification" method="post">
                @csrf

                <button type="submit" class="verify__resend-button">
                    認証メールを再送する
                </button>

            </form>

        </div>

    </div>

@endsection