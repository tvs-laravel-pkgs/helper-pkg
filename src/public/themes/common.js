function showCheckAllTabErrorNoty() {
    custom_noty('error', 'You have errors, Please check all tabs')
}

function showServerErrorNoty() {
    custom_noty('error', 'Something went wrong at server')
}


function showErrorNoty(res) {

    console.log(res);
    if (res.error) {
        showNoty('error', res.error);
    }
    if (res.message) {
        showNoty('error', res.message);
    }

    var errors = '';
    // for (var i in res.errors) {
    //     errors += '<li>' + res.errors[i] + '</li>';
    // }
    angular.forEach(res.errors, function(error, key){
        errors += '<li>' + error + '</li>';
    });

    if (errors) {

        showNoty('error', errors);
    }
}

function showNoty(type, text) {
    $noty = new Noty({
        type: type,
        layout: 'topRight',
        text: text,
    }).show();
}

function url(url) {
    if (base_url.charAt(base_url.length) == '/') {
        return base_url + url;
    } else {
        return base_url + '/' + url;
    }
}

app.directive('presetFilterSelect', function() {
    return {
        templateUrl: preset_filter_select_template_url,
        controller: function() {
            var self = this;
            self.theme = theme;
        }
    }
});

app.directive('filterBtn', function() {
    return {
        templateUrl: filter_btn_template_url,
        controller: function() {
            var self = this;
            self.theme = theme;
        }
    }
});

app.directive('deleteConfirmModal', function() {
    return {
        templateUrl: delete_confirm_modal_template_url,
        controller: function() {
            var self = this;
            self.theme = theme;
        }
    }
});

app.directive('presetFilterForm', function() {
    return {
        templateUrl: preset_filter_form_template_url,
        controller: function() {
            var self = this;
            self.theme = theme;
        }
    }
});

$.ajaxSetup({
    beforeSend: function(xhr, settings) {
        var user = JSON.parse(localStorage.getItem('user'));
        if (user) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + user.token);
        }
    }
});