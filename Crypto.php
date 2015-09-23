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

require_once __DIR__ . '/phpseclib/Crypt/Hash.php';
require_once __DIR__ . '/phpseclib/Crypt/Random.php';
require_once __DIR__ . '/phpseclib/Crypt/RSA.php';
require_once __DIR__ . '/phpseclib/Math/BigInteger.php';

use Exception;
use Piwik\AssetManager;
use Piwik\Common;
use Piwik\Db;
use Piwik\Piwik;

use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;

/**
 * Handles decryption and key generation.
 */
class Crypto
{
    /**
     * Database table used for private key storage (without prefix)
     */
    const KEY_TABLE = 'login_encrypted_keys';

    /**
     * Id of the row in the key database table used for the private key
     */
    const KEY_TABLE_PRIVATE_KEY_ID = 1;

    /**
     * This is the value the generation timestamp is initialized with in the DB.
     * Also, this is the return value of getKeyGenTime() indicating that the key generation
     * time is unknown (e.g. none have been generated so far, or there is a DB access issue).
     */
    const KEY_GENERATION_TIME_UNKNOWN = '2000-01-01 00:00:00';

    /**
     * Client JavaScript file (with path relative to plugin root) where the public key will be written
     */
    const PUBLIC_KEY_JS_FILE = '/javascripts/public_key.js';

    /**
     * Decryption return value (i.e. return value of RSA::decrypt()) indicating failure
     */
    const DECRYPTION_FAILED = false;

    /**
     * Descrypts encrypted text, if provided. Otherwise (false, null, "", etc.)
     * passes the input value to the output.
     *
     * @param string $text Text to decrypt
     * @throws Exception if decryption fails
     * @return string Decrypted text
     */
    public static function decrypt($text)
    {
        // check if a ciphertext was submitted
        // Note: Compare loosely, so both, "" (password input empty; forms send strings)
        //       and "password input not sent" are covered - see
        //       https://secure.php.net/manual/en/types.comparisons.php
        if ($text != "") {
            $rsa = new RSA();
            $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
            $rsa->loadKey(static::getPrivateKey());
            $s = new BigInteger($text, 16);

            // prevent library user notices being shown to the user
            set_error_handler(function() { /* ignore errors */ });

            $text = $rsa->decrypt($s->toBytes());

            restore_error_handler();

            if ($text === Crypto::DECRYPTION_FAILED) {
                throw new Exception(Piwik::translate('LoginEncrypted_DecryptionError') .
                                    ' (Backtrace: ' . static::getBacktrace() . ')'
                                   );
            }
        }

        return $text;
    }

    /**
     * Generates a new key pair. Then stores the private key and returns the public key.
     *
     * @param int $keylength Desired key length in bits
     * @return array The public key as an associative array containing its exponent "e"
     *               and modulus "n" in hexadecimal representation.
     */
    public static function generateKeys($keylength)
    {
        $rsa = new RSA();
        $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_RAW);
        $keys = $rsa->createKey($keylength);

        static::deletePrivateKeyTable(); // delete, if exists - future-proofing for layout changes
        static::createPrivateKeyTable(); // create, if doesn't exist
        static::storePrivateKey($keys['privatekey']);

        $e = new BigInteger($keys['publickey']['e'], 10);
        $n = new BigInteger($keys['publickey']['n'], 10);
        $e = $e->toHex();
        $n = $n->toHex();

        $publickey = array('e'=>$e, 'n'=>$n);
        static::writePublicKeyJS($publickey);

