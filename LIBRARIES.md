# Libraries Integration Notes

## phpseclib

Taken over unmodified. No potentially unneeded files were removed, to not be concerned with breaking their inter-dependencies.

## jsbn

Edited the `rsa.js` file as follows:

  1. Added following function after the `RSAEncrypt` function:
    ```
    function RSAEncryptLong(text) {
      var length = ((this.n.bitLength()+7)>>3) - 11;
      if (length <= 0) return false;
      var ret = "";
      var i = 0;
      while(i + length < text.length) {
        ret += this._short_encrypt(text.substring(i,i+length));
        i += length;
      }
      ret += this._short_encrypt(text.substring(i,text.length));
      return ret;
    }
    ```

  2. Modified the last line from
    ```
    RSAKey.prototype.encrypt = RSAEncrypt;
    ```
    to
    ```
    RSAKey.prototype._short_encrypt = RSAEncrypt;
    RSAKey.prototype.encrypt = RSAEncryptLong;
    ```

## Acknowledgements

Big thanks to:

  * The developers of the libraries (see README.md)
  * [bestmike007](http://bestmike007.com/2011/08/secure-data-transmission-between-pure-php-and-javascript-using-rsa/) for posting integration tips
  * The [Encrypt Configuration](http://extensions.joomla.org/extension/encrypt-configuration) extension for Joomla, for inspiration
