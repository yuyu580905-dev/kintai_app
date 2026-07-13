@extends('layouts.app')

@php
    $hideHeaderNav = true;
@endphp

@section('title', '会員登録')

@section('main-class', 'main--auth')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')

    <div class="auth">

        <div class="auth__container">

            <h1 class="auth__title">
                会員登録
            </h1>

            <form action="/register" method="post" class="auth-form" novalidate>
                @csrf

                <div class="auth-form__group">

                    <label class="auth-form__label" for="name">
                        名前
                    </label>

                    <input class="auth-form__input" type="text" name="name" id="name" value="{{ old('name') }}">

                    @error('name')
                        <p class="auth-form__error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

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

                <div class="auth-form__group">

                    <label class="auth-form__label" for="password_confirmation">
                        パスワード確認
                    </label>

                    <input class="auth-form__input" type="password" name="password_confirmation" id="password_confirmation">

                </div>

                <button type="submit" class="auth-form__button">
                    登録する
                </button>

            </form>

            <a href="/login" class="auth__link">
                ログインはこちら
            </a>

        </div>

    </div>

@endsection