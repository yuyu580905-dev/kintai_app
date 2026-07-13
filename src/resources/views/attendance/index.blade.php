@extends('layouts.app')

@section('title', '勤怠登録')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('header')
    <x-header-nav type="finished" />
@endsection

@section('main-class', 'main--app')

@section('content')

    <div class="attendance">

        <div class="attendance__content">

            {{-- 状態表示 --}}
            <div class="attendance__status">
                {{ $status }}
            </div>

            {{-- 日付 --}}
            <p class="attendance__date">
                {{ $now->isoFormat('YYYY年M月D日(ddd)') }}
            </p>

            {{-- 時刻 --}}
            <p class="attendance__time">
                {{ $now->format('H:i') }}
            </p>

            {{-- ボタンエリア --}}
            @if($status === '勤務外')
                <div class="attendance__actions">
                    <form action="{{ route('attendance.clock-in') }}" method="post">
                        @csrf

                        <button type="submit" class="attendance__button">
                            出勤
                        </button>
                    </form>
                </div>

            @elseif($status === '出勤中')
                <div class="attendance__actions attendance__actions--multiple">
                    <form action="{{ route('attendance.clock-out') }}" method="post">
                        @csrf

                        <button type="submit" class="attendance__button">
                            退勤
                        </button>
                    </form>

                    <form action="{{ route('attendance.break-start') }}" method="post">
                        @csrf

                        <button type="submit" class="attendance__button attendance__button--white">
                            休憩入
                        </button>
                    </form>
                </div>

            @elseif($status === '休憩中')
                <div class="attendance__actions">
                    <form action="{{ route('attendance.break-end') }}" method="post">
                        @csrf

                        <button type="submit" class="attendance__button attendance__button--white">
                            休憩戻
                        </button>
                    </form>
                </div>

            @elseif($status === '退勤済')
                <p class="attendance__message">
                    お疲れ様でした。
                </p>
            @endif

        </div>

    </div>

@endsection