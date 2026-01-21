<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8"/>
    <title>Trophy Hunter Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description"/>
    <meta content="" name="author"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">

    <!-- Plugins css -->
    <link href="{{ asset('plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet"/>
    <link href="{{ asset('plugins/select2/select2.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('plugins/timepicker/bootstrap-material-datetimepicker.css') }}" rel="stylesheet">
    <link href="{{ asset('plugins/bootstrap-touchspin/css/jquery.bootstrap-touchspin.min.css') }}" rel="stylesheet"/>

    <!-- DataTables -->
    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('plugins/datatables/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css"/>
    <!-- Responsive datatable examples -->
    <link href="{{ asset('plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css"/>

    <!-- App css -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/jquery-ui.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/icons.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/metisMenu.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet" type="text/css"/>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/main.css"/>

    {{-- toastr --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.0.1/css/toastr.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet">

    <script>
        const APP_URL = "{{ env('APP_URL') }}";
    </script>

    <style>
        #search-loaderr {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .spinnerr {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

    </style>

</head>

<body class="dark-sidenav">


<div id="search-loaderr" class="search-loaderr" style="display: none;">
    <div class="spinnerr"></div>
</div>


<div class="topbar">

    <div class="topbar-left">
        <ul class="list-unstyled topbar-nav mb-0 desktop-tbar">
            <li>
                <button class="nav-link button-menu-mobile waves-effect waves-light">
                    <i class="la la-bars"></i>
                </button>
            </li>
        </ul>
        <a href="{{ route('dashboard') }}" class="logo">
                    <span>
                    </span>
            <span>
                        <img src="{{ asset('images/logo.png') }}" alt="logo-large" class="logo-lg logo-light">
                        <img src="{{ asset('images/logo.png') }}" alt="logo-large" class="logo-lg">
                    </span>
        </a>
    </div>

    <nav class="navbar-custom">
        <ul class="list-unstyled topbar-nav float-right mb-0">
            <li class="dropdown">
                <a class="nav-link dropdown-toggle waves-effect waves-light nav-user" data-toggle="dropdown" href="#"
                   role="button"
                   aria-haspopup="false" aria-expanded="false">
                    <img src="{{ asset('images/logo.png') }}" alt="profile-user" class="rounded-circle"/>
                    <span class="ml-1 nav-user-name hidden-sm">{{ Auth::user()->name }} <i
                                class="mdi mdi-chevron-down"></i> </span>
                </a>

                <div class="dropdown-menu dropdown-menu-right">
                    {{--                    <a class="dropdown-item" href="{{ route('user.edit', ['user' => Auth::user()->id]) }}"><i--}}
                    {{--                            class="ti-user text-muted mr-2"></i>Edit Profile</a>--}}
                    {{--                    <a class="dropdown-item" href="{{ route('flush.data') }}"><i class="ti-trash text-muted mr-2"></i>Flush Data</a>--}}
                    <div class="dropdown-divider mb-0"></div>
                    <a class="dropdown-item" href="{{ route('logout') }}"><i class="ti-power-off text-muted mr-2"></i>
                        Logout</a>
                </div>
            </li>
        </ul>

        <ul class="list-unstyled topbar-nav mb-0 mobile-tbar">
            <li>
                <button class="nav-link button-menu-mobile waves-effect waves-light">
                    <i class="la la-bars"></i>
                </button>
            </li>
        </ul>
    </nav>
</div>

@include('layouts.portal.partials.sidebar')

