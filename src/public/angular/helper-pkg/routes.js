app.config(['$routeProvider', function($routeProvider) {

    $routeProvider.
    //CUSTOMER
    when('/helper-pkg/helper/list', {
        template: '<helper-list></helper-list>',
        title: 'Helpers',
    }).
    when('/helper-pkg/helper/add', {
        template: '<helper-form></helper-form>',
        title: 'Add Helper',
    }).
    when('/helper-pkg/helper/edit/:id', {
        template: '<helper-form></helper-form>',
        title: 'Edit Helper',
    });
}]);