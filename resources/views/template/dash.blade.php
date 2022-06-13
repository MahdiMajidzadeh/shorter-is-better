@extends('template.master')

@section('content')
    <div class="d-flex flex-column flex-lg-row h-lg-full">
        <!-- Vertical Navbar -->
        <nav class="navbar show navbar-vertical h-lg-screen navbar-expand-lg px-0 py-3 py-lg-0 navbar-light bg-white border-end-lg" id="navbarVertical">
            <div class="container-fluid">
                <!-- Toggler -->
                <button class="navbar-toggler ms-n2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse" aria-controls="sidebarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <!-- Brand -->
                <a class="navbar-brand py-lg-5 mb-lg-5 px-lg-6 me-0" href="{{ url('/panel') }}">
                    <img src="{{ asset('favicon.png') }}" alt="...">
                </a>
                <!-- User menu (mobile) -->
                <div class="navbar-user d-lg-none">
                    <!-- Dropdown -->
                    <div class="dropdown">
                        <!-- Toggle -->
                        <a href="#" id="sidebarAvatar" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <div class="avatar bg-warning rounded-circle text-white">
                                <img alt="..." src="https://images.unsplash.com/photo-1579463148228-138296ac3b98?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=3&w=256&h=256&q=80">
                            </div>
                        </a>
                        <!-- Menu -->
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                            <div class="dropdown-item">
                                <span class="d-block text-sm text-muted mb-1">Signed in as</span>
                                <span class="d-block text-heading font-semibold">Heather Wright</span>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#">Action</a>
                            <a class="dropdown-item" href="#">Another action</a>
                            <a class="dropdown-item" href="#">Something else here</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#">Separated link</a>
                        </div>
                    </div>
                </div>
                <!-- Collapse -->
                <div class="collapse navbar-collapse" id="sidebarCollapse">
                    <!-- Navigation -->
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/panel') }}">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/links') }}">
                                <i class="bi bi-link"></i> Links
                            </a>
                        </li>
                    </ul>
                    <!-- Divider -->
                    <hr class="navbar-divider my-5 opacity-20">
                    <!-- Navigation -->
                    <ul class="navbar-nav mb-md-4">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('settings') }}">
                                <i class="bi bi-gear"></i> Settings
                            </a>
                        </li>
                    </ul>
                    <!-- Push content down -->
                    <div class="mt-auto"></div>
                    <!-- User (md) -->
                    <ul class="navbar-nav mb-5">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('auth/logout') }}">
                                <i class="bi bi-box-arrow-left"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Main content -->
        <div class="h-screen px-3 px-lg-4 flex-grow-1 overflow-y-lg-auto">
            <header>
                <div class="container-fluid">
                    <div class="py-6 border-bottom">
                        <!-- Page heading -->
                        <div>
                            <div class="row align-items-center">
                                <div class="col-md-6 col-12 mb-3 mb-md-0">
                                    <!-- Title -->
                                    <h1 class="h2 mb-0 ls-tight">@yield('header')</h1>
                                </div>
                                <!-- Actions -->
                                <div class="col-md-6 col-12 text-md-end">
                                    <div class="mx-n1">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <main class="py-10">
                <!-- Container -->
                <div class="container-fluid">
                    @yield('page')
                </div>
            </main>
        </div>
    </div>
@endsection
