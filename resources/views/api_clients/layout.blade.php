<!doctype html>
<html lang="th" data-layout="vertical" data-layout-style="default" data-layout-position="fixed" data-topbar="light" data-sidebar="dark" data-sidebar-size="sm-hover" data-layout-width="fluid">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'API Clients')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="ABBL Automation" name="description">

    <link rel="shortcut icon" href="{{ asset('images/icon.ico') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/simplebar/simplebar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/libs/node-waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.min.css') }}">
    <style>
        .api-page .page-title-box {
            padding-bottom: 1rem;
        }

        .api-page .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .api-page .topbar h1 {
            margin: 0;
            font-size: 1.375rem;
            font-weight: 600;
        }

        .api-page .topbar p {
            margin: .35rem 0 0;
            color: var(--vz-secondary-color);
        }

        .api-page .button,
        .api-page button[type="submit"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            border: 1px solid transparent;
            border-radius: .25rem;
            padding: .47rem .9rem;
            background: var(--vz-primary);
            color: #fff;
            font-weight: 500;
            line-height: 1.5;
            text-decoration: none;
            cursor: pointer;
        }

        .api-page .button.secondary {
            background: var(--vz-light);
            border-color: var(--vz-border-color);
            color: var(--vz-body-color);
        }

        .api-page .panel {
            background: var(--vz-card-bg);
            border: 1px solid var(--vz-border-color);
            border-radius: .25rem;
            box-shadow: var(--vz-box-shadow-sm);
            padding: 1.25rem;
        }

        .api-page .filters {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-bottom: 1rem;
        }

        .api-page .alert {
            border-radius: .25rem;
            padding: .8rem 1rem;
            margin-bottom: 1rem;
        }

        .api-page .success {
            background: var(--vz-success-bg-subtle);
            border: 1px solid var(--vz-success-border-subtle);
            color: var(--vz-success-text-emphasis);
        }

        .api-page .error {
            background: var(--vz-danger-bg-subtle);
            border: 1px solid var(--vz-danger-border-subtle);
            color: var(--vz-danger-text-emphasis);
        }

        .api-page .token {
            overflow-wrap: anywhere;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            background: #111827;
            color: #d1f7ff;
            border-radius: .25rem;
            padding: .85rem;
        }

        .api-page table {
            width: 100%;
            border-collapse: collapse;
        }

        .api-page th,
        .api-page td {
            border-bottom: 1px solid var(--vz-border-color);
            padding: .85rem .65rem;
            text-align: left;
            vertical-align: top;
        }

        .api-page th {
            color: var(--vz-secondary-color);
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .api-page label {
            display: block;
            margin-bottom: .4rem;
            font-weight: 600;
        }

        .api-page input,
        .api-page select,
        .api-page textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid var(--vz-input-border-custom);
            border-radius: .25rem;
            padding: .5rem .9rem;
            background: var(--vz-input-bg-custom);
            color: var(--vz-body-color);
            font: inherit;
        }

        .api-page textarea {
            min-height: 120px;
            resize: vertical;
        }

        .api-page .field {
            margin-bottom: 1rem;
        }

        .api-page .actions {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .api-page .badge.active {
            background: var(--vz-success-bg-subtle);
            color: var(--vz-success);
        }

        .api-page .badge.inactive {
            background: var(--vz-secondary-bg-subtle);
            color: var(--vz-secondary);
        }

        @media (max-width: 767.98px) {
            .api-page .topbar {
                flex-direction: column;
            }

            .api-page table,
            .api-page thead,
            .api-page tbody,
            .api-page th,
            .api-page td,
            .api-page tr {
                display: block;
            }

            .api-page thead {
                display: none;
            }

            .api-page td {
                padding: .65rem 0;
            }

            .api-page td::before {
                content: attr(data-label);
                display: block;
                color: var(--vz-secondary-color);
                font-size: .75rem;
                font-weight: 700;
                margin-bottom: .25rem;
                text-transform: uppercase;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div id="layout-wrapper">
        <header id="page-topbar">
            <div class="layout-width">
                <div class="navbar-header">
                    <div class="d-flex">
                        <div class="navbar-brand-box horizontal-logo">
                            <a href="{{ route('api_clients.index') }}" class="logo logo-dark">
                                <span class="logo-sm">
                                    <img src="{{ asset('images/icon.ico') }}" alt="" height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ asset('images/black-logo-bluelane.webp') }}" alt="" height="22">
                                </span>
                            </a>
                            <a href="{{ route('api_clients.index') }}" class="logo logo-light">
                                <span class="logo-sm">
                                    <img src="{{ asset('images/icon.ico') }}" alt="" height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ asset('images/white-logo-bluelane.webp') }}" alt="" height="22">
                                </span>
                            </a>
                        </div>

                        <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger material-shadow-none" id="topnav-hamburger-icon">
                            <span class="hamburger-icon">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        @auth
                            <span class="text-muted small">{{ Auth::user()->fullName() ?: Auth::user()->email }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-soft-danger">Logout</button>
                            </form>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <div class="app-menu navbar-menu">
            <div class="navbar-brand-box">
                <a href="{{ route('api_clients.index') }}" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="{{ asset('images/icon.ico') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('images/white-logo-bluelane.webp') }}" alt="" height="22">
                    </span>
                </a>
                <a href="{{ route('api_clients.index') }}" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="{{ asset('images/icon.ico') }}" alt="" height="22">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ asset('images/white-logo-bluelane.webp') }}" alt="" height="22">
                    </span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>

            <div id="scrollbar">
                <div class="container-fluid">
                    <div id="two-column-menu"></div>
                    <ul class="navbar-nav" id="navbar-nav">
                        <li class="menu-title"><span>Menu</span></li>
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
                            <a class="nav-link menu-link {{ request()->routeIs('business.select') || request()->routeIs('issue.*') || request()->routeIs('office.issue.*') ? 'active' : '' }}" href="{{ route('business.select') }}">
                                <i class="ri-bug-line"></i> <span>Issue Management</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="sidebar-background"></div>
        </div>

        <div class="vertical-overlay"></div>

        <div class="main-content">
            <div class="page-content api-page">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            {{ date('Y') }} © ABBL Automation
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">OneClick Template</div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @stack('scripts')
    @yield('script')
</body>
</html>
