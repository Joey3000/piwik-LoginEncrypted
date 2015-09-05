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

/**
 *
 */
class Auth extends \Piwik\Plugins\Login\Auth
{
    /**
     * Authentication module's name, e.g., "Login"
     *
     * @return string
     */
    public function getName()
    {
        return 'LoginEncrypted';
    }
}
