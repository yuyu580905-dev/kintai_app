@extends('layouts.admin')

@section('title', '申請一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/request-list.css') }}">
@endsection

@section('main-class', 'main--app')

@section('content')

    <div class="request-list">

        <div class="request-list__inner">

            <h1 class="request-list__title">
                申請一覧
            </h1>

            {{-- タブ --}}
            <div class="request-list-tabs">

                <div class="request-list-tabs__header">

                    <a href="{{ route('requests.index', ['status' => 'pending']) }}"
                        class="request-list-tabs__link {{ $status === 'pending' ? 'request-list-tabs__link--active' : '' }}">
                        承認待ち
                    </a>

                    <a href="{{ route('requests.index', ['status' => 'approved']) }}"
                        class="request-list-tabs__link {{ $status === 'approved' ? 'request-list-tabs__link--active' : '' }}">
                        承認済み
                    </a>

                </div>

            </div>

            {{-- テーブル --}}
            <div class="request-table">

                <table class="request-table__table">

                    <thead class="request-table__head">

                        <tr class="request-table__row">
                            <th class="request-table__header request-table__header--status">
                                状態
                            </th>

                            <th class="request-table__header request-table__header--name">
                                名前
                            </th>

                            <th class="request-table__header request-table__header--date">
                                対象日時
                            </th>

                            <th class="request-table__header request-table__header--reason">
                                申請理由
                            </th>

                            <th class="request-table__header request-table__header--request-date">
                                申請日時
                            </th>

                            <th class="request-table__header request-table__header--detail">
                                詳細
                            </th>
                        </tr>

                    </thead>

                    <tbody class="request-table__body">

                        @foreach ($requests as $request)

                            <tr class="request-table__row">

                                <td class="request-table__cell">
                                    {{ $request->status_label }}
                                </td>

                                <td class="request-table__cell">
                                    {{ $request->attendance->user->name }}
                                </td>

                                <td class="request-table__cell">
                                    {{ $request->attendance->work_date->format('Y/m/d') }}
                                </td>

                                <td class="request-table__cell">
                                    {{ $request->reason }}
                                </td>

                                <td class="request-table__cell">
                                    {{ $request->created_at->format('Y/m/d') }}
                                </td>

                                <td class="request-table__cell">
                                    <a href="{{ route('admin.requests.show', $request) }}" class="request-table__detail-link">
                                        詳細
                                    </a>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection