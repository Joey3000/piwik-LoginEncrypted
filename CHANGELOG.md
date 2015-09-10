# LoginEncrypted Changelog

#### 1.0.2

* Implemented a workaround for Piwik minified JS file cache buster issue (see https://github.com/piwik/piwik/issues/8713). The public key file is now not part of the minified file, but is loaded separately. So that no decryption error should appear any longer after a key change.
* Updated documentation

#### 1.0.1

* Fixed empty password handling (no more decryption errors in such a case)
* Updated README.md (more details and better compatibility with the Plugins Marketplace display)

#### 1.0.0

* Initial release
