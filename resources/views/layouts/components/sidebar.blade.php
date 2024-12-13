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
                @can('view security guards')
                <li class="{{ Request::segment(2) == 'security-guards' ? 'mm-active' : '' }}">
                    <a href="{{ route('security-guards.index')}}" class="waves-effect">
                        <i class="fas fa-user-secret"></i>
                        <span key="t-spreadsheet">Onboard Guard</span>
                    </a>
                </li>
                @endcanany
                @can('view guard roaster')
                <li class="{{ Request::segment(2) == 'guard-rosters' ? 'mm-active' : '' }}">
                    <a href="{{ route('guard-rosters.index')}}" class="waves-effect">
                        <i class="bx bx-hive"></i>
                        <span key="t-spreadsheet">Guard Roster</span>
                    </a>
                </li>
                @endcanany
                @can('view attendance')
                <li class="{{ Request::segment(2) == 'attendance' ? 'mm-active' : '' }}">
                    <a href="{{ route('attendance.index')}}" class="waves-effect">
                        <i class="bx bx-spreadsheet"></i>
                        <span key="t-spreadsheet">Attendance</span>
                    </a>
                </li>
                @endcanany
                <li class="{{ Request::segment(2) == 'payrolls' ? 'mm-active' : '' }}">
                    <a href="{{ route('payrolls.index')}}" class="waves-effect">
                        <i class="bx bx-checkbox-square"></i>
                        <span key="t-spreadsheet">Payroll</span>
                    </a>
                </li>
                @canany('view user')
                <li class="{{ Request::segment(2) == 'users' ? 'mm-active' : '' }}">
                    <a href="{{ route('users.index')}}" class="waves-effect">
                        <i class="bx bx-user"></i>
                        <span key="t-user">Onboard Admin</span>
                    </a>
                </li>
                @endcanany
                @can('view leaves')
                <li class="{{ Request::segment(2) == 'leaves' ? 'mm-active' : '' }}">
                    <a href="{{ route('leaves.index')}}" class="waves-effect">
                        <i class="bx bx-tone"></i>
                        <span key="t-spreadsheet">Leaves</span>
                    </a>
                </li>
                @endcanany
                {{--<li {{ Request::segment(2) == 'calendar-management' ? 'mm-active' : '' }}>
                    <a href="{{ route('calendar.management') }}" class="waves-effect">
                        <i class="bx bx-calendar"></i>
                        <span key="t-calendar">Calendar Management</span>
                    </a>
                </li> --}}
               
                @can('view client')
                <li class="{{ Request::segment(2) == 'clients' ? 'mm-active' : '' }}">
                    <a href="{{ route('clients.index')}}" class="waves-effect">
                        <i class="bx bxs-group"></i>
                        <span key="t-user">Client listing</span>
                    </a>
                </li>
                @endcanany
                @can('view client site')
                <li class="{{ Request::segment(2) == 'client-sites' ? 'mm-active' : '' }}">
                    <a href="{{ route('client-sites.index')}}" class="waves-effect">
                        <i class="bx bx-buildings"></i>
                        <span key="t-user">Client sites</span>
                    </a>
                </li>
                @endcanany
                @can('view rate master')
                <li class="{{ Request::segment(2) == 'rate-master' ? 'mm-active' : '' }}">
                    <a href="{{ route('rate-master.index')}}" class="waves-effect">
                        <i class="bx bx-receipt"></i>
                        <span key="t-receipt">Rate Master</span>
                    </a>
                </li>
                @endcanany
                <li class="{{ Request::segment(2) == 'fortnight-dates' ? 'mm-active' : '' }}">
                    <a href="{{ route('fortnight-dates.index')}}" class="waves-effect">
                        <i class="bx bx-grid-horizontal"></i>
                        <span key="t-wrench">Fortnight Dates</span>
                    </a>
                </li>
                @can('view public holiday')
                <li class="{{ Request::segment(2) == 'public-holidays' ? 'mm-active' : '' }}">
                    <a href="{{ route('public-holidays.index')}}" class="waves-effect">
                        <i class="bx bx-gift"></i>
                        <span key="t-receipt">Public Holidays</span>
                    </a>
                </li>
                @endcanany
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
                    'active' => Request::is('faq', 'help_requests'),
                ])>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-list-ul"></i>
                        <span key="t-dashboards">Manage Pages</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('faq.index')}}" key="t-tui-calendar">FAQ</a></li>
                        <li><a href="{{ route('help_requests.index')}}" key="t-tui-calendar">Help Request</a></li>
                    </ul>
                </li>

                <li @class([
                    'active' => Request::is('settings', 'settings/general-setting', 'settings/payment-setting'),
                ])>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bxs-wrench"></i>
                        <span key="t-dashboards">Settings</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('settings.index')}}" key="t-tui-calendar">Site Settings</a></li>
                        <li><a href="{{ route('settings.gerenal-settings')}}" key="t-tui-calendar">Gerenal Settings</a></li>
                        <li><a href="{{ route('settings.payment-settings')}}" key="t-tui-calendar">Payment Settings</a></li>
                    </ul>
                </li>

                @can('view roles & permissions')
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
                @endcanany

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