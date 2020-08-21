var app = angular.module('app', [
    'ngCookies',
    'ngSanitize',
    'ui.select',
    'ngRoute',
    'ngMaterial',
    'ngMessages',
    'daterangepicker',
    'moment-picker'
], function($interpolateProvider) {
    $interpolateProvider.startSymbol('<%');
    $interpolateProvider.endSymbol('%>');
});

// app.config(function($locationProvider) {
//     // $locationProvider.html5Mode(true);
// })

angular.module('app').factory('RequestSvc', [
    '$http',
    '$rootScope',
    '$log',
    '$window',
    '$q',
    'HelperService',
    function(
        $http,
        $rootScope,
        $log,
        $window,
        $q,
        HelperService
    ) {

        // let apiPath = $window.api_url;
        let apiPath = base_url;


        function appendTransform(defaults, transform) {
            // We can't guarantee that the default transformation is an array
            defaults = angular.isArray(defaults) ? defaults : [defaults];

            // Append the new transformation to the defaults
            return defaults.concat(transform);
        }

        return {
            get: function(url, params, transformResponse) {
                // does not accept unformatted momentjs objects
                // could potentially add an alternative get method or an option to not use $.param
                var query = $.param(angular.extend({}, params));

                var deferred = $q.defer();
                let canceller = $q.defer();
                url = apiPath + url + '?' + query;
                transformResponse = transformResponse || function(value) {
                    return value;
                };
                $http({
                        url: url,
                        method: 'GET',
                        headers: {
                            Authorization: HelperService.isLoggedIn() ? 'Bearer ' + HelperService.getLoggedUser().token : null,
                        },
                        transformResponse: appendTransform($http.defaults.transformResponse, function(value) {
                            return transformResponse(value);
                        }),
                        timeout: canceller.promise,
                    })
                    .then(function(response) {
                        if (response.status === -1) {
                            // timed out or cancelled
                            deferred.reject();
                            return;
                        }
                        if (response.data.success) {
                            $rootScope.$broadcast('onResetIdleTimeout');
                            deferred.resolve(response);
                        } else {
                            deferred.reject(response.data.errors);
                        }
                    }, function(error) {
                        deferred.reject('A generic API error occured: ' + error.data.error.message);
                    });

                deferred.promise.cancel = () => {
                    canceller.resolve();
                };

                return deferred.promise;
            },
            post: function(url, data, transformResponse) {
                var deferred = $q.defer();
                transformResponse = transformResponse || function(value) {
                    return value;
                };
                $http({
                        url: apiPath + url,
                        method: 'POST',
                        data: data,
                        headers: {
                            Authorization: HelperService.isLoggedIn() ? 'Bearer ' + HelperService.getLoggedUser().token : null,
                            // Authorization: angular.isObject($localStorage.authToken) ? $localStorage.authToken.token : null,
                        },
                        transformResponse: appendTransform($http.defaults.transformResponse, function(value) {
                            return transformResponse(value);
                        })
                    })
                    .then(function(response) {
                        if (response.data.success) {
                            $rootScope.$broadcast('onResetIdleTimeout');
                            deferred.resolve(response);
                        } else {
                            deferred.reject(response.data.errors);
                        }
                    }, function(error) {
                        deferred.reject('A generic API error occured: ' + error.data.error.message);
                    });

                return deferred.promise;
            }
        };

    }
]);

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

app.factory('$localstorage', ['$window', function($window) {
    return {
        set: function(key, value) {
            $window.localStorage[key] = value;
        },
        get: function(key, defaultValue) {
            return $window.localStorage[key] || defaultValue || false;
        },
        setObject: function(key, value) {
            $window.localStorage[key] = JSON.stringify(value);
        },
        getObject: function(key, defaultValue) {
            if($window.localStorage[key] != undefined){
                return JSON.parse($window.localStorage[key]);
            }else{
                return defaultValue || false;
            }
        },
        remove: function(key){
            $window.localStorage.removeItem(key);
        },
        clear: function(){
            $window.localStorage.clear();
        }
    }
}]);

app.factory("HelperService", function($http, $cookies) {
    return {
        hasPermission: function(permission) {
            return logged_user_permissions.indexOf(permission) != -1;
        },
        calculateTaxAndTotal: function(entity, is_same_state, handling = null) {
            entity.net_amount = parseFloat(entity.qty) * parseFloat(entity.rate);
            entity.tax_total = 0;
            entity.total_amount = 0;

            entity.taxes = [];
            if (entity.tax_code) {
                if (is_same_state) {
                    console.log(is_same_state);
                    angular.forEach(entity.tax_code.taxes, function(tax) {
                        if (tax.type_id == 1160) {
                            //Within State Taxes Only
                            tax.pivot.amount = tax.amount = parseFloat(entity.net_amount) * parseFloat(tax.pivot.percentage) / 100;
                            entity.tax_total += tax.amount;
                            entity.taxes.push(tax);
                        }
                    });
                } else {
                    angular.forEach(entity.tax_code.taxes, function(tax) {
                        if (tax.type_id == 1161) {
                            //Inter State Taxes Only
                            tax.pivot.amount = tax.amount = parseFloat(entity.net_amount) * parseFloat(tax.pivot.percentage) / 100;
                            entity.tax_total += tax.amount;
                            entity.taxes.push(tax);
                        }
                    });
                }
            }
            entity.total_amount = parseFloat(entity.net_amount) + parseFloat(entity.tax_total);
            if (handling != null) {
                entity.total_amount = entity.total_amount + parseFloat(entity.handling_charge);
            }

        },

        calculateTotal: function(items) {
            var total = 0;
            angular.forEach(items, function(item) {
                total += parseFloat(item.total_amount);
            });
            return total;
        },


        getCurrentDate: function() {
            var today = new Date();
            var dd = today.getDate();
            var mm = today.getMonth() + 1;
            var yyyy = today.getFullYear();
            if (dd < 10) {
                dd = '0' + dd;
            }

            if (mm < 10) {
                mm = '0' + mm;
            }
            current_date = dd + '-' + mm + '-' + yyyy;
            return current_date;
        },
        isLoggedIn: function() {
            var user = JSON.parse(localStorage.getItem('user'));
            if (!user) {
                window.location = base_url + '/logout';
                return;
                // return false;
            } else {
                return true;
            }
        },
        hasPerm: function(permission) {
            var user = JSON.parse(localStorage.getItem('user'));
            if (!user) {
                return false;
            }
            return user.permissions.indexOf(permission) != -1;
        },
        getLoggedUser: function() {
            return JSON.parse(localStorage.getItem('user'));
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

    //OTHER PAGES
    when('/permission-denied', {
        template: '<div class="text-center h1 alert alert-danger">Permission Denied!!!</div>'
    }).
    when('/page-not-found', {
        template: '<div class="text-center h1 alert alert-danger">Page Not Found!!!</div>'
    }).
    otherwise('/');
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