        return $publickey;
    }

    /**
     * Creates DB table for private key storage, if it does not exist yet.
     *
     * @throws Exception If there is an error in the SQL.
     */
    protected static function createPrivateKeyTable()
    {
        try {
            // Note: Not using any constraings (e.g. NOT NULL, etc.) here, to be MySQL
            //       version independent. The version will automatically assume
            //       whichever suits it better according to value types.
            $sql = "CREATE TABLE " . Common::prefixTable(static::KEY_TABLE) . " (
                        id TINYINT(1) UNSIGNED DEFAULT " . static::KEY_TABLE_PRIVATE_KEY_ID . ",
                        key_content TEXT,
                        gen_time TIMESTAMP DEFAULT '" . static::KEY_GENERATION_TIME_UNKNOWN . "',
                        PRIMARY KEY ( id )
                    ) DEFAULT CHARSET=utf8 ";
            Db::exec($sql);
        } catch (Exception $e) {
            // ignore error if table already exists (1050 code is for 'table already exists')
            if (!Db::get()->isErrNo($e, '1050')) {
                throw $e;
            }
        }
    }

    /**
     * Deletes the DB table for private key storage, if exists.
     *
     * @throws Exception If there is an error in the SQL.
     */
    public static function deletePrivateKeyTable()
    {
        try {
            Db::dropTables(Common::prefixTable(static::KEY_TABLE));
        } catch (Exception $e) {
            // ignore error if table does not exist (1051 code is for 'unknown table')
            if (!Db::get()->isErrNo($e, '1051')) {
                throw $e;
            }
        }
    }

    /**
     * Stores the private key into the database.
     *
     * @param string $key Key to store
     */
    protected static function storePrivateKey($key)
    {
        // insert key if none exists, or replace if it already does
        $sql = "INSERT INTO " . Common::prefixTable(static::KEY_TABLE) . " (
                    id,
                    key_content,
                    gen_time
                ) VALUES (?, ?, NOW())
                  ON DUPLICATE KEY UPDATE
                    key_content = VALUES(key_content),
                    gen_time = VALUES(gen_time)";
        Db::get()->query($sql, array(static::KEY_TABLE_PRIVATE_KEY_ID, $key));
    }

    /**
     * Writes the public key to the client JavaScript file.
     *
     * @param array $key Key to write (contains exponent "e" and modulus "n")
     */
    protected static function writePublicKeyJS($key)
    {
        // write the key to the file
        $content = 'var loginEncrypted_PublicKey = {e:\'' . $key['e'] . '\', n:\'' . $key['n'] . '\'};';
        file_put_contents(__DIR__ . static::PUBLIC_KEY_JS_FILE, $content);

        // remove merged piwik JS file, to force its re-generation including the newly written file
        // Note: Plugin name provided, so that, depending on its type, either "core" or "non_core" JS
        //       gets removed from /tmp/assets. Otherwise, both JS would be removed, unnecessarily.
        $pluginName = explode('\\', get_called_class());
        AssetManager::getInstance()->removeMergedAssets($pluginName[2]);
    }

    /**
     * Returns the private key from database
     *
     * @return string
     */
    protected static function getPrivateKey()
    {
        $privatekey = Db::fetchOne("SELECT key_content FROM " . Common::prefixTable(static::KEY_TABLE) . " WHERE id = ?", array(static::KEY_TABLE_PRIVATE_KEY_ID));
        return $privatekey;
    }

    /**
     * Returns the generation timestamp of last generated keys
     *
     * @return string
     */
    public static function getKeyGenTime()
    {
        try {
            $keygentime = Db::fetchOne("SELECT gen_time FROM " . Common::prefixTable(static::KEY_TABLE) . " WHERE id = ?", array(static::KEY_TABLE_PRIVATE_KEY_ID));
        } catch (Exception $e) {
            // return this in case of any DB errors
            $keygentime = static::KEY_GENERATION_TIME_UNKNOWN;
        }
        return $keygentime;
    }

    /**
    * Getting backtrace - adapted from https://secure.php.net/manual/en/function.debug-backtrace.php#111355
    *
    * @param int $maximum Ignore calls above this number
    *
    * @return string
    */
    protected static function getBacktrace($maximum = 4)
    {
        $trace = '';
        foreach (debug_backtrace() as $k => $v) {
            if ($k > $maximum) {
                continue;
            }

            // prevent user notices about array conversion to string (on implode()) appearing
            set_error_handler(function() { /* ignore errors */ });

            $trace .= '#' . $k . ' ' . $v['file'] . '(' . (isset($v['line']) ? $v['line'] : '') . '): ' . (isset($v['class']) ? $v['class'] . '->' : '') . $v['function'] . '(' . implode(', ', $v['args']) . ')' . " || ";

            restore_error_handler();
        }

        return $trace;
    }
}
