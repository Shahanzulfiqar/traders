 {{-- Core JS --}}
 <script src="{{ asset('js/jquery1-3.4.1.min.js') }}"></script>
 <script src="{{ asset('js/popper1.min.js') }}"></script>
 <script src="{{ asset('js/bootstrap1.min.js') }}"></script>
 <script src="{{ asset('js/metisMenu.js') }}"></script>

 {{-- Plugins --}}
 <script src="{{ asset('vendors/datatable/js/jquery.dataTables.min.js') }}"></script>
 <script src="{{ asset('vendors/datatable/js/dataTables.responsive.min.js') }}"></script>
 <script src="{{ asset('vendors/apex_chart/apex-chart2.js') }}"></script>
 <script src="{{ asset('vendors/apex_chart/apex_dashboard.js') }}"></script>

 {{-- Theme JS --}}
 <script src="{{ asset('js/dashboard_init.js') }}"></script>
 <script src="{{ asset('js/custom.js') }}"></script>

 {{-- Page JS --}}
 @stack('js')
