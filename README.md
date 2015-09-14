# LoginEncrypted plugin for Piwik

## Description

This [Piwik](http://piwik.org/) plugin encrypts users' passwords when transmitted from browser to the Piwik server on login or password change. The [RSA public-key cryptosystem](https://en.wikipedia.org/wiki/RSA_%28cryptosystem%29) with the [PKCS#1 padding scheme](https://en.wikipedia.org/wiki/PKCS_1) is used for that.

A public/private key pair is generated on plugin installation, and can be changed by a Piwik super user in plugin settings. The public key is then used for encryption of passwords on login and password change forms, provided that JavaScript is enabled in the browser. With JavaScript disabled, the plugin falls back to the regular un-encrypted login or password change.

Apart from the cryptographic features, this plugin reuses the functionality of the Piwik core Login plugin. It does not re-implement it, but uses the core plugin which, even disabled, is still available in Piwik. Since the core Login plugin cannot be active at the same time as this plugin, it will be automatically deactivated on activation of this plugin, and activated again on deactivation of this plugin.

The plugin integrates following cryptographic packages:

  * Server side, for key generation and password decryption (PHP): [phpseclib](https://github.com/phpseclib/phpseclib), version 2.0.0
  * Client side, for password encryption (JavaScript): [jsbn](http://www-cs-students.stanford.edu/~tjw/jsbn/), version 1.4

__Important Notes__

  * This plugin is not meant to be a replacement for use of HTTPS with Piwik. It is meant only for Piwik installations where, for whatever reason, implementation of HTTPS is impossible. If HTTPS can be implemented, it should be used instead, as it is much better and this plugin will not be necessary. This plugin encrypts sent passwords, which is useful, e.g., on open public wireless networks, but cannot prevent active [Man-in-the-Middle attacks](https://en.wikipedia.org/wiki/Man-in-the-middle_attack), where an attacker can alter the communication between the user and Piwik in real-time, nor active [Man-on-the-Side attacks](https://en.wikipedia.org/wiki/Man-on-the-side_attack), where an attacker can listen to the communication and instantly use the learned information. E.g., a user's session can still be [hijacked](https://en.wikipedia.org/wiki/Session_hijacking) while the user is logged in, as it is still transmitted in clear text. So, logging out of Piwik after having finished using it is still crucial with this plugin. It is only meant to prevent a passive communication listener/logger from retrospectively learning the Piwik password looking at the logs.
  * If [installing](https://piwik.org/faq/plugins/#faq_21) this plugin using a ZIP file or manually via the FTP file upload, please download the plugin from the [Piwik Plugins Marketplace](https://plugins.piwik.org/LoginEncrypted), NOT from its Github releases. The Marketplace picks up the Github releases automatically and converts them into a form usable with Piwik (e.g. correct naming of the plugin folder, removal of possibly negatively impacting files and directories, etc.).
  * Encryption of passwords on their changes in Piwik settings with the user already logged in, i.e., (a) on change of one's own password by current user or (b) on change of other users' passwords by a super user is (currently) not supported.
  * Password auto-completion on login would not work, because the encrypted password would be filled in, which then would be encrypted again, resulting in incorrect password submission. So, the auto-completion is turned off by client side JS on login page load. (But its effectiveness may depend on the browser used.)

## FAQ

__How can I see that the password encryption is active?__

As visual feedback, one will, on some browsers, see the password switch to a much larger length upon a click on the "Sign in" button. (If one enters a password shorter than the password field that is.) Whether one can see that depends on the browser used, though.

__How often do the encryption keys need to be changed?__

They don't normally need to be changed for a few years. The reason is that the private key, which is used for decryption, never leavers the server - it is never sent to the browser. While the public key, which is sent to the browser, is just that - public. Any one can know it, but can use it for encryption only, not for decryption. That is the base of [asymmetric cryptography](https://en.wikipedia.org/wiki/Public-key_cryptography). And the PKCS#1 padding scheme hardens against [offline password bruteforcing](https://en.wikipedia.org/wiki/Chosen_plaintext_attack) (i.e. trying to encrypt various passwords, comparing them to the known encrypted result), by making the encrypted result change on every encryption. Situations when a keys change is needed are:

  * When the private key gets compromised (e.g. by an unauthorized access to the Piwik database)
  * When advances in cryptography show that current key length is not sufficient and a longer key is needed
  * When an issue is discovered in encryption software implementation, requiring its update (and therefore new keys, since the issue could have led to key compromise)
  * After a longer while, just in case any of the above has occurred unnoticed

__What is the reason for the chosen default key length in the plugin?__

It is one that is not too resource-demanding (see next point), while still considered safe for years to come.

__What is the maximum usable key length?__

It depends on the integrated cryptographic packages listed above, first and foremost on phpseclib on the server side. As well as used server hardware, PHP version and configuration (e.g. if bcmath or gmp extensions are installed, otherwise a pure-PHP implementation will be used by phpseclib). This plugin is completely transparent as far as that is concerned. If the key is set too long - it will not be generated (the generation will just "hang", until the plugin settings page is reloaded by the user). Around the same size, even if generation succeeds, it is possible that password decryption on a subsequent login will run into the Piwik-enforced execution timeout of 30 seconds, causing the login to fail. (See the FAQ points below on what to do in such a case.) But a key length of 4096 bits should be safe to use for anybody, and is [enough for the foreseeable future](https://en.wikipedia.org/wiki/RSA_%28cryptosystem%29#Integer_factorization_and_RSA_problem). Also, if one is concerned about actors being able to break a longer key than that, they should switch to properly implemented HTTPS.

Additionally: RSA gets too resource-demanding with longer keys, so that instead of increasing its key size, a move to a more modern asymmetric cryptosystem (e.g. [ECC](https://en.wikipedia.org/wiki/Elliptic_curve_cryptography)) would be better in future.

__There is no way for me to log in. What do I do?__

Disable JavaScript in the browser and reload the login page. You will then be able to log in the standard way, without password encryption. After having logged in, you can enable JavaScript again. Then, if you wish as Piwik super user, you can deactivate and uninstall the plugin in Piwik administration.

__Nothing works. And I can't login to deactivate the plugin. What do I do?__

Connect to your Piwik installation using FTP. Then in the /config/config.ini.php file change the

    Plugins[] = "LoginEncrypted"

line to

    Plugins[] = "Login"

That will disable the LoginEncrypted plugin and enable the original Piwik Login plugin instead. After having done that, clear your browser cache. Then do as in the above "There is no way for me to log in. What do I do?" point.

## Changelog

See [CHANGELOG.md](https://github.com/Joey3000/piwik-LoginEncrypted/blob/master/CHANGELOG.md).

## Support

Please direct questions and feedback to [http://forum.piwik.org/read.php?9,129164](http://forum.piwik.org/read.php?9,129164). Or send me a PM (private message) in that forum. Issues can be reported at [https://github.com/Joey3000/piwik-LoginEncrypted/issues](https://github.com/Joey3000/piwik-LoginEncrypted/issues).
