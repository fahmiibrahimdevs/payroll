<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand"
             x-data="{ logoUrl: @js($sidebarCompanyProfile?->logo_url ?? asset('assets/img/larashiz-logo.jpg')), code: @js($sidebarCompanyProfile?->getDisplayCode() ?? 'LARASHIZ') }"
             x-on:company-profile-updated.window="logoUrl = $event.detail.logoUrl; code = $event.detail.code">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center justify-content-center" title="{{ $sidebarCompanyProfile?->name ?? 'Larashiz' }}">
                <img x-bind:src="logoUrl" x-bind:alt="code" src="{{ $sidebarCompanyProfile?->logo_url ?? asset('assets/img/larashiz-logo.jpg') }}" alt="{{ $sidebarCompanyProfile?->name ?? 'Larashiz' }}" width="32" height="32" class="mr-2 shadow-sm" style="object-fit: contain;">
                <span x-text="code">{{ $sidebarCompanyProfile?->getDisplayCode() ?? 'LARASHIZ' }}</span>
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm"
             x-data="{ logoUrl: @js($sidebarCompanyProfile?->logo_url ?? asset('assets/img/larashiz-logo.jpg')), code: @js($sidebarCompanyProfile?->getDisplayCode() ?? 'LARASHIZ') }"
             x-on:company-profile-updated.window="logoUrl = $event.detail.logoUrl; code = $event.detail.code">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center justify-content-center" title="{{ $sidebarCompanyProfile?->name ?? 'Larashiz' }}">
                <img x-bind:src="logoUrl" x-bind:alt="code" src="{{ $sidebarCompanyProfile?->logo_url ?? asset('assets/img/larashiz-logo.jpg') }}" alt="{{ $sidebarCompanyProfile?->name ?? 'Larashiz' }}" width="28" height="28" class="shadow-sm" style="object-fit: contain;">
            </a>
        </div>
        <ul class="sidebar-menu">
            @if (auth()->user()?->hasRole('developer'))
                <li class="menu-header">Developer</li>
                <li class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('logs.index') }}"><i class="fas fa-history"></i> <span>Monitoring Log</span></a>
                </li>
            @else
                <li class="menu-header">Dashboard</li>
                <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('dashboard') }}"><i class="fas fa-fire"></i> <span>Main Dashboard</span></a>
                </li>
            @endif

            @if (auth()->user()?->hasRole('developer'))
                {{-- Developer hanya menggunakan Monitoring Log sebagai halaman utama. --}}
            @elseif (auth()->user()?->hasRole('karyawan'))
                <li class="menu-header">Menu Karyawan</li>
                <li class="{{ request()->routeIs('cash-advances.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('cash-advances.index') }}">
                        <i class="fas fa-hand-holding-usd"></i> <span>Kasbon Saya</span>
                    </a>
                </li>
            @else
                <li class="menu-header">Main Menu</li>
                {{-- <li class="{{ request()->routeIs('posts.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('posts.index') }}">
                        <i class="fas fa-newspaper"></i> <span>Posts</span>
                    </a>
                </li> --}}

                <li class="nav-item dropdown {{ request()->routeIs(['departments.*', 'positions.*', 'employees.*', 'salary-components.*']) ? 'active' : '' }}">
                    <a href="#" class="nav-link has-dropdown"><i class="fas fa-database"></i><span>Master Data</span></a>
                    <ul class="dropdown-menu">
                        @if (auth()->user()?->hasRole(['admin', 'direktur']))
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
                        @if (auth()->user()?->hasRole(['admin', 'direktur']))
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

                @if (auth()->user()?->hasRole(['admin', 'direktur']))
                    <li class="{{ request()->routeIs('company-profile.*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('company-profile.index') }}">
                            <i class="fas fa-building"></i> <span>Profil Perusahaan</span>
                        </a>
                    </li>
                @endif

            @endif
        </ul>
    </aside>
</div>
