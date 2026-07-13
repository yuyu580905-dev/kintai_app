@extends('layouts.app')

@section('title', 'マイ勤怠レポート')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/report.css') }}">
@endsection

@section('header')
    <x-header-nav type="report" />
@endsection

@section('main-class', 'main--report')

@section('content')

    <div class="report">

        <h1 class="report__title">
            マイ勤怠レポート
        </h1>

        <p class="report__description">
            過去６ヶ月の勤怠データから集計しています。
        </p>

        {{-- 基本サマリー --}}
        <section class="report-summary">

            <h2 class="report-heading">
                基本サマリー
            </h2>

            <div class="report-summary__cards">

                <div class="report-card">
                    <p class="report-card__label">
                        総労働時間
                    </p>

                    <p class="report-card__value">
                        {{ $report['totalWorkTime'] }}
                    </p>
                </div>

                <div class="report-card">
                    <p class="report-card__label">
                        総残業時間
                    </p>

                    <p class="report-card__value">
                        {{ $report['totalOvertimeTime'] }}
                    </p>
                </div>

                <div class="report-card">
                    <p class="report-card__label">
                        平均労働時間 / 日
                    </p>

                    <p class="report-card__value">
                        {{ $report['averageWorkTime'] }}
                    </p>
                </div>

            </div>

        </section>

        {{-- 月次推移 --}}
        <section class="report-monthly">

            <h2 class="report-heading">
                月次推移（過去６ヶ月）
            </h2>

            <table class="report-table">

                <thead class="report-table__head">
                    <tr class="report-table__header-row">
                        <th class="report-table__header">月</th>
                        <th class="report-table__header report-table__header--center">労働時間</th>
                        <th class="report-table__header report-table__header--right">残業時間</th>
                    </tr>
                </thead>

                <tbody class="report-table__body">
                    @foreach($report['monthlyTrend'] as $monthly)
                        <tr class="report-table__row">
                            <td class="report-table__cell">{{ $monthly['month'] }}</td>
                            <td class="report-table__cell report-table__cell--center">{{ $monthly['workTime'] }}</td>
                            <td class="report-table__cell report-table__cell--right">{{ $monthly['overtimeTime'] }}</td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

        </section>

        {{-- 異常検知 --}}
        <section class="report-alert">

            <h2 class="report-heading">
                今月の異常検知
            </h2>

            <p class="report-alert__description">
                基準: 始業 09:00 / 終業 18:00 / 長時間労働は1日10時間超
            </p>

            <div class="report-alert__cards">

                <div class="report-card">

                    <p class="report-card__label">
                        遅刻回数
                    </p>

                    <p class="report-card__value">
                        {{ $report['lateCount'] }}回
                    </p>

                </div>

                <div class="report-card">

                    <p class="report-card__label">
                        早退回数
                    </p>

                    <p class="report-card__value">
                        {{ $report['earlyLeaveCount'] }}回
                    </p>

                </div>

                <div class="report-card">

                    <p class="report-card__label">
                        長時間労働日数
                    </p>

                    <p class="report-card__value">
                        {{ $report['longWorkCount'] }}日
                    </p>

                </div>

            </div>

        </section>

    </div>

@endsection