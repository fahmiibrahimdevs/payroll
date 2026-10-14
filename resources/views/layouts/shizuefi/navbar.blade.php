<nav class="navbar navbar-expand-lg main-navbar">
    <form class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
        </ul>
    </form>

    <ul class="navbar-nav navbar-right">
        @auth
            <!-- Notification Dropdown (Shizuefi Native Component) -->
            @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('direktur'))
                <li class="dropdown dropdown-list-toggle">
                    <a href="#" data-toggle="dropdown" class="nav-link notification-toggle nav-link-lg {{ !empty($navbarNotifications) ? 'beep' : '' }}" title="{{ !empty($navbarNotifications) ? count($navbarNotifications) . ' Hal Perlu Perhatian' : 'Notifikasi' }}">
                        <i class="far fa-bell"></i>
                    </a>
                    <div class="dropdown-menu dropdown-list dropdown-menu-right shadow-md" style="border-radius: 12px; border: 1px solid #e9edf2; overflow: hidden; width: 360px;">
                        <div class="dropdown-header d-flex justify-content-between align-items-center" style="background-color: #ffffff; border-bottom: 1px solid #f1f5f9; padding: 14px 18px;">
                            <span class="font-weight-600 text-dark" style="font-size: 13.5px;">Tindakan Perlu Perhatian</span>
                            @if (!empty($navbarNotifications))
                                <span class="badge badge-warning font-weight-600" style="font-size: 10.5px; border-radius: 9999px; padding: 3px 8px;">
                                    {{ count($navbarNotifications) }} Hal
                                </span>
                            @endif
                        </div>
                        <div class="dropdown-list-content dropdown-list-icons" style="max-height: 360px; overflow-y: auto;">
                            @forelse ($navbarNotifications ?? [] as $item)
                                <a href="{{ $item['url'] }}" class="dropdown-item dropdown-item-unread" style="padding: 12px 16px; border-bottom: 1px solid #f8fafc; text-decoration: none; display: flex; align-items: flex-start;">
                                    <div class="dropdown-item-icon {{ $item['type'] === 'danger' ? 'bg-danger' : ($item['type'] === 'warning' ? 'bg-warning' : ($item['type'] === 'success' ? 'bg-success' : 'bg-primary')) }} text-white mr-3" style="width: 36px; height: 36px; line-height: 36px; border-radius: 10px; font-size: 13px; text-align: center; flex-shrink: 0;">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </div>
                                    <div class="dropdown-item-desc" style="margin-left: 0; flex-grow: 1;">
                                        @if (!empty($item['title']))
                                            <div class="text-dark font-weight-600" style="font-size: 12px; line-height: 1.35; margin-bottom: 2px;">
                                                {{ $item['title'] }}
                                            </div>
                                            <div style="font-size: 11.5px; line-height: 1.45; font-weight: 400; color: #475569;">
                                                {{ $item['text'] }}
                                            </div>
                                        @else
                                            <div style="font-size: 12px; line-height: 1.45; font-weight: 400; color: #334155;">
                                                {{ $item['text'] }}
                                            </div>
                                        @endif
                                        <div class="time {{ $item['type'] === 'danger' ? 'text-danger' : ($item['type'] === 'warning' ? 'text-warning' : 'text-primary') }}" style="font-size: 11px; margin-top: 5px; font-weight: 600;">
                                            {{ $item['action_label'] }} &rarr;
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center py-4 px-3 text-muted">
                                    <i class="far fa-bell-slash mb-2" style="font-size: 26px; color: #94a3b8; display: block;"></i>
                                    <div class="font-weight-600 text-dark" style="font-size: 13px;">Semua Selesai!</div>
                                    <div class="small text-muted mt-1" style="font-size: 11.5px;">Tidak ada tindakan yang memerlukan perhatian saat ini.</div>
                                </div>
                            @endforelse
                        </div>
                        <div class="dropdown-footer text-center" style="background-color: #fafbfc; border-top: 1px solid #f1f5f9; padding: 10px 15px;">
                            <a href="{{ route('dashboard') }}" class="font-weight-600 text-primary" style="font-size: 12px; text-decoration: none;">
                                Buka Dashboard Utama <i class="fas fa-chevron-right ml-1" style="font-size: 9px;"></i>
                            </a>
                        </div>
                    </div>
                </li>
            @endif

            <!-- User Dropdown -->
            <li class="dropdown">
                <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                    <img alt="image" src="/assets/img/avatar/avatar-1.png" class="rounded-circle mr-1">
                    <div class="d-sm-none d-lg-inline-block">Hi, {{ auth()->user()->name }}</div>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <div class="dropdown-title">
                        Role: {{ auth()->user()->roles->pluck('name')->join(', ') ?: 'User' }}
                    </div>
                    <a href="{{ route('profile') }}" class="dropdown-item has-icon">
                        <i class="far fa-user"></i> Profil Saya
                    </a>
                    @if (auth()->user()->hasRole('admin'))
                        <a href="{{ route('admin.users') }}" class="dropdown-item has-icon">
                            <i class="fas fa-users-cog"></i> Kelola Pengguna
                        </a>
                    @endif
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                        @csrf
                        <button type="submit" class="dropdown-item has-icon text-danger border-0 bg-transparent w-100 text-left" style="cursor: pointer; display: flex; align-items: center; outline: none; font-size: 13px; font-weight: 500; padding: 10px 20px;">
                            <i class="fas fa-sign-out-alt mr-2"></i> Keluar (Logout)
                        </button>
                    </form>
                </div>
            </li>
        @else
            <li class="nav-item">
                <a href="{{ route('login') }}" class="nav-link nav-link-lg">Login</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('register') }}" class="nav-link nav-link-lg">Register</a>
            </li>
        @endauth
    </ul>
</nav>
