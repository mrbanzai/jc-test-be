var CKEditors = [];

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

	// run csrf protection
	$.csrfProtection();

    // initialize any CK Editors
    initCKEditors();

    // remove error box on focus
    $('form .error').live('focus', function() {
        $(this).removeClass('error');
    });

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

})(jQuery);

/**
 * Checks for any CK editors that may be on the page and initializes them.
 * Assumes the following:
 *
 * <script type="text/javascript" src="/public/js/ckeditor/ckeditor.js"></script>
 * <script type="text/javascript" src="/public/js/ckeditor/adapters/jquery.js"></script>
 */
function initCKEditors() {
    if (window.CKEDITOR) {
        if (!CKEDITOR.env.isCompatible) {
			showCompatibilityMsg();
        } else {
            var $editor = $('.editor');
            var $editor_sm = $('.editor-sm');
            var $editor_lg = $('.editor-lg');
            if ($editor_sm.length) createCKEditor($editor_sm, 'Basic', '450px', '180px');
            if ($editor.length) createCKEditor($editor, 'Medium', '450px', '180px');
            if ($editor_lg.length) createCKEditor($editor_lg, 'Full', '450px', '180px');
        }
    }
}

/**
 * Create an instance of the CKEditor.
 */
function createCKEditor($elem, type, width, height) {

    $elem.each(function() {
        // load and store
        CKEditors[CKEditors.length] = $(this).ckeditor(
            // callback
            function() { },
            // options
            { toolbar: type }
        );
    });

}

/**
 * CKEditor compatibility warning message.
 */
function showCompatibilityMsg() {
    var env = CKEDITOR.env;
    var html = "Your browser is not compatible with CKEditor.\n\n";
    var browsers =
    {
        gecko : 'Firefox 2.0',
        ie : 'Internet Explorer 6.0',
        opera : 'Opera 9.5',
        webkit : 'Safari 3.0'
    };

    var alsoBrowsers = '';
    for (var key in env) {
        if (browsers[key]) {
            if (env[key]) {
                html += " CKEditor is compatible with ' + browsers[ key ] + ' or higher.\n";
            } else {
                alsoBrowsers += browsers[ key ] + '+, ';
            }
        }
    }
    alsoBrowsers = alsoBrowsers.replace(/\+,([^,]+), $/, "+ and $1\n");
    html += ' It is also compatible with ' + alsoBrowsers + ".\n";
    alert(html);
}