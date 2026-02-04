<div class="left-sidenav">
    <ul class="metismenu left-sidenav-menu">
        <li>
            <a href="{{ route('dashboard') }}"><i class="ti-layout-grid2"></i><span>Dashboard</span><span
                        class="menu-arrow"></span></a>
        </li>

        <li class="nk-menu-heading">
            <h6 class="overline-title">Team Management</h6>
        </li>

        <li>
            <a href="{{ route('team.index') }}"><i
                        class="ti-view-list-alt"></i><span>Teams</span><span
                        class="menu-arrow"></span></a>
        </li>

        <li class="nk-menu-heading">
            <h6 class="overline-title">Event Management</h6>
        </li>

        <li>
            <a href="{{ route('event.index') }}"><i
                        class="ti-view-list-alt"></i><span>Events</span><span
                        class="menu-arrow"></span></a>
        </li>

        <li>
            <a href="{{ route('event.delete.catch.media') }}"><i
                        class="ti-trash"></i><span>Delete Catch Media</span><span
                        class="menu-arrow"></span></a>
        </li>

        <li>
            <a href="{{ route('specie.index') }}"><i
                        class="ti-view-list-alt"></i><span>Species</span><span
                        class="menu-arrow"></span></a>
        </li>

        <li class="nk-menu-heading">
            <h6 class="overline-title">Reports</h6>
        </li>

        <li>
            <a href="{{ route('team.ranking.report') }}"><i
                        class="ti-view-list-alt"></i><span>Team Ranking Report</span><span
                        class="menu-arrow"></span></a>
        </li>

        <li>
            <a href="{{ route('individual.fish.report') }}"><i
                        class="ti-view-list-alt"></i><span>Individual Fish Report</span><span
                        class="menu-arrow"></span></a>
        </li>

        <li>
            <a href="{{ route('extra.photo.report') }}"><i
                        class="ti-view-list-alt"></i><span>Glory Fish Photo</span><span
                        class="menu-arrow"></span></a>
        </li>

        <li>
            <a href="{{ route('event.login.report') }}"><i
                    class="ti-view-list-alt"></i><span>Login Report</span><span
                    class="menu-arrow"></span></a>
        </li>

        <li>
            <a href="{{ route('team.profiles.report') }}"><i
                    class="ti-user"></i><span>Teams Profiles Report</span><span
                    class="menu-arrow"></span></a>
        </li>

        <li class="nk-menu-heading">
            <h6 class="overline-title">Notifications</h6>
        </li>

        <li>
            <a href="{{ route('notification.index') }}"><i
                        class="ti-view-list-alt"></i><span>Notifications</span><span
                        class="menu-arrow"></span></a>
        </li>

        <li>
            <a href="{{ route('logout') }}"><i class="ti-layout-grid2"></i><span>Logout</span><span
                    class="menu-arrow"></span></a>
        </li>

    </ul>
</div>
