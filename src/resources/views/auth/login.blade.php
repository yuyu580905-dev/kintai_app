@extends('layouts.app')

@php
    $hideHeaderNav = true;
@endphp

@section('title', 'ログイン')

@section('main-class', 'main--auth')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')

    <div class="auth">

        <div class="auth__container">

            <h1 class="auth__title">
                ログイン
            </h1>

            <form action="/login" method="post" class="auth-form" novalidate>
                @csrf

                <div class="auth-form__group">

                    <label class="auth-form__label" for="email">
                        メールアドレス
                    </label>

                    <input class="auth-form__input" type="email" name="email" id="email" value="{{ old('email') }}">

                    @error('email')
                        <p class="auth-form__error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <div class="auth-form__group">

                    <label class="auth-form__label" for="password">
                        パスワード
                    </label>

                    <input class="auth-form__input" type="password" name="password" id="password">

                    @error('password')
                        <p class="auth-form__error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <button type="submit" class="auth-form__button">
                    ログインする
                </button>

            </form>

            <a href="/register" class="auth__link">
                会員登録はこちら
            </a>

        </div>

    </div>

@endsection