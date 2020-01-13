@if(config('custom.PKG_DEV'))
    <?php $helper_pkg_prefix = '/packages/abs/helper-pkg/src';?>
@else
    <?php $helper_pkg_prefix = '';?>
@endif

<script data-require="underscore.js@*" data-semver="1.5.1" src="https://underscorejs.org/underscore-min.js"></script>
<script type="text/javascript" src="{{URL::asset($helper_pkg_prefix.'/public/angular/helper-pkg/ng-shortcut.js?v=2')}}"></script>

<script type="text/javascript">
function showErrorNoty(response){
	if(!response.success){

	}
}
</script>
