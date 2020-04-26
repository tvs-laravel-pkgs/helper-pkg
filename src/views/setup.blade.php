@if(config('helper-pkg.DEV'))
    <?php $helper_pkg_prefix = '/packages/abs/helper-pkg/src';?>
@else
    <?php $helper_pkg_prefix = '';?>
@endif

<script type="text/javascript">
    if(typeof(base_url) == 'undefined'){
        var base_url = '{{url('')}}';
    }
    @if(isset($theme))
        var theme_url = '{{url('public/themes/'.$theme.'/')}}';
        var theme = '{{url('public/themes/'.$theme.'/')}}';
    @else
        var theme_url = '';
        var theme = '';
    @endif

    @if(Auth::user())
        var user_id = {{Auth::id()}};
    @endif

    var preset_filter_select_template_url = "{{asset($helper_pkg_prefix.'/public/themes/'.$theme.'/preset-filter-select.html')}}";
    var filter_btn_template_url = "{{asset($helper_pkg_prefix.'/public/themes/'.$theme.'/filter-btn.html')}}";
    var delete_confirm_modal_template_url = "{{asset($helper_pkg_prefix.'/public/themes/'.$theme.'/delete-confirm-modal.html')}}";
    var preset_filter_form_template_url = "{{asset($helper_pkg_prefix.'/public/themes/'.$theme.'/preset-filter-form.html')}}";




</script>
<script src="{{ asset($helper_pkg_prefix.'/public/angular/helper-pkg/angular-setup.js')}}"></script>
<script src="{{asset($helper_pkg_prefix.'/public/themes/common.js')}}"></script>

<script data-require="underscore.js@*" data-semver="1.5.1" src="https://underscorejs.org/underscore-min.js"></script>
<script type="text/javascript" src="{{URL::asset($helper_pkg_prefix.'/public/angular/helper-pkg/ng-shortcut.js?v=2')}}"></script>

<script type="text/javascript">
    var laravel_routes = [];
    @foreach(Route::getRoutes()->getRoutesByName() as $route_name => $route)
        laravel_routes['{{$route_name}}'] = url('{{$route->uri}}');
    @endforeach

</script>

<!-- CSRF TOKEN SETUP FOR AJAX CALLS -->
<script type="text/javascript">
    $.ajaxSetup({headers: {'X-CSRF-TOKEN': '{{csrf_token()}}'}});
</script>
