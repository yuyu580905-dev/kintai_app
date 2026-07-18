@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('main-class', 'main--app')

@section('content')

    <div class="attendance-list">

        <h1 class="attendance-list__title">
            勤怠一覧
        </h1>

        <div class="attendance-list__date-nav">

            <a href="{{ route('attendance.list', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}"
                class="attendance-list__date-link">
                <img src="{{ asset('images/arrow.png') }}" class="attendance-list__arrow--left" alt="前月">前月
            </a>

            <div class="attendance-list__current-date">
                <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
                {{ $currentMonth->format('Y/m') }}
            </div>



            <a href="{{ route('attendance.list', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}"
                class="attendance-list__date-link">
                <span>翌月</span>
                <img src="{{ asset('images/arrow.png') }}" class="attendance-list__arrow--right" alt="翌月">
            </a>

        </div>

        <table class="attendance-table">

            <thead class="attendance-table__head">

                <tr class="attendance-table__row">
                    <th class="attendance-table__header attendance-table__header--date">日付</th>
                    <th class="attendance-table__header attendance-table__header--clock-in">出勤</th>
                    <th class="attendance-table__header attendance-table__header--clock-out">退勤</th>
                    <th class="attendance-table__header attendance-table__header--break">休憩</th>
                    <th class="attendance-table__header attendance-table__header--total">合計</th>
                    <th class="attendance-table__header attendance-table__header--detail">詳細</th>
                </tr>

            </thead>

            <tbody class="attendance-table__body">

                @foreach ($days as $day)

                    @php
    $attendance = $attendances[$day->format('Y-m-d')] ?? null;
                    @endphp

                    <tr class="attendance-table__row">

                        <td class="attendance-table__cell">
                            {{ $day->isoFormat('MM/DD(ddd)') }}
                        </td>

                        <td class="attendance-table__cell">
                            {{ optional($attendance?->clock_in)->format('H:i') }}
                        </td>

                        <td class="attendance-table__cell">
                            {{ optional($attendance?->clock_out)->format('H:i') }}
                        </td>

                        <td class="attendance-table__cell">
                            @if ($attendance)
                                {{ $attendance->formattedBreakTime() }}
                            @endif
                        </td>

                        <td class="attendance-table__cell">
                            @if ($attendance && $attendance->workingMinutes() !== null)
                                {{ $attendance->formattedWorkingTime() }}
                            @endif
                        </td>

                        <td class="attendance-table__cell">
                            @if ($attendance)
                                <a href="{{ route('attendance.detail', $attendance) }}" class="attendance-table__detail-link">
                                    詳細
                                </a>
                            @endif
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

@endsection