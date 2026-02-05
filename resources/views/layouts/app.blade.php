<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Judson Associates | Inventory</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet"
        href="{{ asset('template/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- DataTables CSS -->
    <link rel="stylesheet"
        href="{{ asset('template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('template/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('template/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="{{ asset('template/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    @stack('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Navbar -->
        @include('layouts.header')
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        @include('layouts.sidebar')

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">@yield('page_title', 'Dashboard')</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <!-- <li class="breadcrumb-item"><a href="#">Dashboard</a></li> -->
                                @yield('breadcrumb', 'Dashboard')
                            </ol>

                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->
            <!-- Main content -->
            <div class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <div class="p-3">
                <h5>Title</h5>
                <p>Sidebar content</p>
            </div>
        </aside>
        <!-- /.control-sidebar -->

        <!-- Main Footer -->
        @include('layouts.footer')
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->
    <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}">
    </script>
    <!-- overlayScrollbars -->
    <script src="{{ asset('template/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>

    <!-- DataTables JS -->
    <script src="{{ asset('template/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script
        src="{{ asset('template/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}">
    </script>
    <script
        src="{{ asset('template/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}">
    </script>
    <script
        src="{{ asset('template/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}">
    </script>
    <script
        src="{{ asset('template/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}">
    </script>
    <script
        src="{{ asset('template/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}">
    </script>
    <script src="{{ asset('template/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('template/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('template/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('template/plugins/datatables-buttons/js/buttons.html5.min.js') }}">
    </script>
    <script src="{{ asset('template/plugins/datatables-buttons/js/buttons.print.min.js') }}">
    </script>
    <script src="{{ asset('template/plugins/datatables-buttons/js/buttons.colVis.min.js') }}">
    </script>
    <script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>

    <!-- Init all .table instances as DataTables -->
    @stack('scripts')
</body>

</html>
