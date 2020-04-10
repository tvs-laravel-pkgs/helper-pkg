@if(config('helper-pkg.DEV'))
    <?php $helper_pkg_prefix = '/packages/abs/helper-pkg/src/';?>
@else
    <?php $helper_pkg_prefix = '';?>
@endif

<script src="{{ URL::asset($helper_pkg_prefix.'public/plugins/tiny-color/dist/tinycolor-min.js')}}"></script>
