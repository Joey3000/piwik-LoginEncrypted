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
 * Handles decryption of form inputs.
 */
class CryptoForm
{
    /**
     * Gets the input value from the HTML form, decrypts it and writes the decrypted
     * value back into _POST.
     * Note: Writing to _POST directly, as there doesn't seem to be another way. E.g., if
     *       value is replaced as in https://pear.php.net/manual/en/package.html.html-quickform2.qf-migration.php
     *       (using array_unshift()), it would not persist, as a "new" object instance
     *       will re-read its sources (i.e. _POST).
     *
     * @param QuickForm2 $form The HTML form which the input is part of
     * @param string $inputId The input ID of the field on the HTML form
     */
    public static function decryptFormInput($form, $inputId)
    {
        $value = $form->getSubmitValue($inputId);
        static::decryptAndWriteToPost($inputId, $value);
    }

    /**
     * Decrypts the parameter value and writes it out to _POST.
     *
     * @param string $parameter _POST parameter name
     * @param string $value Parameter value to be decrypted
     */
    public static function decryptAndWriteToPost($parameter, $value)
    {
        $value = Crypto::decrypt($value);

        // write out if a value was submitted
        // Note: Compare loosely, so both, "" (input empty; forms send strings)
        //       and NULL (input not sent - see QuickForm2->getSubmitValue())
        //       are covered - see https://secure.php.net/manual/en/types.comparisons.php
        if ($value != "") {
            $_POST[$parameter] = $value;
        }
    }
}
