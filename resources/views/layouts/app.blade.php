<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>

    {{-- CSS Files --}}
    @include('layouts.css')

    {{-- Page-specific CSS --}}
    @stack('css')
</head>

<body class="crm_body_bg">

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- Main Content --}}
    <div class="main_content dashboard_part">

        {{-- Navbar --}}
        @include('layouts.navbar')

        {{-- Page Content --}}
        <div class="main_content_iner">
            <div class="container-fluid p-0">
                @yield('content')
            </div>
        </div>

        {{-- Footer --}}
        @include('layouts.footer')

    </div>

    {{-- Chatbox --}}
    @include('layouts.chatbox')

    {{-- Back to Top --}}
    <div id="back-top" style="display:none;">
        <a title="Go to Top" href="#">
            <i class="ti-angle-up"></i>
        </a>
    </div>

    {{-- JS Scripts --}}
    @include('layouts.script')

    {{-- Page-specific JS --}}
    @stack('js')

</body>

</html>
