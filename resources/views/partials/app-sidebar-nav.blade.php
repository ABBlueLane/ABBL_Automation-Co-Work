@php
    $currentBusinessId = officeBusinessId();
@endphp
<ul class="navbar-nav" id="navbar-nav">
    <li class="menu-title"><span>Menu</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="ri-dashboard-line"></i> <span>Dashboard</span>
        </a>
    </li>
    @if ($currentBusinessId)
        <li class="nav-item">
            <a class="nav-link menu-link {{ request()->routeIs('office.issue.*') && !request()->routeIs('office.issue.project.*') && !request()->routeIs('office.issue.critical.*') ? 'active' : 'collapsed' }}" href="#sidebarOfficeIssue" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('office.*') ? 'true' : 'false' }}" aria-controls="sidebarOfficeIssue">
                <i class="ri-bug-line"></i> <span>Office Issue</span>
            </a>
            <div class="collapse menu-dropdown {{ request()->routeIs('office.*') ? 'show' : '' }}" id="sidebarOfficeIssue">
                <ul class="nav nav-sm flex-column">
                    <li class="nav-item">
                        <a href="{{ route('office.issue.index', ['business' => $currentBusinessId]) }}" class="nav-link {{ request()->routeIs('office.issue.index') || request()->routeIs('office.issue.view') ? 'active' : '' }}">Issue</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('office.issue.project.index', ['business' => $currentBusinessId]) }}" class="nav-link {{ request()->routeIs('office.issue.project.*') ? 'active' : '' }}">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('office.issue.critical.index', ['business' => $currentBusinessId]) }}" class="nav-link {{ request()->routeIs('office.issue.critical.*') ? 'active' : '' }}">Critical</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('office.dashboard.issue', ['business' => $currentBusinessId]) }}" class="nav-link {{ request()->routeIs('office.dashboard.*') ? 'active' : '' }}">Dashboard</a>
                    </li>
                </ul>
            </div>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link menu-link {{ request()->routeIs('admin.issues.*') ? 'active' : '' }}" href="{{ route('admin.issues.index') }}">
            <i class="ri-file-list-3-line"></i> <span>Issue Management</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link {{ request()->routeIs('api_clients.*') ? 'active' : 'collapsed' }}" href="#sidebarSettings" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('api_clients.*') ? 'true' : 'false' }}" aria-controls="sidebarSettings">
            <i class="ri-settings-3-line"></i> <span>ตั้งค่า</span>
        </a>
        <div class="collapse menu-dropdown {{ request()->routeIs('api_clients.*') ? 'show' : '' }}" id="sidebarSettings">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a href="{{ route('api_clients.index') }}" class="nav-link {{ request()->routeIs('api_clients.*') ? 'active' : '' }}">API Clients</a>
                </li>
            </ul>
        </div>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
            <i class="ri-user-settings-line"></i> <span>Users</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
            <i class="ri-file-list-3-line"></i> <span>Logs</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link {{ request()->routeIs('monitor.*') ? 'active' : 'collapsed' }}" href="#sidebarMonitor" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('monitor.*') ? 'true' : 'false' }}" aria-controls="sidebarMonitor">
            <i class="ri-pulse-line"></i> <span>Uptime Monitor</span>
        </a>
        <div class="collapse menu-dropdown {{ request()->routeIs('monitor.*') ? 'show' : '' }}" id="sidebarMonitor">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a href="{{ route('monitor.index') }}" class="nav-link {{ request()->routeIs('monitor.index') || request()->routeIs('monitor.status') ? 'active' : '' }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('monitor.settings') }}" class="nav-link {{ request()->routeIs('monitor.settings*') ? 'active' : '' }}">ตั้งค่าแจ้งเตือน LINE</a>
                </li>
            </ul>
        </div>
    </li>
</ul>
