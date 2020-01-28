@if(config('custom.PKG_DEV'))
    <?php $helper_pkg_prefix = '/packages/abs/helper-pkg/src';?>
@else
    <?php $helper_pkg_prefix = '';?>
@endif



<script type="text/javascript">
    var base_url = '{{url('')}}';
</script>
<script src="{{ URL::asset($helper_pkg_prefix.'/public/angular/helper-pkg/angular-setup.js')}}"></script>

<script data-require="underscore.js@*" data-semver="1.5.1" src="https://underscorejs.org/underscore-min.js"></script>
<script type="text/javascript" src="{{URL::asset($helper_pkg_prefix.'/public/angular/helper-pkg/ng-shortcut.js?v=2')}}"></script>

<script type="text/javascript">
    function showCheckAllTabErrorNoty(){
        custom_noty('error', 'You have errors, Please check all tabs')
    }

    function showServerErrorNoty(){
        custom_noty('error', 'Something went wrong at server')
    }


    function showErrorNoty(res){
        custom_noty('error', res.error);

        var errors = '';
        for (var i in res.errors) {
            errors += '<li>' + res.errors[i] + '</li>';
        }
        if (errors) {
            custom_noty('error', errors);
        }
    }

    function url(url){
        return base_url+'/'+url;
    }

    var laravel_routes = [];
    @foreach(Route::getRoutes()->getRoutesByName() as $route_name => $route)
        laravel_routes['{{$route_name}}'] = url('{{$route->uri}}');
    @endforeach

</script>

<!-- CSRF TOKEN SETUP FOR AJAX CALLS -->
<script type="text/javascript">
    $.ajaxSetup({headers: {'X-CSRF-TOKEN': '{{csrf_token()}}'}});
</script>
