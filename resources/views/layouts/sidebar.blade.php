<nav class="sidebar dark_sidebar">

<ul id="sidebar_menu">

@foreach($sidebarMenus as $menu)

<li>

    {{-- Parent Menu With Children --}}
    @if($menu->children && $menu->children->count())

        <a class="has-arrow" href="#" aria-expanded="false">

            <div class="nav_icon_small">
                @if($menu->icon)
                    <img src="{{ asset($menu->icon) }}" alt="">
                @endif
            </div>

            <div class="nav_title">
                <span>{{ $menu->name }}</span>
            </div>

        </a>

        <ul>

            @foreach($menu->children as $child)

                <li>

                    <a href="{{ $child->route && Route::has($child->route) ? route($child->route) : '#' }}">

                        {{ $child->name }}

                    </a>

                </li>

            @endforeach

        </ul>

    {{-- Single Menu --}}
    @else

        <a href="{{ $menu->route && Route::has($menu->route) ? route($menu->route) : '#' }}">

            <div class="nav_icon_small">
                @if($menu->icon)
                    <img src="{{ asset($menu->icon) }}" alt="">
                @endif
            </div>

            <div class="nav_title">
                <span>{{ $menu->name }}</span>
            </div>

        </a>

    @endif

</li>

@endforeach

</ul>

</nav>