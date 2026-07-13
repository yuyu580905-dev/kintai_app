@extends('layouts.admin')

@section('title', '勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance-detail.css') }}">
@endsection

@section('main-class', 'main--app')

@section('content')

    @php
        $displayClockIn = $pendingRequest
            ? $pendingRequest->requested_clock_in
            : $attendance->clock_in;

        $displayClockOut = $pendingRequest
            ? $pendingRequest->requested_clock_out
            : $attendance->clock_out;

        $displayBreaks = $pendingRequest
            ? $pendingRequest->breaks
            : $attendance->breaks;

        $displayReason = $pendingRequest
            ? $pendingRequest->reason
            : $attendance->reason;
    @endphp

    <div class="attendance-detail">

        <div class="attendance-detail__inner">

            <h1 class="attendance-detail__title">
                勤怠詳細
            </h1>

            <form action="" method="post" class="attendance-detail__form">
                @csrf
                @method('PATCH')

                <div class="attendance-detail-card">

                    {{-- 名前 --}}
                    <div class="attendance-detail-card__row">
                        <div class="attendance-detail-card__label">
                            名前
                        </div>

                        <div class="attendance-detail-card__content">
                            <span class="attendance-detail-card__text">
                                {{ $attendance->user->name }}
                            </span>
                        </div>
                    </div>

                    {{-- 日付 --}}
                    <div class="attendance-detail-card__row">
                        <div class="attendance-detail-card__label">
                            日付
                        </div>

                        <div class="attendance-detail-card__content attendance-detail-card__content--date">
                            <span class="attendance-detail-card__text">
                                {{ $attendance->work_date->format('Y年') }}
                            </span>

                            <span class="attendance-detail-card__text">
                                {{ $attendance->work_date->format('n月j日') }}
                            </span>
                        </div>
                    </div>

                    {{-- 出勤退勤 --}}
                    <div class="attendance-detail-card__row">
                        <div class="attendance-detail-card__label">
                            出勤・退勤
                        </div>

                        <div class="attendance-detail-card__content attendance-detail-card__content--time">

                            <input type="time" name="clock_in" class="attendance-detail-card__input-time"
                                value="{{ old('clock_in', optional($displayClockIn)->format('H:i')) }}"
                                @disabled($pendingRequest)>

                            <span class="attendance-detail-card__separator">
                                〜
                            </span>

                            <input type="time" name="clock_out" class="attendance-detail-card__input-time"
                                value="{{ old('clock_out', optional($displayClockOut)->format('H:i')) }}"
                                @disabled($pendingRequest)>

                            @error('clock_in')
                                <p class="attendance-detail__error">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>
                    </div>

                    {{-- 休憩 --}}
                    @foreach ($displayBreaks as $index => $break)

                        <div class="attendance-detail-card__row">

                            <div class="attendance-detail-card__label">
                                {{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}
                            </div>

                            <div class="attendance-detail-card__content attendance-detail-card__content--time">

                                <input type="time" name="breaks[{{ $index }}][break_start]"
                                    class="attendance-detail-card__input-time"
                                    value="{{ old("breaks.$index.break_start", optional($break->break_start)->format('H:i')) }}"
                                    @disabled($pendingRequest)>

                                <span class="attendance-detail-card__separator">
                                    〜
                                </span>

                                <input type="time" name="breaks[{{ $index }}][break_end]"
                                    class="attendance-detail-card__input-time"
                                    value="{{ old("breaks.$index.break_end", optional($break->break_end)->format('H:i')) }}"
                                    @disabled($pendingRequest)>

                                @error("breaks.$index.break_start")
                                    <p class=" attendance-detail__error">
                                        {{ $message }}
                                    </p>
                                @enderror

                                @error("breaks.$index.break_end")
                                    <p class="attendance-detail__error">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>

                        </div>

                    @endforeach

                    @if (!$pendingRequest)

                                <div class="attendance-detail-card__row">

                                    <div class="attendance-detail-card__label">
                                        {{ $displayBreaks->isEmpty()
                        ? '休憩'
                        : '休憩' . ($displayBreaks->count() + 1)}}
                                    </div>

                                    <div class="attendance-detail-card__content attendance-detail-card__content--time">

                                        <input type="time" name="breaks[{{ $displayBreaks->count() }}][break_start]"
                                            class="attendance-detail-card__input-time" @disabled($pendingRequest)>

                                        <span class="attendance-detail-card__separator">
                                            〜
                                        </span>

                                        <input type="time" name="breaks[{{ $displayBreaks->count() }}][break_end]"
                                            class="attendance-detail-card__input-time" @disabled($pendingRequest)>

                                    </div>

                                </div>

                    @endif

                    {{-- 備考 --}}
                    <div class="attendance-detail-card__row">
                        <div class="attendance-detail-card__label">
                            備考
                        </div>

                        <div class="attendance-detail-card__content">

                            <textarea name="reason" class="attendance-detail-card__textarea"
                                @disabled($pendingRequest)>{{ old('reason', $displayReason) }}</textarea>

                            @error('reason')
                                <p class="attendance-detail__error">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>
                    </div>

                </div>

                <div class="attendance-detail__button-area">
                    @if(!$pendingRequest)
                        <button type="submit" class="attendance-detail__button">
                            修正
                        </button>
                    @else
                        <p class="attendance-detail__pending-message">
                            *承認待ちのため修正はできません。
                        </p>
                    @endif
                </div>

            </form>

        </div>

    </div>

@endsection