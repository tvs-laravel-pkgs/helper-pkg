var app = angular.module('app', ['ngCookies', 'ngSanitize', 'ui.select', 'ngRoute', 'ngMaterial', 'ngMessages', 'daterangepicker', 'moment-picker'], function($interpolateProvider) {
    $interpolateProvider.startSymbol('<%');
    $interpolateProvider.endSymbol('%>');
});

// app.config(function($locationProvider) {
//     // $locationProvider.html5Mode(true);
// })

var page_permissions = [];
var angular_routes = [];

app.directive('fileModel', ['$parse', function($parse) {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            var model = $parse(attrs.fileModel);
            var modelSetter = model.assign;

            element.bind('change', function() {
                scope.$apply(function() {
                    modelSetter(scope, element[0].files[0]);
                });
            });
        }
    };
}]);

/* <!-- Angular Moment Picker JS (timepicker JS) --> */
app.config(['momentPickerProvider', function(momentPickerProvider) {
    momentPickerProvider.options({
        /* hoursFormat: 'hh:mm', */
        today: false,
        locale: 'en',
        /* format: 'LT' */
    });
}]);

app.factory("HelperService", function($http) {
    return {
        hasPermission: function(permission) {
            return logged_user_permissions.indexOf(permission) != -1;
        },
    }
});

function createRoute($routeProvider, menu) {

    if (typeof(menu.angular_routes) != 'undefined') {
        $.each(menu.angular_routes, function(index, route) {
            $routeProvider.
            when(route.url, {
                template: route.template,
                title: route.browser_title
            });
            angular_routes[route.name] = route.url;
            page_permissions.push({
                url: route.url,
                permission: route.permission,
            });

        });
    }

    if (typeof(menu.sub_menus) !== 'undefined') {
        $.each(menu.sub_menus, function(index, sub_menu) {
            createRoute($routeProvider, sub_menu);
        });
    }

}

//ROUTES
app.config(['$routeProvider', function($routeProvider) {
    // $.each(menus, function(index, menu){
    //     createRoute($routeProvider,menu)
    // });
    $routeProvider.

    // //ORDER
    // when('/', {
    //     template: '<order-list></order-list>',
    //     title: 'Orders',
    // }).
    // when('/order/list', {
    //     template: '<order-list></order-list>',
    //     title: 'Orders',
    // }).
    // when('/order/view/:order_id', {
    //     template: '<order-view></order-view>',
    //     title: 'View Order',
    // }).
    //OTHER PAGES
    when('/permission-denied', {
        template: '<div class="text-center h1 alert alert-danger">Permission Denied!!!</div>'
    }).
    when('/page-not-found', {
        template: '<div class="text-center h1 alert alert-danger">Page Not Found!!!</div>'
    }).
    otherwise('/page-not-found');
}]);

app.directive("themeHeader", function() {
    return {
        templateUrl: theme_header_template_url,
        scope: {
            // customerInfo: '=info'
        },
    };
});

app.directive("themeBanner", function() {
    return {
        templateUrl: theme_banner_template_url,
        scope: {
            // customerInfo: '=info'
        },
    };
});


app.config(['uiSelectConfig', function(uiSelectConfig) {
    uiSelectConfig.theme = 'select2';
}]);

app.filter('propsFilter', function() {
    return function(items, props) {
        var out = [];

        if (angular.isArray(items)) {
            var keys = Object.keys(props);

            items.forEach(function(item) {
                var itemMatches = false;

                for (var i = 0; i < keys.length; i++) {
                    var prop = keys[i];
                    var text = props[prop].toLowerCase();
                    if (item[prop].toString().toLowerCase().indexOf(text) !== -1) {
                        itemMatches = true;
                        break;
                    }
                }

                if (itemMatches) {
                    out.push(item);
                }
            });
        } else {
            // Let the output be the input untouched
            out = items;
        }

        return out;
    };
});
app.run(function($rootScope, $location) {
    $rootScope.theme_url = theme_url;
    $rootScope.theme = theme_url;

    $rootScope.$on('$routeChangeStart', function(event, next, current) {
        $rootScope.loading = true;
        $("#modal-loading").modal({
            // backdrop: 'static',
            keyboard: true
        });

        console.log($rootScope.loading);
        var permission_required_for_url = $.grep(page_permissions, function(obj) {
            if (obj.url === $location.path()) {
                return obj;
            }
        });
        if (typeof(permission_required_for_url[0]) != 'undefined') {
            permission_required_for_url = permission_required_for_url[0].permission;
            if (logged_user_permissions.indexOf(permission_required_for_url) == -1) {
                // console.log('Permission denied')
                $location.path('/permission-denied');
            }
        }
    });

    $rootScope.$on('$routeChangeSuccess', function(event, current, previous) {
        $('.initial-focus').focus();
        $rootScope.title = current.$$route.title;
        $('#modal-loading').modal('hide');
    });
});
