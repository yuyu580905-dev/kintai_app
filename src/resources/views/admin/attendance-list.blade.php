@extends('layouts.admin')

@section('title', '勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendance-list.css') }}">
@endsection

@section('main-class', 'main--app')

@section('content')

    <div class="attendance-list">

        <h1 class="attendance-list__title">
            {{ $currentDate->format('Y年n月j日') }}の勤怠
        </h1>

        <div class="attendance-list__date-nav">

            <a href="{{ route('admin.attendance.list', [
        'date' => $currentDate->copy()->subDay()->toDateString()
    ]) }}" class="attendance-list__date-link">
                <img src="{{ asset('images/arrow.png') }}" class="attendance-list__arrow--left" alt="前日">前日
            </a>

            <div class="attendance-list__current-date">
                📅 {{ $currentDate->format('Y/m/d') }}
            </div>

            <a href="{{ route('admin.attendance.list', [
        'date' => $currentDate->copy()->addDay()->toDateString()
    ]) }}" class="attendance-list__date-link">
                <span>翌日</span>
                <img src="{{ asset('images/arrow.png') }}" class="attendance-list__arrow--right" alt="翌日">
            </a>

        </div>

        <table class="attendance-table">

            <thead class="attendance-table__head">

                <tr class="attendance-table__row">
                    <th class="attendance-table__header attendance-table__header--name">名前</th>
                    <th class="attendance-table__header attendance-table__header--clock-in">出勤</th>
                    <th class="attendance-table__header attendance-table__header--clock-out">退勤</th>
                    <th class="attendance-table__header attendance-table__header--break">休憩</th>
                    <th class="attendance-table__header attendance-table__header--total">合計</th>
                    <th class="attendance-table__header attendance-table__header--detail">詳細</th>
                </tr>

            </thead>

            <tbody class="attendance-table__body">

                @foreach ($attendances as $attendance)

                    <tr class="attendance-table__row">

                        <td class="attendance-table__cell">
                            {{ $attendance->user->name }}
                        </td>

                        <td class="attendance-table__cell">
                            {{ optional($attendance->clock_in)->format('H:i') }}
                        </td>

                        <td class="attendance-table__cell">
                            {{ optional($attendance->clock_out)->format('H:i') }}
                        </td>

                        <td class="attendance-table__cell">
                            {{ $attendance->formattedBreakTime() }}
                        </td>

                        <td class="attendance-table__cell">
                            {{ $attendance->formattedWorkingTime() }}
                        </td>

                        <td class="attendance-table__cell">

                            <a href="{{ route('admin.attendance.detail', $attendance->id) }}"
                                class="attendance-table__detail-link">
                                詳細
                            </a>

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

@endsection