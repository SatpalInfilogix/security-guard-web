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
                <li class="menu-title" key="t-menu">Guard</li>
                <li @class([
                    'active' => Request::is(
                        'security-guards*',
                        'guard-rosters*',
                        'attendance*',
                        'deductions*',
                        'payrolls*',
                        'invoices*',
                        'leaves*',
                        'clients*',
                        'client-sites*',
                        'rate-master*',
                        'fortnight-dates*'),
                ])>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="fas fa-user-secret"></i>
                        <span key="t-dashboards">Guard</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        @can('view security guards')
                            <li><a href="{{ route('security-guards.index') }}">Onboard Guard</a></li>
                        @endcan
                        @can('view guard roster')
                            <li><a href="{{ route('guard-rosters.index') }}">Guard Roster</a></li>
                        @endcan
                        @can('view attendance')
                            <li><a href="{{ route('attendance.index') }}">Attendance</a></li>
                        @endcan
                        @can('view nst deduction')
                            <li><a href="{{ route('deductions.index') }}">NST Deduction</a></li>
                        @endcan
                        @can('view payroll')
                            <li><a href="{{ route('payrolls.index') }}">Payroll</a></li>
                        @endcan
                        @can('view invoice')
                            <li><a href="{{ route('invoices.index') }}">Invoice</a></li>
                        @endcan
                        @can('view leaves')
                            <li><a href="{{ route('leaves.index') }}">Leaves</a></li>
                        @endcan
                        @can('view guard encashment')
                            <li><a href="{{ route('guard-leave-encashment.index') }}">Pay In Lieu of Guard</a></li>
                        @endcan
                        @can('view client')
                            <li><a href="{{ route('clients.index') }}">Client listing</a></li>
                        @endcan
                        @can('view client site')
                            <li><a href="{{ route('client-sites.index') }}">Client sites</a></li>
                        @endcan
                        @can('view rate master')
                            <li><a href="{{ route('rate-master.index') }}">Rate Master</a></li>
                        @endcan
                        <li><a href="{{ route('fortnight-dates.index') }}">Fortnight Dates</a></li>
                    </ul>
                </li>

                <li class="menu-title " key="t-menu">Employee</li>
                @canany(['view employee', 'view employee rate master', 'view employee leaves', 'view employee payroll'])
                    <li @class([
                        'active' => Request::is(
                            'employees*',
                            'employee-rate-master*',
                            'employee-deductions*',
                            'employee-leaves*',
                            'employee-payroll*',
                            'twenty-two-days-interval'),
                    ])>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="fas fa-female"></i>
                            <span key="t-dashboards">Employee</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @can('view employee')
                                <li><a href="{{ route('employees.index') }}">Employee</a></li>
                            @endcan
                            @can('view employee rate master')
                                <li><a href="{{ route('employee-rate-master.index') }}">Employee Rate Master</a></li>
                            @endcan
                            <li><a href="{{ route('employee-deductions.index') }}">Employee Deduction</a></li>
                            @can('view employee leaves')
                                <li><a href="{{ route('employee-leaves.index') }}">Employee Leaves</a></li>
                            @endcan
                            @can('view employee payroll')
                                <li><a href="{{ route('employee-payroll.index') }}">Employee Payroll</a></li>
                            @endcan
                            @can('view employee overtime')
                                <li><a href="{{ route('employee-overtime.index') }}">Employee Overtime</a></li>
                            @endcan
                            @can('view employee encashment')
                                <li><a href="{{ route('employee-leave-encashment.index') }}">Pay In Lieu of Employee</a></li>
                            @endcan
                            <li><a href="{{ route('get-interval') }}">Twenty Two Days Interval</a></li>
                        </ul>
                    </li>
                @endcanany
                <li class="menu-title" key="t-menu">Other</li>
                @can('view public holiday')
                    <li class="{{ Request::segment(2) == 'public-holidays' ? 'mm-active' : '' }}">
                        <a href="{{ route('public-holidays.index') }}" class="waves-effect">
                            <i class="bx bx-gift"></i>
                            <span key="t-receipt">Public Holidays</span>
                        </a>
                    </li>
                @endcan
                @canany(['view faq', 'view help request'])
                    <li @class([
                        'active' => Request::is('faq', 'help_requests'),
                    ])>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="bx bx-list-ul"></i>
                            <span key="t-dashboards">Manage Pages</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @can('view faq')
                                <li><a href="{{ route('faq.index') }}" key="t-tui-calendar">FAQ</a></li>
                            @endcan
                            @can('view help request')
                                <li><a href="{{ route('help_requests.index') }}" key="t-tui-calendar">Help Request</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany
                @canany([
                    'view site setting',
                    'view gerenal setting',
                    'view payment setting',
                    'view employee tax
                    threshold',
                    'view gurad tax threshold',
                    'view user activity',
                    ])
                    <li @class([
                        'active' => Request::is(
                            'settings',
                            'settings/general-setting',
                            'settings/payment-setting'),
                    ])>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="bx bxs-wrench"></i>
                            <span key="t-dashboards">Settings</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @can('view site setting')
                                <li><a href="{{ route('settings.index') }}" key="t-tui-calendar">Site Settings</a></li>
                            @endcan
                            @can('view gerenal setting')
                                <li><a href="{{ route('settings.gerenal-settings') }}" key="t-tui-calendar">Gerenal
                                        Settings</a></li>
                            @endcan
                            @can('view payment setting')
                                <li><a href="{{ route('settings.payment-settings') }}" key="t-tui-calendar">Payment
                                        Settings</a></li>
                            @endcan
                            @can('view employee tax threshold')
                                <li><a href="{{ route('employee-tax-threshold.index') }}" key="t-tui-calendar">Employee
                                        Tax Threshold</a></li>
                            @endcan
                            @can('view gurad tax threshold')
                                <li><a href="{{ route('guard-tax-threshold.index') }}" key="t-tui-calendar">Guard
                                        Tax Threshold</a></li>
                            @endcan
                            {{-- @can('view user activity')
                                <li><a href="{{ route('activities.index') }}" key="t-tui-calendar">User Activity</a></li>
                            @endcan --}}
                        </ul>
                    </li>
                @endcanany

                @canany(['view roles & permissions', 'view user','view user activity'])
                    <li @class([
                        'active' => Request::is(
                            'roles-and-permissions',
                            'users/index',
                            'roles-and-permissions/role-list',
                            'roles-and-permissions/index'),
                    ])>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="bx bxs-user-detail"></i>
                            <span key="t-dashboards">User/Roles</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            @can('view roles & permissions')
                                <li><a href="{{ route('roles-and-permissions.role-list') }}" key="t-tui-calendar">Manage
                                        Roles</a></li>
                            @endcan
                            @can('view user')
                                <li class="{{ Request::segment(2) == 'users' ? 'mm-active' : '' }}"><a
                                        href="{{ route('users.index') }}" key="t-user">User</a></li>
                            @endcan
                            @can('view roles & permissions')
                                <li><a href="{{ route('roles-and-permissions.index') }}" key="t-full-calendar">Manage
                                        Permissions</a></li>
                            @endcan
                        </ul>
                    </li>
                    @can('view user activity')
                        <li><a href="{{ route('activities.index') }}" key="t-tui-calendar"><i class="bx bx-home-alt"></i>User
                                Activity</a></li>
                    @endcan
                @endcanany




                {{-- <li>
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
                </li> --}}
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
