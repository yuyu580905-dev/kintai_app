@extends('layouts.admin')

@php
    $hideHeaderNav = true;
@endphp

@section('title', '管理者ログイン')

@section('main-class', 'main--auth')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/login.css') }}">
@endsection

@section('content')

    <div class="admin-login">

        <div class="admin-login__container">

            <h1 class="admin-login__title">
                管理者ログイン
            </h1>

            <form action="/admin/login" method="post" class="admin-login__form" novalidate>
                @csrf

                <div class="admin-login__group">

                    <label for="email" class="admin-login__label">
                        メールアドレス
                    </label>

                    <input id="email" type="email" name="email" class="admin-login__input" value="{{ old('email') }}">

                    @error('email')
                        <p class="admin-login__error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <div class="admin-login__group">

                    <label for="password" class="admin-login__label">
                        パスワード
                    </label>

                    <input id="password" type="password" name="password" class="admin-login__input">

                    @error('password')
                        <p class="admin-login__error">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

                <button type="submit" class="admin-login__button">
                    管理者ログインする
                </button>

            </form>

        </div>

    </div>

@endsection