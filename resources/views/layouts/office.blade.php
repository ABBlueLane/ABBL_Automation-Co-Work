<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Office IMS')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" rel="stylesheet">
    @yield('css')
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom mb-3">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">ABBL Office</a>
            @php
                $currentBusinessId = officeBusinessId();
            @endphp
            @if ($currentBusinessId)
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('office.issue.index', ['business' => $currentBusinessId]) }}">Issue</a>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('office.issue.project.index', ['business' => $currentBusinessId]) }}">Projects</a>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('office.issue.critical.index', ['business' => $currentBusinessId]) }}">Critical</a>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('office.dashboard.issue', ['business' => $currentBusinessId]) }}">Dashboard</a>
                </div>
            @endif
            <div class="ms-auto d-flex gap-2">
                @auth
                    <span class="text-muted small align-self-center">{{ auth()->user()->full_name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger" type="submit">Logout</button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    <main class="container-fluid py-2">
        @yield('content')
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        function copyText(text) {
            navigator.clipboard.writeText(text).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'คัดลอกแล้ว',
                    timer: 900,
                    showConfirmButton: false
                });
            }).catch(function() {
                Swal.fire('ผิดพลาด', 'คัดลอกไม่สำเร็จ', 'error');
            });
        }
    </script>
    @yield('script')
</body>

</html>
