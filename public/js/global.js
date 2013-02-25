(function($) {

    $.getCsrfToken = function() {
        return $('meta[name=csrf-token]').attr('content');
    }

    $.setCsrfToken = function(val) {
        $('meta[name=csrf-token]').attr('content', val);
    }

    $.csrfProtection = function() {
        // append the token to all ajax requests
        $(document).ajaxSend(function(e, req, settings) {
            // append the token to the data being sent
            var tokenstr = "csrf-token=" + $.getCsrfToken();
            if (settings.type == "GET") {
                settings.url += (settings.url.indexOf("?") < 0) ? "?" + tokenstr : "&" + tokenstr;
            } else if (settings.data == undefined) {
                settings.data = tokenstr;
            } else if (settings.data.length > 0) {
                settings.data += "&" + tokenstr;
            }
        }).ajaxComplete(function(e, req, opt) {
            var token = req.getResponseHeader('csrf-token');
            if (!token) {
                window.location.reload();
            } else {
                jQuery.setCsrfToken(token);
            }
        });
    }

    // auto append the csrf token to all forms
    $('form').live('submit', function() {
        var $form = $(this);
        var $field = $(this).find('input[name=csrf-token]');
        if ($field.length) {
            $field.val($.getCsrfToken());
        } else {
            $form.append('<input type="hidden" name="csrf-token" value="' + $.getCsrfToken() + '">');
        }
    });

    $.errorHandler = function(err, form) {
        err = (err == undefined || err == '') ? {} : err;
        for (var name in err) {
            var message = err[name];
            var $field = form && form.length ? $('#' + form).find('#' + name) : $('#' + name);
            if (!$field.length) $field = form && form.length ? $('#' + form).find('*[name=' + name + ']') : $('*[name=' + name + ']');
            if ($field.length) {
                $field.addClass('error');
                $field.after('<span class="error"><span>' + message + '</span></span>');
            }
        }
    }

    // style all dropdowns
    $('.chzn').chosen();
})(jQuery);
