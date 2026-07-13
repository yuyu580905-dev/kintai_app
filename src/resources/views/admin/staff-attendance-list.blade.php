@extends('layouts.admin')

@section('title', 'スタッフ別勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/staff-attendance-list.css') }}">
@endsection

@section('main-class', 'main--app')

@section('content')

        <div class="staff-attendance-list">

            <div class="staff-attendance-list__inner">

                <h1 class="staff-attendance-list__title">
                    {{ $user->name }}さんの勤怠
                </h1>

                {{-- 月切り替え --}}
                <div class="staff-attendance-list__date-nav">

                    <a href="{{ route('admin.attendance.staff', ['user' => $user, 'month' => $month->copy()->subMonth()->format('Y-m')]) }}"
                        class="staff-attendance-list__date-link">
                        <img src="{{ asset('images/arrow.png') }}" class="staff-attendance-list__arrow--left" alt="前月">前月
                    </a>

                    <div class="staff-attendance-list__current-date">
                        📅 {{ $month->format('Y/m') }}
                    </div>

                    <a href="{{ route('admin.attendance.staff', ['user' => $user, 'month' => $month->copy()->addMonth()->format('Y-m')]) }}"
                        class="staff-attendance-list__date-link">
                        <span>翌月</span>
                        <img src="{{ asset('images/arrow.png') }}" class="staff-attendance-list__arrow--right" alt="翌月">
                    </a>

                </div>

                {{-- テーブル --}}
                <div class="staff-attendance-table">

                    <table class="staff-attendance-table__table">

                        <thead class="staff-attendance-table__head">

                            <tr class="staff-attendance-table__row">
                                <th class="staff-attendance-table__header staff-attendance-table__header--date">日付</th>
                                <th class="staff-attendance-table__header staff-attendance-table__header--clock-in">出勤</th>
                                <th class="staff-attendance-table__header staff-attendance-table__header--clock-out">退勤</th>
                                <th class="staff-attendance-table__header staff-attendance-table__header--break">休憩</th>
                                <th class="staff-attendance-table__header staff-attendance-table__header--total">合計</th>
                                <th class="staff-attendance-table__header staff-attendance-table__header--detail">詳細</th>
                            </tr>

                        </thead>

                        <tbody class="staff-attendance-table__body">

                            @foreach ($days as $day)

                                @php
    $attendance = $attendances[$day->format('Y-m-d')] ?? null;
                                @endphp

                                <tr class="staff-attendance-table__row">

                                    <td class="staff-attendance-table__cell">
                                        {{ $day->isoFormat('MM/DD(ddd)') }}
                                    </td>

                                    <td class="staff-attendance-table__cell">
                                        {{ optional($attendance?->clock_in)->format('H:i') }}
                                    </td>

                                    <td class="staff-attendance-table__cell">
                                        {{ optional($attendance?->clock_out)->format('H:i') }}
                                    </td>

                                    <td class="staff-attendance-table__cell">
                                        @if ($attendance)
                                            {{ $attendance->formattedBreakTime() }}
                                        @endif
                                    </td>

                                    <td class="staff-attendance-table__cell">
                                        @if ($attendance && $attendance->workingMinutes() !== null)
                                            {{ $attendance->formattedWorkingTime() }}
                                        @endif
                                    </td>

                                    <td class="staff-attendance-table__cell">
                                        @if ($attendance)
                                            <a href="{{ route('admin.attendance.detail', $attendance) }}"
                                                class="staff-attendance-table__detail-link">
                                                詳細
                                            </a>
                                        @endif
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <div class="staff-attendance-list__button-area">
                    <form action="{{ route('admin.attendance.staff.csv', $user) }}" method="GET">
                        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                        <button type="submit" class="staff-attendance-list__csv-button">
                            CSV出力
                        </button>
                    </form>
                </div>

            </div>

        </div>

@endsection