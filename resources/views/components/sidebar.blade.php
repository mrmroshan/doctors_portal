<aside class="main-sidebar sidebar-dark-primary elevation-4">
<a href="{{ url('/home') }}" class="brand-link">
        @if (Auth::user()->profile_pic)
        <img src="{{ asset('storage/profile_pics/' . Auth::user()->profile_pic) }}" alt="{{ Auth::user()->name }}" class="brand-image elevation-3" style="opacity: .8; width: 100px; height: 100px; object-fit: cover;">
        @else
            <img src="{{ Storage::url('images/o-logo.png') }}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        @endif
        <span class="brand-text font-weight-light">Odoo Portal</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ Storage::url('images/user.png') }}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">{{ Auth::user()->name ?? 'Guest' }}</a>
            </div>
        </div>

        <div class="form-inline">
            <div class="input-group" data-widget="sidebar-search">
                <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
                <div class="input-group-append">
                    <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                    </button>
                </div>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                @foreach($menuItems as $item)
                    @php
                        $hasSubmenu = !empty($item['submenu']);
                        $isActive = request()->is(trim($item['url'] ?? '', '/') . '*');
                        $hasActiveChild = $hasSubmenu && collect($item['submenu'])->contains(function ($subitem) {
                            return request()->is(trim($subitem['url'] ?? '', '/') . '*');
                        });
                    @endphp

                    @if (Auth::user()->isAdmin() || !in_array('admin', $item['roles'] ?? []))
                        <li class="nav-item {{ $hasSubmenu ? ($isActive || $hasActiveChild ? 'menu-open' : '') : '' }}">
                            <a href="{{ $item['url'] ?? '#' }}" class="nav-link {{ $isActive ? 'active' : '' }}">
                                <i class="nav-icon {{ $item['icon'] ?? 'fas fa-circle' }}"></i>
                                <p>
                                    {{ $item['text'] }}
                                    @if($hasSubmenu)
                                        <i class="right fas fa-angle-left"></i>
                                    @endif
                                </p>
                            </a>
                            @if($hasSubmenu)
                                <ul class="nav nav-treeview">
                                    @foreach($item['submenu'] as $subitem)
                                        @php
                                            $isSubItemActive = request()->is(trim($subitem['url'] ?? '', '/') . '*');
                                        @endphp
                                        <li class="nav-item">
                                            <a href="{{ url($subitem['url'] ?? '#') }}" class="nav-link {{ $isSubItemActive ? '' : '' }}"> <!--active-->
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>{{ $subitem['text'] }}</p>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>
    </div>
</aside>