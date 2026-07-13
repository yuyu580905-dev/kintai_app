@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/staff-list.css') }}">
@endsection

@section('main-class', 'main--app')

@section('content')

    <div class="staff-list">

        <div class="staff-list__inner">

            <h1 class="staff-list__title">
                スタッフ一覧
            </h1>

            <div class="staff-table">

                <table class="staff-table__table">

                    <thead class="staff-table__head">

                        <tr class="staff-table__row">

                            <th class="staff-table__header">
                                名前
                            </th>

                            <th class="staff-table__header">
                                メールアドレス
                            </th>

                            <th class="staff-table__header">
                                月次勤怠
                            </th>

                        </tr>

                    </thead>

                    <tbody class="staff-table__body">

                        @foreach ($users as $user)

                            <tr class="staff-table__row">

                                <td class="staff-table__cell">
                                    {{ $user->name }}
                                </td>

                                <td class="staff-table__cell">
                                    {{ $user->email }}
                                </td>

                                <td class="staff-table__cell">

                                    <a href="{{ route('admin.attendance.staff', $user) }}" class="staff-table__link">
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