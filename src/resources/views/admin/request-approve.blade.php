@extends('layouts.admin')

@section('title', '修正申請承認')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/request-approve.css') }}">
@endsection

@section('main-class', 'main--app')

@section('content')

    <div class="request-detail">

        <div class="request-detail__inner">

            <h1 class="request-detail__title">
                勤怠詳細
            </h1>

            <form action="{{ route('admin.requests.approve', $attendanceRequest) }}" method="POST"
                class="request-detail__form">

                @csrf
                @method('PATCH')

                <div class="request-detail-card">

                    {{-- 名前 --}}
                    <div class="request-detail-card__row">

                        <div class="request-detail-card__label">
                            名前
                        </div>

                        <div class="request-detail-card__content">
                            <span class="request-detail-card__text">
                                {{ $attendanceRequest->user->name }}
                            </span>
                        </div>

                    </div>

                    {{-- 日付 --}}
                    <div class="request-detail-card__row">

                        <div class="request-detail-card__label">
                            日付
                        </div>

                        <div class="request-detail-card__content request-detail-card__content--date">

                            <span class="request-detail-card__text">
                                {{ optional($attendanceRequest->attendance->work_date)->format('Y年') }}
                            </span>

                            <span class="request-detail-card__text">
                                {{ optional($attendanceRequest->attendance->work_date)->format('n月j日') }}
                            </span>

                        </div>

                    </div>

                    {{-- 出勤退勤 --}}
                    <div class="request-detail-card__row">

                        <div class="request-detail-card__label">
                            出勤・退勤
                        </div>

                        <div class="request-detail-card__content request-detail-card__content--time">

                            <span class="request-detail-card__text">
                                {{ optional($attendanceRequest->requested_clock_in)->format('H:i') }}
                            </span>

                            <span class="request-detail-card__separator">
                                〜
                            </span>

                            <span class="request-detail-card__text">
                                {{ optional($attendanceRequest->requested_clock_out)->format('H:i') }}
                            </span>

                        </div>

                    </div>

                    {{-- 休憩 --}}
                    @foreach ($attendanceRequest->breaks as $index => $break)

                        <div class="request-detail-card__row">

                            <div class="request-detail-card__label">
                                {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                            </div>

                            <div class="request-detail-card__content request-detail-card__content--time">

                                <span class="request-detail-card__text">
                                    {{ optional($break->break_start)->format('H:i') }}
                                </span>

                                <span class="request-detail-card__separator">
                                    〜
                                </span>

                                <span class="request-detail-card__text">
                                    {{ optional($break->break_end)->format('H:i') }}
                                </span>

                            </div>

                        </div>

                    @endforeach


                    {{-- 備考 --}}
                    <div class="request-detail-card__row">

                        <div class="request-detail-card__label">
                            備考
                        </div>

                        <div class="request-detail-card__content">

                            <span class="request-detail-card__text">
                                {{ $attendanceRequest->reason }}
                            </span>

                        </div>

                    </div>

                </div>

                <div class="request-detail__button-area">
                    @if ($attendanceRequest->status === 'pending')
                        <button type="submit" class="request-detail__button">
                            承認
                        </button>
                    @else
                        <button type="button" class="request-detail__button--approved" disabled>
                            承認済み
                        </button>
                    @endif
                </div>

            </form>

        </div>

    </div>

@endsection