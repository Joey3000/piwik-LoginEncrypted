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

use Piwik\Plugin\Manager;

/**
 *
 */
class LoginEncrypted extends \Piwik\Plugins\Login\Login
{
    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "plugins/LoginEncrypted/javascripts/jsbn/jsbn.js";
        $jsFiles[] = "plugins/LoginEncrypted/javascripts/jsbn/prng4.js";
        $jsFiles[] = "plugins/LoginEncrypted/javascripts/jsbn/rng.js";
        $jsFiles[] = "plugins/LoginEncrypted/javascripts/jsbn/rsa.js";
        // Following file will be loaded from within login.js, as a workaround
        // for https://github.com/piwik/piwik/issues/8713
        //$jsFiles[] = "plugins/LoginEncrypted" . Crypto::PUBLIC_KEY_JS_FILE;
        $jsFiles[] = "plugins/LoginEncrypted/javascripts/login.js";
    }

    /**
     * Called on first plugin activation of a previously non-existent plugin.
     * I.e., on its first activation after having uploaded plug-in files.
     * The call is done before the subsequent call of activate().
     */
    public function install()
    {
        // force generation of new keys; existing keys will be overwritten
        $this->generateKeysAndStoreAll();
    }

    /**
     * Called on plugin uninstallation.
     */
    public function uninstall()
    {
        // remove private key table from DB
        Crypto::deletePrivateKeyTable();

        // Notes: - No DB problems exception catching done here (to prevent uninstallation on
        //          DB issues), as that is already done by the plugin manager for all plugins.
        //        - The public key stored in plugin settings and in the JavaScript file is
        //          left to Piwik to be removed on plugin files and settings removal.
    }

    /**
     * Called on plugin activation.
     */
    public function activate()
    {
        // deactivate default Login module, as both cannot be activated together
        if (Manager::getInstance()->isPluginActivated("Login") == true) {
            Manager::getInstance()->deactivatePlugin("Login");
        }

        // check if keys are missing in any of the storages and generate them if so
        $this->checkMissingKeysAndGenerate();
    }

    /**
     * Called on plugin deactivation.
     */
    public function deactivate()
    {
        // activate default Login module, as one of them is needed to access Piwik
        if (Manager::getInstance()->isPluginActivated("Login") == false) {
            Manager::getInstance()->activatePlugin("Login");
        }
    }

    /**
     * Checks if keys are missing in any of the storages and generates them if so.
     */
    protected function checkMissingKeysAndGenerate()
    {
        $settings = new Settings;
        if (($settings->publicKey->getValue() == $settings->publicKey->defaultValue) or // public key in plugin settins
            (Crypto::getKeyGenTime() == Crypto::KEY_GENERATION_TIME_UNKNOWN) or // private key in plugin DB table
            (!file_exists(__DIR__ . Crypto::PUBLIC_KEY_JS_FILE)) // public key in JS file
           ) {
            $this->generateKeysAndStoreAll();
        }
    }

    /**
     * Generates new keys and stores them in all the places necessary. Existing keys will be overwritten.
     */
    protected function generateKeysAndStoreAll()
    {
        $settings = new Settings;

        // generate keys and store the private key in DB, and the public key in the JS file
        // Notes: - Key length with be the one configured in the plugin settings, or the default
        //          from there if none has been configured yet.
        $publickey = Crypto::generateKeys($settings->keyLength->getValue());

        // store the public key in plugin settings as well, for viewing purposes
        $settings->publicKey->setValue($publickey['e'] . ', ' . $publickey['n']);
        $settings->save();
    }
}
