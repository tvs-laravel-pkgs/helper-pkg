@extends($theme.'-pkg::demo/layouts/authed')
@section('app-head')
    @include('helper-pkg::angular-js/css')
@endsection

@section('content')
    <div ng-view></div>
@endsection

@section('footer_js')
    @include('helper-pkg::angular-js/js')
    @include('helper-pkg::setup')
    @include($theme.'-pkg::setup')
@endsection
