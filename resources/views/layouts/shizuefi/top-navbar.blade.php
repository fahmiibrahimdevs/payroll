<nav class="navbar navbar-secondary navbar-expand-lg">
    <div class="container">
        <ul class="navbar-nav">
            <li class="nav-item dropdown {{ request()->routeIs('dashboard') || request()->is('shizuefi/index*') ? 'active' : '' }}">
                <a href="#" data-toggle="dropdown" class="nav-link has-dropdown"><i class="fas fa-fire"></i><span>Dashboard</span></a>
                <ul class="dropdown-menu">
                    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"><a href="{{ route('dashboard') }}" class="nav-link">Main Dashboard</a></li>
                    @if (auth()->user()?->hasRole('admin'))
                        <li class="nav-item {{ request()->is('shizuefi/index-0') ? 'active' : '' }}"><a href="/shizuefi/index-0" class="nav-link">General Dashboard</a></li>
                        <li class="nav-item {{ request()->is('shizuefi/index') ? 'active' : '' }}"><a href="/shizuefi/index" class="nav-link">Ecommerce Dashboard</a></li>
                    @endif
                </ul>
            </li>

            @auth
                @if (auth()->user()->hasRole('admin'))
                    <li class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                        <a href="{{ route('admin.users') }}" class="nav-link"><i class="fas fa-users-cog"></i><span>Kelola Pengguna</span></a>
                    </li>
                @endif
            @endauth

            {{-- Grup link demo template shizuefi: route-nya admin-only, jadi sembunyikan untuk role lain --}}
            @if (auth()->user()?->hasRole('admin'))
            <li class="nav-item dropdown">
                <a href="#" data-toggle="dropdown" class="nav-link has-dropdown"><i class="fas fa-columns"></i><span>Layouts</span></a>
                <ul class="dropdown-menu">
                    <li class="nav-item"><a href="/shizuefi/layout-default" class="nav-link">1. Default Layout</a></li>
                    <li class="nav-item"><a href="/shizuefi/layout-transparent" class="nav-link">2. Transparent Sidebar</a></li>
                    <li class="nav-item active"><a href="/shizuefi/layout-top-navigation" class="nav-link">3. Top Navigation</a></li>
                    <li class="nav-item"><a href="/shizuefi/blank" class="nav-link">Blank Page</a></li>
                </ul>
            </li>

            <li class="nav-item dropdown">
                <a href="#" data-toggle="dropdown" class="nav-link has-dropdown"><i class="fas fa-th"></i><span>Bootstrap</span></a>
                <ul class="dropdown-menu">
                    <li class="nav-item"><a href="/shizuefi/bootstrap-alert" class="nav-link">Alert</a></li>
                    <li class="nav-item"><a href="/shizuefi/bootstrap-badge" class="nav-link">Badge</a></li>
                    <li class="nav-item"><a href="/shizuefi/bootstrap-buttons" class="nav-link">Buttons</a></li>
                    <li class="nav-item"><a href="/shizuefi/bootstrap-card" class="nav-link">Card</a></li>
                    <li class="nav-item"><a href="/shizuefi/bootstrap-modal" class="nav-link">Modal</a></li>
                    <li class="nav-item"><a href="/shizuefi/bootstrap-table" class="nav-link">Table</a></li>
                    <li class="nav-item"><a href="/shizuefi/bootstrap-typography" class="nav-link">Typography</a></li>
                </ul>
            </li>

            <li class="nav-item dropdown">
                <a href="#" data-toggle="dropdown" class="nav-link has-dropdown"><i class="fas fa-th-large"></i><span>Components</span></a>
                <ul class="dropdown-menu">
                    <li class="nav-item"><a href="/shizuefi/components-article" class="nav-link">Article</a></li>
                    <li class="nav-item"><a href="/shizuefi/components-avatar" class="nav-link">Avatar</a></li>
                    <li class="nav-item"><a href="/shizuefi/components-pricing" class="nav-link">Pricing</a></li>
                    <li class="nav-item"><a href="/shizuefi/components-statistic" class="nav-link">Statistic</a></li>
                    <li class="nav-item"><a href="/shizuefi/components-table" class="nav-link">Table</a></li>
                    <li class="nav-item"><a href="/shizuefi/components-user" class="nav-link">User</a></li>
                    <li class="nav-item"><a href="/shizuefi/components-wizard" class="nav-link">Wizard</a></li>
                </ul>
            </li>

            <li class="nav-item dropdown">
                <a href="#" data-toggle="dropdown" class="nav-link has-dropdown"><i class="far fa-file-alt"></i><span>Forms</span></a>
                <ul class="dropdown-menu">
                    <li class="nav-item"><a href="/shizuefi/forms-advanced-form" class="nav-link">Advanced Form</a></li>
                    <li class="nav-item"><a href="/shizuefi/forms-editor" class="nav-link">Editor</a></li>
                    <li class="nav-item"><a href="/shizuefi/forms-validation" class="nav-link">Validation</a></li>
                </ul>
            </li>

            <li class="nav-item dropdown">
                <a href="#" data-toggle="dropdown" class="nav-link has-dropdown"><i class="fas fa-plug"></i><span>Modules</span></a>
                <ul class="dropdown-menu">
                    <li class="nav-item"><a href="/shizuefi/modules-chartjs" class="nav-link">ChartJS</a></li>
                    <li class="nav-item"><a href="/shizuefi/modules-datatables" class="nav-link">DataTables</a></li>
                    <li class="nav-item"><a href="/shizuefi/modules-sweet-alert" class="nav-link">Sweet Alert</a></li>
                    <li class="nav-item"><a href="/shizuefi/modules-toastr" class="nav-link">Toastr</a></li>
                    <li class="nav-item"><a href="/shizuefi/modules-calendar" class="nav-link">Calendar</a></li>
                </ul>
            </li>

            <li class="nav-item dropdown">
                <a href="#" data-toggle="dropdown" class="nav-link has-dropdown"><i class="fas fa-bicycle"></i><span>Features</span></a>
                <ul class="dropdown-menu">
                    <li class="nav-item"><a href="/shizuefi/features-activities" class="nav-link">Activities</a></li>
                    <li class="nav-item"><a href="/shizuefi/features-posts" class="nav-link">Posts</a></li>
                    <li class="nav-item"><a href="/shizuefi/features-profile" class="nav-link">Profile</a></li>
                    <li class="nav-item"><a href="/shizuefi/features-settings" class="nav-link">Settings</a></li>
                </ul>
            </li>
            @endif
        </ul>
    </div>
</nav>
