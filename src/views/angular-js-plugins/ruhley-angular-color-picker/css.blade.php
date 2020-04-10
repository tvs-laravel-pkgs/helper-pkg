@if(config('helper-pkg.DEV'))
    <?php $helper_pkg_prefix = '/packages/abs/helper-pkg/src/';?>
@else
    <?php $helper_pkg_prefix = '';?>
@endif

<link rel="stylesheet" href="{{ URL::asset($helper_pkg_prefix.'public/plugins/ruhley-angular-color-picker/dist/angularjs-color-picker.min.css') }}" />
<!-- only include if you use bootstrap -->
<link rel="stylesheet" href="{{ URL::asset($helper_pkg_prefix.'public/plugins/ruhley-angular-color-picker/dist/themes/angularjs-color-picker-bootstrap.min.css') }}" />