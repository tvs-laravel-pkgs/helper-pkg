@if(config('custom.PKG_DEV'))
    <?php $helper_pkg_prefix = '/packages/abs/helper-pkg/src';?>
@else
    <?php $helper_pkg_prefix = '';?>
@endif

<script data-require="underscore.js@*" data-semver="1.5.1" src="https://underscorejs.org/underscore-min.js"></script>
<script type="text/javascript" src="{{URL::asset($helper_pkg_prefix.'/public/angular/helper-pkg/ng-shortcut.js?v=2')}}"></script>

<script type="text/javascript">
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
</script>
