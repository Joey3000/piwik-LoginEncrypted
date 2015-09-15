<?php
/**
 * LoginEncrypted plugin for Piwik, the free/libre analytics platform
 *
 * @author  Joey3000 https://github.com/Joey3000
 * @link    https://github.com/Joey3000/piwik-LoginEncrypted
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\LoginEncrypted;

use Exception;
use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugins\Login\FormLogin;
use Piwik\Plugins\Login\FormResetPassword;

// Following "uses" are needed just for the parent copy-paste functions (see function comments below)
use Piwik\Log;
use Piwik\Nonce;

/**
 * Login controller
 *
 */
class Controller extends \Piwik\Plugins\Login\Controller
{
    /**
     * @var PasswordResetter This is just a copy-paste of the parent's member.
     * It's sole reason for existence here is the fact that the parent has it
     * defined as "private". See https://github.com/piwik/piwik/pull/8681 for
     * information.
     */
    protected $passwordResetter;

    /**
     * @var Auth This is just a copy-paste of the parent's member.
     * It's sole reason for existence here is the fact that the parent has it
     * defined as "private". See https://github.com/piwik/piwik/pull/8681 for
     * information.
     */
    protected $auth;

    /**
     * @var SessionInitializer This is just a copy-paste of the parent's member.
     * It's sole reason for existence here is the fact that the parent has it
     * defined as "private". See https://github.com/piwik/piwik/pull/8681 for
     * information.
     */
    protected $sessionInitializer;

    /**
     * Login form. Decrypts received password value and then calls
     * the original (parent class) function for regular processing.
     *
     * @see the parent class function for parameters and return value
     */
    public function login($messageNoAccess = null, $infoMessage = false)
    {
        $form = new FormLogin();

        // check if "encrypted" flag is set
        if (Common::getRequestVar('form_encrypted', 'false', 'string') == 'true') {
            $this->decryptPassword($form, 'form_password');
        }

        // call the original function on the decrypted values
        return parent::login($messageNoAccess, $infoMessage);
    }

    /**
     * This is just a copy-paste of the parent's member.
     * It's sole reason for existence here is the fact that the parent has it
     * defined as "private". See https://github.com/piwik/piwik/pull/8681 for
     * information.
     *
     * @param View $view
     */
    protected function configureView($view)
    {
        $this->setBasicVariablesView($view);

        $view->linkTitle = Piwik::getRandomTitle();

        // crsf token: don't trust the submitted value; generate/fetch it from session data
        $view->nonce = Nonce::getNonce('Login.login');
    }

    /**
     * Reset password action. Decrypts received password values and then calls
     * the original (parent class) function for regular processing.
     *
     * @see the parent class function for parameters and return value
     */
    public function resetPassword()
    {
        $form = new FormResetPassword();

        // check if "encrypted" flag is set
        if (Common::getRequestVar('form_encrypted', 'false', 'string') == 'true') {
            $this->decryptPassword($form, 'form_password');
            $this->decryptPassword($form, 'form_password_bis');
        }

        // call the original function on the decrypted values
        return parent::resetPassword();
    }

    /**
     * This is just a copy-paste of the parent's member.
     * It's sole reason for existence here is the fact that the parent has it
     * defined as "private". See https://github.com/piwik/piwik/pull/8681 for
     * information.
     *
     * @param QuickForm2 $form
     * @return array Error message(s) if an error occurs.
     */
    protected function resetPasswordFirstStep($form)
    {
        $loginMail = $form->getSubmitValue('form_login');
        $password  = $form->getSubmitValue('form_password');

        try {
            $this->passwordResetter->initiatePasswordResetProcess($loginMail, $password);
        } catch (Exception $ex) {
            Log::debug($ex);

            return array($ex->getMessage());
        }

        return null;
    }

    /**
     * Gets the password from the HTML form, decrypts it and writes the decrypted
     * value back into the _POST request.
     * Note: Writing to _POST directly, as there doesn't seem to be another way. E.g., if
     *       value is replaced as in https://pear.php.net/manual/en/package.html.html-quickform2.qf-migration.php
     *       (using array_unshift()), it would not persist, as a "new" object instance
     *       will re-read its sources (i.e. _POST).
     *
     * @param QuickForm2 $form The HTML form which the password is part of
     * @param string $passwordInputId The input ID of the password field on the HTML form
     * @throws Exception if decryption fails
     */
    protected function decryptPassword($form, $passwordInputId)
    {
        $password = $form->getSubmitValue($passwordInputId);

        // check if a password was submitted
        // Note: Compare loosely, so both, "" (password input empty; forms send strings)
        //       and NULL (password input not sent - see QuickForm2->getSubmitValue())
        //       are covered - see https://secure.php.net/manual/en/types.comparisons.php
        if($password != "") {
            // decrypt and replace password
            $password = Crypto::decrypt($password);
            if ($password === Crypto::DECRYPTION_FAILED) {
                throw new Exception(Piwik::translate('LoginEncrypted_DecryptionError'));
            }
            $_POST[$passwordInputId] = $password;
        }
    }
}
