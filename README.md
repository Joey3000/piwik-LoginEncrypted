# LoginEncrypted plugin for Piwik

## Description

This [Piwik](http://piwik.org/) plugin will encrypt users' passwords when transmitted from browser to the Piwik server on login or password change. The [RSA public-key cryptosystem](https://en.wikipedia.org/wiki/RSA_%28cryptosystem%29) with the [PKCS#1 padding scheme](https://en.wikipedia.org/wiki/PKCS_1) is used for that.

A public/private key pair will be generated on plugin installation, and can be changed by the Piwik super user in the settings of the plugin. The public key is then used for encryption of passwords on login and password change forms, provided that JavaScript is enabled in the browser. With JavaScript disabled, the plugin falls back to the regular un-encrypted login or password change.

This plugin reuses the functionality of the Piwik core Login plugin. It does not re-implement that functionality, but uses that core plugin which, even disabled, is still available in Piwik. Since the core Login plugin cannot be active at the same time as this plugin, it will be automatically deactivated on activation of this plugin, and activated again on deactivation of this plugin.

The plugin integrates following cryptographic packages:

  * Server side, for key generation and password decryption (PHP): [phpseclib](https://github.com/phpseclib/phpseclib), version 2.0.0
  * Client side, for password encryption (JavaScript): [jsbn](http://www-cs-students.stanford.edu/~tjw/jsbn/), version 1.4

__Important Notes__
  * Encryption of passwords on their changes in Piwik settings with the user already logged in, i.e., (a) on change of one's own password by current user or (b) on change of other users' passwords by the super user is (currently) not supported.
  * Password auto-completion on login would not work, because the encrypted password would be filled in, which then would be encrypted again, resulting in incorrect password submission. So, the auto-completion is turned off by client side JS on login page load. (But its effectiveness may depend on the browser used.)
  * This plugin is not meant to be a replacement for usage of HTTPS with Piwik. It is meant only for Piwik installations where, for whatever reason, usage of HTTPS is impossible. If HTTPS is possible - it should be used instead, as it is much better and this plugin will not be necessary. This plugin encrypts sent passwords, which is useful e.g. on open public wireless networks, but cannot prevent active [Man-in-the-Middle attacks](https://en.wikipedia.org/wiki/Man-in-the-middle_attack), where an attacker can alter the communication between the user and Piwik just-in-time. E.g., a user's session can still be hijacked while the user is logged in, as it will still be transmitted in clear text. So, logging out of Piwik after having finished using it will still be necessary. This plugin is only meant to protect a passive communication listener/logger from retrospectively learning the Piwik password looking at the logs.

## Changelog

See [CHANGELOG.md](https://github.com/Joey3000/piwik-LoginEncrypted/blob/master/CHANGELOG.md).

## FAQ

__How can I see that the password encryption is active?__

As visual feedback, one will, on some browsers, see the password switch to a much larger length upon a click on the "Sign in" button. (If one enters a password shorter than the password field that is.) Whether one can see that depends on the browser used, though.

__What is the maximum usable key length?__

It depends on the integrated cryptographic packages listed above, first and foremost on phpseclib on the server side. As well as used server hardware, PHP version and configuration (e.g. if bcmath or gmp extensions are installed, otherwise the pure-PHP implementation will be used). This plugin is completely transparent as far as that is concerned. If the key is set too long - it will not be generated (the generation will just "hang", until the plugin settings page is reloaded by the user). Around the same size, even if generation succeeds, it is possible that password decryption on a subsequent login will run into the Piwik-enforced execution timeout of 30 seconds, causing the login to fail. (See the "Troubleshooting" section below on what to do in such a case.) But a key length of 4096 bits should be safe to use for anybody, and is [enough for the foreseeable future](https://en.wikipedia.org/wiki/RSA_%28cryptosystem%29#Integer_factorization_and_RSA_problem). Also, if one is concerned about actors being able to break a longer key than that, they should switch to properly implemented HTTPS.

__I get a "Decryption error" shown when trying to log in. What does it mean?__

As the accompanying text states, that may occur after installation of the LoginEncrypted plugin or change of its encryption keys. As the text also says, please clear the browser cache and reload the login page. That is necessary to make the browser download the latest JavaScript file containing the new public key. See following issue report for information: [https://github.com/piwik/piwik/issues/8713](https://github.com/piwik/piwik/issues/8713).
Note: Alternatively to the browser cache cleaning, a forced reloading (i.e. re-downloading of all locally cached files) of the login page would work es well. How it can be done, depends on the browser used, e.g., on Firefox: hold down the Shift key while clicking on the "Reload" arrow.

__I get one or several "/plugins/LoginEncrypted/phpseclib/Crypt/RSA.php(...): User Notice - Decryption error" warnings in the Piwik dashboard after login. What do they mean?__

They can be safely closed and ignored. They appear after the pre-login decryption errors described in the above point. Please note that they not necessarily refer to decryption errors on your own login or password change attempts, but to ones having occurred with any Piwik user before you logged in.

## Troubleshooting

__There is no way for me to log in. What do I do?__

Disable JavaScript in the browser and reload the login page. You will then be able to log in the standard way, without password encryption. Then, if you wish as Piwik super user, you can deactivate the plugin in Piwik administration.

__Nothing works. And I can't login to deactivate the plugin. What do I do?__

Connect to your Piwik installation using FTP. Then in the /config/config.ini.php file change the

`Plugins[] = "LoginEncrypted"`

line to

`Plugins[] = "Login"`

That will disable the LoginEncrypted plugin and enable the original Piwik Login plugin instead. After having done that, clear your browser cache and reload the login page. You will then be able to log in the standard way, without password encryption. And if you wish as Piwik super user, you can then uninstall the plugin in Piwik administration.

## Support

Please direct questions and feedback to http://forum.piwik.org/read.php?9,129164. Issues can be reported at https://github.com/Joey3000/piwik-LoginEncrypted/issues.
