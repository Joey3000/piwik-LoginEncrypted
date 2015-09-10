/*!
 * LoginEncrypted plugin for Piwik, the free/libre analytics platform
 *
 * This file is a take-over of the equally named file of the Piwik core
 * Login plugin, modified for the needs of this plugin.
 *
 * @author  Joey3000 https://github.com/Joey3000
 * @link    https://github.com/Joey3000/piwik-LoginEncrypted
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function ($) {

    $(function() {
        var switchForm = function (fromFormId, toFormId, message, callback) {
            var fromLoginInputId = '#' + fromFormId + '_login',
                toLoginInputId = '#' + toFormId + '_login',
                toPasswordInputId = '#' + toFormId + '_password',
                fromLoginNavId = '#' + fromFormId + '_nav',
                toLoginNavId = '#' + toFormId + '_nav';

            if ($(toLoginInputId).val() === '') {
                $(toLoginInputId).val($(fromLoginInputId).val());
            }

            // hide the bottom portion of the login screen & show the password reset bits
            $('#' + fromFormId + ',#message_container').fadeOut(500, function () {
                // show lost password instructions
                $('#message_container').html(message);

                $(fromLoginNavId).hide();
                $(toLoginNavId).show();
                $('#' + toFormId + ',#message_container').fadeIn(500, function () {
                    // focus on login or password control based on whether a login exists
                    if ($(toLoginInputId).val() === '') {
                        $(toLoginInputId).focus();
                    }
                    else {
                        $(toPasswordInputId).focus();
                    }

                    if (callback) {
                        callback();
                    }
                });
            });
        };

        var encryptPassword = function (formId, passwordConfirmInputId) {
            // ecrypt password field
            var passwordInputId = '#' + formId + '_password';
            var rsa = new RSAKey();
            rsa.setPublic(LoginEncrypted_PublicKey.n, LoginEncrypted_PublicKey.e);
            // don't encrypt an empty field, as it may cause a decryption issue server-side
            if ($(passwordInputId).val() !== '') {
                $(passwordInputId).val(rsa.encrypt($(passwordInputId).val()));
            }

            // encrypt password confirmation field, if provided as argument and is not empty
            if (passwordConfirmInputId && ($('#' + passwordConfirmInputId).val() !== '')) {
                $('#' + passwordConfirmInputId).val(rsa.encrypt($('#' + passwordConfirmInputId).val()));
            }

            // set encryption flag
            $('<input>').attr({
                type: 'hidden',
                id: '#' + formId + '_encrypted',
                name: 'form_encrypted',
                value: 'true'
            }).appendTo('#' + formId);
        };

        // 'lost your password?' on click
        $('#login_form_nav').click(function (e) {
            e.preventDefault();
            switchForm('login_form', 'reset_form', $('#lost_password_instructions').html());
            // clear password fields, as they may contain previous encrypted values, which differ when encrypted
            $('input[type=password]').val('');
            return false;
        });

        // 'cancel' on click
        $('#reset_form_nav,#alternate_reset_nav').click(function (e) {
            e.preventDefault();
            $('#alternate_reset_nav').hide();
            switchForm('reset_form', 'login_form', '');
            return false;
        });

        // password reset on submit
        $('#reset_form_submit').click(function (e) {
            e.preventDefault();

            var ajaxDone = function (response) {
                $('.loadingPiwik').hide();

                var isSuccess = response.indexOf('id="login_error"') === -1,
                    fadeOutIds = '#message_container';
                if (isSuccess) {
                    fadeOutIds += ',#reset_form,#reset_form_nav';
                }

                $(fadeOutIds).fadeOut(300, function () {
                    if (isSuccess) {
                        $('#alternate_reset_nav').show();
                    }

                    $('#message_container').html(response).fadeIn(300);
                });
            };

            // ecrypt password fields
            encryptPassword('reset_form', 'reset_form_password_bis');

            $('.loadingPiwik').show();

            // perform reset password request
            $.ajax({
                type: 'POST',
                url: 'index.php',
                dataType: 'html',
                async: true,
                error: function () { ajaxDone('<div id="login_error"><strong>HTTP Error</strong></div>'); },
                success: ajaxDone,	// Callback when the request succeeds
                data: $('#reset_form').serialize()
            });

            return false;
        });

        // 'submit' on click
        $('#login_form_submit').click(function (e) {
            encryptPassword('login_form');

            return true; // do not block submission
        });

        // disable password autocomplete, to prevent previously encrypted passwords being autocompleted instead of clear text ones
        $('#login_form_password').attr('autocomplete', 'off');

        $('#login_form_login').focus();
    });

}(jQuery));
