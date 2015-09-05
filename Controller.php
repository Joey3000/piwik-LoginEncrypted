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
     * @param string $messageNoAccess Access error message
     * @param bool $infoMessage
     * @return string
     */
    public function login($messageNoAccess = null, $infoMessage = false)
    {
        $form = new FormLogin();

        // check if "encrypted" flag is set
        if (Common::getRequestVar('form_encrypted', 'false', 'string') == 'true') {
            // check if a password was submitted
            $password = $form->getSubmitValue('form_password');
            if($password !== null) {
                // decrypt and replace password
                // Note: Operating on _POST directly, as there doesn't seem to be another way. E.g., if
                //       value is replaced as in https://pear.php.net/manual/en/package.html.html-quickform2.qf-migration.php
                //       (using array_unshift()), it would not persist, as a "new" object instance will.
                //       re-read the sources (i.e. _POST).
                $password = Crypto::decrypt($password);
                if ($password === Crypto::DECRYPTION_FAILED) {
                    throw new Exception(Piwik::translate('LoginEncrypted_DecryptionError'));
                }
                $_POST['form_password'] = $password;
            }
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
     * @param none
     */
    public function resetPassword()
    {
        $form = new FormResetPassword();

        // check if "encrypted" flag is set
        if (Common::getRequestVar('form_encrypted', 'false', 'string') == 'true') {
            // check if a password was submitted
            $password = $form->getSubmitValue('form_password');
            if($password !== null) {
                // decrypt and replace password
                // Note: Operating on _POST directly, as there doesn't seem to be another way. E.g., if
                //       value is replaced as in https://pear.php.net/manual/en/package.html.html-quickform2.qf-migration.php
                //       (using array_unshift()), it would not persist, as a "new" object instance will.
                //       re-read the sources (i.e. _POST).
                $password = Crypto::decrypt($password);
                if ($password === Crypto::DECRYPTION_FAILED) {
                    throw new Exception(Piwik::translate('LoginEncrypted_DecryptionError'));
                }
                $_POST['form_password'] = $password;
            }

            // check if a password confirmation was submitted
            $passwordBis = $form->getSubmitValue('form_password_bis');
            if($passwordBis !== null) {
                // decrypt and replace password confirmation
                $passwordBis = Crypto::decrypt($passwordBis);
                if ($passwordBis === Crypto::DECRYPTION_FAILED) {
                    throw new Exception(Piwik::translate('LoginEncrypted_DecryptionError'));
                }
                $_POST['form_password_bis'] = $passwordBis;
            }
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
}
