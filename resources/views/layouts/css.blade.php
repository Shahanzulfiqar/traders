{{-- Meta --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Title --}}
<title>@yield('title', config('app.name', 'Dashboard'))</title>

{{-- Favicon --}}
<link rel="icon" href="{{ asset('img/mini_logo.png') }}" type="image/png">

{{-- Core CSS --}}
<link rel="stylesheet" href="{{ asset('css/bootstrap1.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendors/themefy_icon/themify-icons.css') }}">
<link rel="stylesheet" href="{{ asset('vendors/font_awesome/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/metisMenu.css') }}">

{{-- Plugins --}}
<link rel="stylesheet" href="{{ asset('vendors/datatable/css/jquery.dataTables.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendors/datatable/css/responsive.dataTables.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendors/apex_chart/apex-chart.css') }}">

{{-- Theme Style --}}
<link rel="stylesheet" href="{{ asset('css/style1.css') }}">
<link rel="stylesheet" href="{{ asset('css/colors/default.css') }}" id="colorSkinCSS">

{{-- Page CSS --}}
@stack('css')
