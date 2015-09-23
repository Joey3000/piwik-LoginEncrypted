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
use Piwik\Piwik;
use Piwik\Settings\SystemSetting;

/**
 * Defines Settings for LoginEncrypted.
 */
class Settings extends \Piwik\Plugin\Settings
{
    /** @var SystemSetting */
    public $publicKey;

    /** @var SystemSetting */
    public $keyLength;

    /** @var SystemSetting */
    public $generateKeys;

    /**
     * Minimum key length, just to have some min. length
     */
    const MINIMUM_KEY_LENGTH = 512;

    protected function init()
    {
        $this->setIntroduction(Piwik::translate('LoginEncrypted_SettingsIntroduction'));

        // System setting --> textarea
        $this->createPublicKeySetting();

        // User setting --> textbox converted to int defining a validator and filter
        $this->createKeyLengthSetting();

        // User setting --> checkbox converted to bool
        $this->createGenerateKeysSetting();
    }

    private function createPublicKeySetting()
    {
        $this->publicKey = new SystemSetting('publicKey', Piwik::translate('LoginEncrypted_SettingsPublicKeyTitle'));
        $this->publicKey->readableByCurrentUser = true;
        $this->publicKey->uiControlType = static::CONTROL_TEXTAREA;
        $this->publicKey->uiControlAttributes = array('readonly' => 'readonly');
        $this->publicKey->description   = Piwik::translate('LoginEncrypted_SettingsPublicKeyDescription').Crypto::getKeyGenTime();
        $this->publicKey->defaultValue  = "None";

        $this->addSetting($this->publicKey);
    }

    private function createKeyLengthSetting()
    {
        $this->keyLength        = new SystemSetting('keyLength', Piwik::translate('LoginEncrypted_SettingsKeyLengthTitle'));
        $this->keyLength->readableByCurrentUser = true;
        $this->keyLength->type  = static::TYPE_INT;
        $this->keyLength->uiControlType = static::CONTROL_TEXT;
        $this->keyLength->description   = Piwik::translate('LoginEncrypted_SettingsKeyLengthDescription');
        $this->keyLength->inlineHelp    = Piwik::translate('LoginEncrypted_SettingsKeyLengthInlineHelp') . static::MINIMUM_KEY_LENGTH;
        $this->keyLength->defaultValue  = 2048;
        $this->keyLength->validate = function ($value, $setting) {
            if ($value < static::MINIMUM_KEY_LENGTH) {
                throw new Exception(Piwik::translate('LoginEncrypted_SettingsKeyLengthInvalid'));
            }
        };

        $this->addSetting($this->keyLength);
    }

    private function createGenerateKeysSetting()
    {
        $this->generateKeys        = new SystemSetting('generateKeys', Piwik::translate('LoginEncrypted_SettingsGenerateKeysTitle'));
        $this->generateKeys->readableByCurrentUser = true;
        $this->generateKeys->type  = static::TYPE_BOOL;
        $this->generateKeys->uiControlType = static::CONTROL_CHECKBOX;
        $this->generateKeys->description   = Piwik::translate('LoginEncrypted_SettingsGenerateKeysDescription');
        $this->generateKeys->defaultValue  = false;
        $this->generateKeys->transform = function ($value, $setting) {
            // check if check box got activated, and generate keys if so
            if ($value != $setting->defaultValue) {
                $publickey = Crypto::generateKeys($this->keyLength->getValue());
                $this->publicKey->setValue($publickey['e'] . ', ' . $publickey['n']);
            }
            return $setting->defaultValue; // reset checkbox
        };

        $this->addSetting($this->generateKeys);
    }
}
