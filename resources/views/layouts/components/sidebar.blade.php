<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu">Menu</li>

                <li @class(['mm-active' => Request::is('dashboard')])>
                    <a href="{{ route('admin.dashboard.index') }}" class="waves-effect">
                        <i class="bx bx-home-alt"></i>
                        <span key="t-dashboards">Dashboard</span>
                    </a>
                </li>
                <li class="{{ Request::segment(2) == 'security-guards' ? 'mm-active' : '' }}">
                    <a href="{{ route('security-guards.index')}}" class="waves-effect">
                        <i class="fas fa-user-secret"></i>
                        <span key="t-spreadsheet">Security Guard</span>
                    </a>
                </li>
                <li class="{{ Request::segment(2) == 'attendance' ? 'mm-active' : '' }}">
                    <a href="{{ route('attendance.index')}}" class="waves-effect">
                        <i class="bx bx-spreadsheet"></i>
                        <span key="t-spreadsheet">Attendance</span>
                    </a>
                </li>
                {{--<li {{ Request::segment(2) == 'calendar-management' ? 'mm-active' : '' }}>
                    <a href="{{ route('calendar.management') }}" class="waves-effect">
                        <i class="bx bx-calendar"></i>
                        <span key="t-calendar">Calendar Management</span>
                    </a>
                </li> --}}
                <li class="{{ Request::segment(2) == 'users' ? 'mm-active' : '' }}">
                    <a href="{{ route('users.index')}}" class="waves-effect">
                        <i class="bx bx-user"></i>
                        <span key="t-user">Users</span>
                    </a>
                </li>
                <li class="{{ Request::segment(2) == 'rate-master' ? 'mm-active' : '' }}">
                    <a href="{{ route('rate-master.index')}}" class="waves-effect">
                        <i class="bx bx-receipt"></i>
                        <span key="t-receipt">Rate Master</span>
                    </a>
                </li>
                {{-- </li>
                <li>
                    <a href="#" class="waves-effect">
                        <i class="bx bx-globe"></i>
                        <span key="t-globe">Location Tracking</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="waves-effect">
                        <i class="bx bx-wrench"></i>
                        <span key="t-wrench">Site Management</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="waves-effect">
                        <i class="bx bx-bell"></i>
                        <span key="t-bell">Enquiries</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="waves-effect">
                        <i class="bx bx-detail"></i>
                        <span key="t-bell">Reports</span>
                    </a>
                </li> --}}
                <li @class([
                    'active' => Request::is('faq'),
                ])>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-list-ul"></i>
                        <span key="t-dashboards">Manage Pages</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('faq.index')}}" key="t-tui-calendar">FAQ</a></li>
                    </ul>
                </li>

                <li @class([
                    'active' => Request::is('settings', 'settings/general-setting'),
                ])>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bxs-wrench"></i>
                        <span key="t-dashboards">Settings</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('settings.index')}}" key="t-tui-calendar">Site Settings</a></li>
                    </ul>
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bxs-user-detail"></i>
                        <span key="t-dashboards">Roles and Permissions</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('roles-and-permissions.role-list') }}" key="t-tui-calendar">Manage Roles</a></li>
                        <li><a href="{{ route('roles-and-permissions.index') }}" key="t-full-calendar">Manage Permissions</a></li>
                    </ul>
                </li>

                {{--<li>
                    <a href="javascript: void(0);" class="waves-effect has-arrow">
                        <i class="bx bx-briefcase-alt"></i>
                        <span key="t-jobs">Jobs</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="javascript: void(0);" key="t-job-list">Job List</a></li>
                        <li><a href="javascript: void(0);" key="t-job-grid">Job Grid</a></li>
                        <li><a href="javascript: void(0);" key="t-apply-job">Apply Job</a></li>
                        <li><a href="javascript: void(0);" key="t-job-details">Job Details</a></li>
                        <li><a href="javascript: void(0);" key="t-Jobs-categories">Jobs Categories</a></li>
                        <li>
                            <a href="javascript: void(0);" class="has-arrow" key="t-candidate">Candidate</a>
                            <ul class="sub-menu" aria-expanded="true">
                                <li><a href="javascript: void(0);" key="t-list">List</a></li>
                                <li><a href="javascript: void(0);" key="t-overview">Overview</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>--}}
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>