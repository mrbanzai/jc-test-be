/**
 * A jQuery extension to handle form errors. Takes JSON encoded data
 * and outputs a more or less sanitized error notice for the form field.
 */
if (jQuery) (function($) {
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

    // remove error box on focus
    $('form .error').live('focus', function() {
        $(this).removeClass('error');
    });
})(jQuery);