<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center justify-content-center">
                <img src="{{ asset('assets/img/larashiz-logo.jpg') }}" alt="Larashiz" width="32" height="32" class="rounded-circle mr-2 shadow-sm">
                <span>LARASHIZ</span>
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center justify-content-center">
                <img src="{{ asset('assets/img/larashiz-logo.jpg') }}" alt="Larashiz" width="28" height="28" class="rounded-circle shadow-sm">
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">Dashboard</li>
            <li class="nav-item dropdown {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown"><i class="fas fa-fire"></i><span>Dashboard</span></a>
                <ul class="dropdown-menu">
                    <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('dashboard') }}">Main Dashboard</a>
                    </li>
                </ul>
            </li>

            <li class="menu-header">Main Menu</li>
            {{-- <li class="{{ request()->routeIs('posts.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('posts.index') }}">
                    <i class="fas fa-newspaper"></i> <span>Posts</span>
                </a>
            </li> --}}

            <li class="nav-item dropdown {{ request()->routeIs(['departments.*', 'positions.*', 'employees.*', 'salary-components.*']) ? 'active' : '' }}">
                <a href="#" class="nav-link has-dropdown"><i class="fas fa-database"></i><span>Master Data</span></a>
                <ul class="dropdown-menu">
                    @if (auth()->user()?->hasRole('admin'))
                        <li class="{{ request()->routeIs('departments.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('departments.index') }}">Department</a>
                        </li>
                        <li class="{{ request()->routeIs('positions.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('positions.index') }}">Jabatan</a>
                        </li>
                    @endif
                    <li class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('employees.index') }}">Karyawan</a>
                    </li>
                    @if (auth()->user()?->hasRole('admin'))
                        <li class="{{ request()->routeIs('salary-components.*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('salary-components.index') }}">Komponen Gaji</a>
                        </li>
                    @endif
                </ul>
            </li>

            <li class="{{ request()->routeIs('cash-advances.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('cash-advances.index') }}">
                    <i class="fas fa-hand-holding-usd"></i> <span>Kasbon</span>
                </a>
            </li>

            <li class="{{ request()->routeIs('payrolls.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('payrolls.index') }}">
                    <i class="fas fa-money-check-alt"></i> <span>Penggajian</span>
                </a>
            </li>

            @if (auth()->user()?->hasRole('admin'))
                <li class="{{ request()->routeIs('company-profile.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('company-profile.index') }}">
                        <i class="fas fa-building"></i> <span>Profil Perusahaan</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->hasRole(['developer', 'admin']))
                <li class="{{ request()->routeIs('logs.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('logs.index') }}">
                        <i class="fas fa-history"></i> <span>Monitoring Log</span>
                    </a>
                </li>
            @endif
        </ul>
    </aside>
</div>
