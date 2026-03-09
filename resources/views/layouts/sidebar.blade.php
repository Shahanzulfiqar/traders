<nav class="sidebar dark_sidebar">

<ul id="sidebar_menu">

@foreach($sidebarMenus as $menu)

<li>

@if($menu->children->count())

<a class="has-arrow" href="#" aria-expanded="false">

<div class="nav_icon_small">
<img src="{{ asset($menu->icon) }}" alt="">
</div>

<div class="nav_title">
<span>{{ $menu->name }}</span>
</div>

</a>

<ul>

@foreach($menu->children as $child)

<li>
<a href="{{ route($child->route) }}">
{{ $child->name }}
</a>
</li>

@endforeach

</ul>

@else

<a href="{{ route($menu->route) }}">

<div class="nav_icon_small">
<img src="{{ asset($menu->icon) }}" alt="">
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