<nav class="header-nav">

    <ul class="header-nav__list">

        @if($type === 'admin')

            <li class="header-nav__item">
                <a href="/admin/attendance/list">勤怠一覧</a>
            </li>

            <li class="header-nav__item">
                <a href="/admin/staff/list">スタッフ一覧</a>
            </li>

            <li class="header-nav__item">
                <a href="/stamp_correction_request/list">申請一覧</a>
            </li>

        @elseif($type === 'finished')

            <li class="header-nav__item">
                <a href="/attendance/list">今月の出勤一覧</a>
            </li>

            <li class="header-nav__item">
                <a href="/stamp_correction_request/list">申請一覧</a>
            </li>

        @elseif($type === 'report')

            <li class="header-nav__item">
                <a href="/attendance">勤怠</a>
            </li>

            <li class="header-nav__item">
                <a href="/attendance/list">勤怠一覧</a>
            </li>

            <li class="header-nav__item">
                <a href="/stamp_correction_request/list">申請</a>
            </li>

            <li class="header-nav__item">
                <a href="/attendance/report">レポート</a>
            </li>

        @else

            <li class="header-nav__item">
                <a href="/attendance">勤怠</a>
            </li>

            <li class="header-nav__item">
                <a href="/attendance/list">勤怠一覧</a>
            </li>

            <li class="header-nav__item">
                <a href="/stamp_correction_request/list">申請</a>
            </li>

        @endif

        <li class="header-nav__item">
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button type="submit">
                    ログアウト
                </button>
            </form>
        </li>

    </ul>

</nav>