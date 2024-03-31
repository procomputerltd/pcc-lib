<?php
/*
Copyright (C) 2018 Pro Computer James R. Steel

This program is distributed WITHOUT ANY WARRANTY; without 
even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU General Public License 
for more details.
*/
/* 
    Created on  : Jan 01, 2016, 12:00:00 PM
    Organization: Pro Computer
    Author      : James R. Steel
    Description : System utilities. All methods are static, called like 'System::method(args, ...)
*/
namespace Procomputer\Pcclib;
/**
 * System utilities. All methods are static, called like 'System::method(args, ...)
 */
class System {

    /**
     * Default HTTP port number.
     *
     * @var int
     */
    const DEFAULT_HTTP_PORT = 80;

    /**
     * Default HTTPS SSL port number.
     *
     * @var int
     */
    const DEFAULT_HTTPS_PORT = 443;

    /**
     * When TRUE the OS is MS, else not MS.
     *
     * @var boolean
     */
    private static $_osWindows = null;

    /**
     * Default URL argument delimiter e.g. '&'
     *
     * @var string
     */
    private static $_argDelim = null;

    /**
     * When TRUE the current script is HTTPS.
     *
     * @var boolean
     */
    private static $_https = null;

    /**
     * Current server host name.
     *
     * @var string
     */
    private static $_svrHost = null;

    /**
     * URL parts keys used in URL parsing and expanding.
     *
     * @var array
     */
    protected static $_url_parts_keys = ["scheme", "host", "port", "user",
        "pass", "path", "query", "fragment"];

    /**
     * URL scheme names and associated default port numbers.
     *
     * @var array
     */
    protected static $_scheme_ports = ['http' => 80, 'https' => 443, 'ftp' => 21,
        'imap' => 143, 'imaps' => 993, 'pop3' => 110, 'pop3s' => 995];

    /**
     * @return boolean   Returns TRUE when the OS is MS.
     */
    public static function osWindows() {
        if(is_null(self::$_osWindows)) {
            // Samples returned by php_uname("s"): 'FreeBSD', 'Wndoze NT'
            self::$_osWindows = (false !== strpos(strtolower(php_uname("s")), "win"));
        }
        return self::$_osWindows;
    }

    /**
     * @return boolean Returns TRUE when the current script is secure HTTPS, else FALSE.
     */
    public static function requestIsHttps() {
        if(is_null(self::$_https)) {
            self::$_https = false;

            $vars = ["HTTPS", "HTTP_X_FORWARDED_SERVER", "SCRIPT_URI", "HTTP_HOST"];
            foreach($vars as $key => $name) {
                $value = self::getServerVar($name);
                if(null !== $value) {
                    $value = strtolower(trim(strval($value)));
                    switch($key) {
                    case 0: // HTTPS
                        if(strcmp($value, "on") && strcmp($value, "1")) {
                            continue 2;
                        }
                        break;
                    case 1: // HTTP_X_FORWARDED_SERVER
                        if(strcmp($value, "secure") && strcmp($value, "ssl")) {
                            continue 2;
                        }
                        break;
                    case 2: // SCRIPT_URI
                        if(strlen($value) < 5 || strcmp(strtolower(substr($value, 0, 5)), "https")) {
                            continue 2;
                        }
                        break;
                    case 3: // HTTP_HOST
                        if(strlen($value) < 4 || false === strpos($value, ":443")) {
                            continue 2;
                        }
                        break;
                    }
                    self::$_https = true;
                    break;
                }
            }
        }
        return self::$_https;
    }

    /**
     * @return string    Returns the current script's absolute server path.
     *
     * @example /var/www/html/phpMyAdmin/phpinfo.php
     */
    public static function getScriptAbsPath() {
        /* 	Name                    Non-MS sample           MS sample
          ----------------------  ----------------------  -----------------------------------
          SCRIPT_FILENAME	        /var/www/html/php.php   C:\Inetpub\wwwroot\php.php
          ORIG_PATH_TRANSLATED  	N/A                     C:\Inetpub\wwwroot\php.php
          PATH_TRANSLATED	        /var/www/html/php.php   C:\Inetpub\wwwroot
         */
        return self::getFirstValidServerValue(["SCRIPT_FILENAME", "ORIG_PATH_TRANSLATED", "PATH_TRANSLATED"]);
    }

    /**
     * @return string    Returns the current script's absolute directory path.
     *
     * @example /var/www/html/phpMyAdmin
     */
    public static function getScriptDirAbsPath() {
        return dirname(self::getScriptAbsPath());
    }

    /**
     * @return string    Returns the current script's virtual path.
     *
     * @example /phpMyAdmin/phpinfo.php
     */
    public static function getScriptVirtualPath() {
        return self::getFirstValidServerValue(["SCRIPT_NAME", "ORIG_PATH_INFO", "PATH_INFO", "PHP_SELF"]);
    }

    /**
     * Returns the server's absolute directory path.
     * @return string Returns the server's absolute directory path.
     * @example /var/www/html -or- D:/INETPUB/091
     */
    public static function getServerAbsPath() {
        $path = self::getFirstValidServerValue(["DOCUMENT_ROOT"]);
        if(strlen($path)) {
            return $path;
        }
        $path = self::getScriptAbsPath();
        $len = strlen($path);
        if($len) {
            $v_len = strlen(self::getScriptVirtualPath());
            if($v_len) {
                $len -= $v_len;
                if($len > 0) {
                    return substr($path, 0, $len);
                }
            }
        }
        return null;
    }

    /**
     * Returns the server's absolute directory path.
     * @return string Returns the server's absolute directory path.
     * @example /var/www/html -or- D:/INETPUB/091
     */
    public static function getDocumentRoot() {
        return self::getServerAbsPath();
    }

    /**
     * @return string    Returns the server host full url.
     *
     * @example http://www.example.com
     *
     * Sample server vars for UNIX/Linux server:       HTTPS enabled
     * ---------------------------------------------   ---------------------------------------
     * $_SERVER["HTTP_HOST"]   www.example.com         $_SERVER["HTTP_HOST"]   www.example.com
     * $_SERVER["SERVER_NAME"] www.example.com         $_SERVER["SERVER_NAME"] www.example.com
     * $_SERVER["HTTPS"]       (null)                  $_SERVER["HTTPS"]       on
     * getenv("HTTPS")         (false)                 getenv("HTTPS")         on
     * $_SERVER["SERVER_PORT"] 80                      $_SERVER["SERVER_PORT"] 443
     *
     * Sample server vars for Wndoze 2003 server:    HTTPS enabled
     * ---------------------------------------------   ---------------------------------------
     * $_SERVER["HTTP_HOST"]   localhost               $_SERVER["HTTP_HOST"]   localhost
     * $_SERVER["SERVER_NAME"] localhost               $_SERVER["SERVER_NAME"] localhost
     * $_SERVER["HTTPS"]       off                     $_SERVER["HTTPS"]       on
     * getenv("HTTPS")         (false)                 getenv("HTTPS")         (false)
     * $_SERVER["SERVER_PORT"] 80                      $_SERVER["SERVER_PORT"] 443
     */
    public static function getServerUrl() {
        $host = self::getServerHost(); // E.g. 'www.example.com', 'locahost:3783'
        if(!empty($host) && false !== strpos($host, ":")) {
            // Remove the :nnnn port specifier.
            $host = preg_replace('/\s*\:[0-9]+\s*$/', "", $host);
            $use_port = true;
        }
        else {
            $use_port = false;
        }
        if(empty($host)) {
            // PHP is corrupted?
            return "";
        }
        $secure = self::requestIsHttps();
        $temp = self::getServerVar('SERVER_PORT');
        $port = (is_numeric($temp)) ? intval($temp) : null;
        if(empty($port)) {
            $port = ($secure ? self::DEFAULT_HTTPS_PORT : self::DEFAULT_HTTP_PORT);
        }
        $uri = ($secure ? "https" : "http") . "://" . $host;
        if($use_port || $port != ($secure ? self::DEFAULT_HTTPS_PORT : self::DEFAULT_HTTP_PORT)) {
            $uri .= ":" . $port;
        }
        return $uri;
    }

    /**
     * Returns the current script's full server url.
     *
     * @return string   Returns the current script's full server url e.g.
     * http://www.example.com/myfolder/index.php
     */
    public static function getScriptHref() {
        return self::getServerUrl() . "/" . preg_replace('@^/+@', '', self::getScriptVirtualPath());
    }

    /**
     * @return string Returns the current script's host name in $_SERVER[] values
     * 'HTTP_HOST' or 'HTTP_X_FORWARDED_HOST'
     */
    public static function getServerHost() {
        if(is_null(self::$_svrHost)) {
            self::$_svrHost = "";
            $svr_keys = ["HTTP_X_FORWARDED_HOST", "HTTP_HOST"];
            foreach($svr_keys as $key) {
                $h = self::getServerVar($key);
                if(null !== $h && strlen($h = trim($h))) {
                    self::$_svrHost = $h;
                    break;
                }
            }
        }
        return self::$_svrHost;
    }

    /**
     * Helper function to get the MS operating system COMPUTERNAME environment value
     * e.g. 'dataserver001' and 'PCCNT7C3POR2D2'
     *
     * @return string    Returns the MS operating system COMPUTERNAME environment value.
     */
    public static function getComputerName() {
        $var = 'COMPUTERNAME';
        $temp = getenv($var);
        $name = (false === $temp) ? "" : trim($temp);
        if(!strlen($name)) {
            $cname = self::getServerVar($var);
            $name = (null === $cname) ? '' : $cname;
        }
        return $name;
    }

    /**
     * @return string    Returns the default file path delimiter.
     */
    public static function pathSlash() {
        return self::osWindows() ? '\\' : '/';
    }

    /**
     * @return string    Returns the default URL argument delimiter e.g. '&'.
     *
     */
    public static function urlGetArgDelimiter() {
        if(empty(self::$_argDelim)) {
            self::$_argDelim = ini_get("arg_separator.output");
            if(is_string(self::$_argDelim) && ($len = strlen(self::$_argDelim)) && strcmp(strtolower(self::$_argDelim), "&amp;")) {
                if(self::$_argDelim > 1) {
                    self::$_argDelim = substr(self::$_argDelim, 0, 1);
                }
            }
            else {
                self::$_argDelim = "&";
            }
        }
        return self::$_argDelim;
    }

    /**
     * Appends a slash to a path when the path does not have a trailing slash.
     *
     * @param string $path   Path for which to appnd a slash.
     * @param string $delim  The delimiter slash to append to the path.
     *
     * @return string    Returns path with slash appended.
     */
    public static function addPathSlash($path, $delim = "/") {
        if(is_null($path)) {
            return "";
        }
        if(!is_string($path)) {
            return strval($path) . $delim;
        }
        $n = strlen($path);
        if($n) {
            $c = substr($path, --$n, 1);
            if($c != '/' && $c != '\\' && ($c != ':' || !self::osWindows())) {
                $path .= $delim;
            }
        }
        return $path;
    }

    /**
     * Removes trailing slashes from a pathname.
     *
     * @param string $path   The path from which to remove trailing slashes.
     *
     * @return string    Returns path with trailing slashes removed.
     */
    public static function removePathSlash($path) {
        if(is_null($path)) {
            return "";
        }
        if(!is_string($path)) {
            return $path;
        }
        $n = strlen($path);
        if(!$n) {
            return "";
        }
        $c = substr($path, --$n, 1);
        if(false === strpos('/\\', $c)) {
            return $path;
        }
        $path = preg_replace('@^(.*?)([/\\\\]+)$@', "$1", $path);
        return strlen($path) ? $path : $c;
    }

    /**
     * Removes leading slashes from a pathname.
     *
     * @param string $path   The path from which to remove leading slashes.
     *
     * @return string    Returns path with leading slashes removed.
     */
    public static function removeLeadingSlashes($path) {
        if(is_null($path)) {
            return "";
        }
        if(!is_string($path)) {
            return $path;
        }
        $n = strlen($path);
        if(!$n) {
            return "";
        }
        $c = substr($path, 0, 1);
        if(false === strpos('/\\', $c)) {
            return $path;
        }
        $path = preg_replace('@^([/\\\\]+)(.*)$@', '$2', $path);
        return strlen($path) ? $path : $c;
    }

    /**
     * Returns the base filename part without the extension. Returns empty string if no path expression.
     *
     * @param string $path   The path for which to get the file base name.
     *
     * @return string    Returns the file base name.
     *
     * PHP's pathinfo() method returns an array:
     *     [dirname]   => c:\temp
     *     [basename]  => base.foo.bar
     *     [extension] => bar
     */
    public static function getFileBasename($path) {
        if(!Types::isBlank($path)) {
            $p = pathinfo($path);
            if(is_array($p) && isset($p["basename"])) {
                $b = $p["basename"];
                if(preg_match('/^[a-z]\:.*$/i', $b)) {
                    if(false === ($b = substr($b, 2))) {
                        return "";
                    }
                }
                if(isset($p["extension"]) && ($l = strlen($e = $p["extension"]))) {
                    $b = substr($b, 0, strlen($b) - $l - 1);
                }
                return $b;
            }
        }
        return "";
    }

    /**
     * Returns the extension part of a pathname with a dot(.) appended when 'add_dot' is true.
     *
     * @param string  $path      The path for which to get the file extension.
     * @param boolean $add_dot   prepend a dot (period) to the returned file extension.
     *
     * @return string    Returns the file extension
     */
    public static function getFileExtension($path, $add_dot = false) {
        if(isset($path) && strlen($path = trim($path))) {
            $p = pathinfo($path);
            if(isset($p["extension"]) && strlen(trim($e = $p["extension"]))) {
                if($add_dot) {
                    $e = "." . $e;
                }
                return $e;
            }
        }
        return "";
    }

    /**
     * Merges $_GET and $_POST super-global arrays into a single array and escapes
     * the values in that array if method 'haveMagicQuotesGpc()' returns true.
     *
     * @return array Returns an array of merged $_GET and $_POST super-global arrays.
     */
    public static function getRequestVars() {
        $req = array_merge($_GET, $_POST);
        if(is_array($req) && count($req) && self::haveMagicQuotesGpc()) {
            $req = self::unEscape($req);
        }
        return $req;
    }

    /**
     * @return boolean   Returns a boolean returned by PHP's 'get_magic_quotes_gpc()' function.
     */
    public static function haveMagicQuotesGpc() {
        return (bool)(function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc());
    }

    /**
     * Escapes slashes from a string or array of strings and sub-arrays.
     *
     * The most common use of unEscape() is to strip escape slashes from PHP's
     * super global request variables $_REQUEST, $_GET, $_POST and $_COOKIE when
     * the 'magic_quotes_gpc' php.ini directives is ON and PHP has escaped the
     * request values on startup.
     *
     * When 'magic_quotes_gpc' is ON PHP prepends a backslash to single-quotes, double quotes,
     * backslashes and NULLs. This is identical to what addslashes() does.
     *
     * @param mixed $mixed Scalar or array value to unEscape.
     *
     * @return mixed Returns the value unescaped.
     */
    public static function unEscape($mixed) {
        if(is_array($mixed)) {
            // Call unEscape recursively for array variables.
            $mixed = array_map([__CLASS__, __FUNCTION__], $mixed);
            return $mixed;
        }
        return (strpos($mixed, '\\\'') !== false || strpos($mixed, '\\\\') !== false || strpos($mixed, '\\"') !== false) ? stripslashes($mixed) : $mixed;
    }

    /**
     * Returns a string value for an ini configuration option referenced by '$name' parameter.
     *
     * @param mixed $name    Name of ini configuration option.
     *
     * @return string    Return configuration option value or empty string if not found.
     */
    public static function iniGet($name) {
        if(empty($name)) {
            return null;
        }
        return ini_get($name);
    }

    /**
     * Returns a boolean TRUE or FALSE for ini configuration option referenced by '$name' parameter.
     *
     * @param mixed $name    Name of ini configuration option.
     *
     * @return boolean    Return boolean of ini configuration option.
     */
    public static function iniGetBool($name) {
        if(empty($name)) {
            return null;
        }
        return self::iniBoolValue(ini_get($name));
    }

    /**
     * Returns a boolean TRUE or FALSE for a value retrieved by 'ini_get()'
     *
     * @param mixed $value    Value for which to determine boolean value.
     *
     * @return boolean    Return boolean for value.
     */
    public static function iniBoolValue($value) {
        if(empty($value)) {
            return false;
        }
        if(!is_string($value)) {
            return $value ? true : false;
        }
        $value = trim($value);
        if(is_numeric($value)) {
            return floatval($value) ? true : false;
        }
        $value = strtolower($value);
        return (!strcmp("off", $value) || !strcmp("false", $value) || !strcmp("no", $value)) ? false : true;
    }

    /**
     * Scans list of server keys for first non-blank SERVER value.
     *
     * @param array $keys   List of server keys to scan for first non-blank SERVER value.
     *
     * @return string  Returns the first valid non-blank SERVER value referenced by keys parameter.
     */
    public static function getFirstValidServerValue($keys) {
        foreach($keys as $key) {
            $value = self::getServerVar($key);
            if(is_string($value) && (strlen($value = trim($value)))) {
                return $value;
            }
        }
        return "";
    }

    /**
     * Returns a server variable value.
     * @param string $var   The variable name.
     * @param int    $type  A PHP 'INPUT_*' constant input type.
     * @return mixed  Returns the value or NULL if not found.
     */
    public static function getServerVar($var, $type = INPUT_SERVER) {
        if(filter_has_var($type, $var)) {
            $value = filter_input($type, $var, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        }
        elseif(isset($_SERVER[$var])) {
            $value = filter_var($_SERVER[$var], FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        }
        else {
            $value = null;
        }
        return $value;
    }

    /**
     * Completes a uri when the uri is incomplete. For example, this method adds the scheme
     * (e.g 'http://') if it's missing. When the argument evaluates as a Winblow filename i.e. it
     * begins with a drive specifier followed by a colon (e.g. C:) this method prepends 'file:///'
     *
     * @param string $uri    URL to complete.
     *
     * @return string    Returns completed URL.
     */
    public static function urlComplete($uri) {
        if(!is_scalar($uri)) {
            trigger_error("In " . __FUNCTION__ . " line " . __LINE__
                . ": invalid '\$uri' parameter '" . Types::getVartype($uri)
                . "'", E_USER_WARNING);
            // Return non-scalar to sender.
            return $uri;
        }
        $uriTrimmed = trim(strval($uri));
        $len = strlen($uriTrimmed);
        if(!$len) {
            // Return empties to sender.
            return $uri;
        }
        if(preg_match('/^[a-z]\:.*$/i', $uriTrimmed)) {
            // Assume a wndoze path and prepend file protocol.
            return "file:///" . $uriTrimmed;
        }
        $c = substr($uriTrimmed, 0, 1);
        if('\\' == $c) {
            // Can't complete when begins with a backslash. Return to sender.
            return $uri;
        }
        if('/' == $c) {
            $uriTrimmed = self::removeLeadingSlashes($uriTrimmed);
            if(!strlen($uriTrimmed) || false !== strpos('/\\', substr($uriTrimmed, 0, 1))) {
                // Can't complete when single slash. Return to sender.
                return $uri;
            }
        }
        elseif(preg_match('@^([a-z][a-z][a-z][a-z]*)[ \\t]*\:[ \\t]*(.*)$@i', $uriTrimmed, $matches)) {
            if(strlen($matches[2]) && "/" == substr($matches[2], 0, 1)) {
                $matches[2] = preg_replace('@^/+(.*)$@', "$1", $matches[2]);
            }
            return $matches[1] . "://" . $matches[2];
        }
        return "http://" . $uriTrimmed;
    }

    /**
     * Set the HTTP scheme protocol in a URL.
     *
     * @param string  $url       URL for which to set HTTP scheme protocol.
     * @param boolean $secure    When NULL the URL's scheme is unchanged. If TRUE HTTPS is used, else HTTP.
     * @param int     $port      (optional) Alternate port number. When NULL the current port is used.
     *
     * @return string   Returns the current script's full server url.
     *
     * @example http://www.example.com/myfolder/index.php
     */
    public static function urlSetHttpScheme($url, $secure, $port = null) {
        if(empty($secure) && empty($port)) {
            return $url;
        }
        $parts = self::urlParse($url);
        // Don't set scheme protocol except for HTTP and HTTPS.
        if(!empty($parts["scheme"]) && strcmp("http", $parts["scheme"]) && strcmp("https", $parts["scheme"])) {
            return $url;
        }
        $parts["scheme"] = $secure ? "https" : "http";
        if(!empty($port)) {
            $port = self::urlValidatePort($port);
            if(false === $port) {
                trigger_error("In " . __FUNCTION__ . " line " . __LINE__
                    . ": invalid '\$port' parameter '" . Types::getVartype($port)
                    . "'", E_USER_WARNING);
            }
            else {
                $parts["port"] = $port;
                $use_port = true;
            }
        }
        if(!isset($use_port) && !empty($parts["port"])) {
            $port = self::urlValidatePort($parts["port"]);
            if(($secure && self::DEFAULT_HTTPS_PORT == $port) || (!$secure && self::DEFAULT_HTTP_PORT == $port)) {
                $parts["port"] = "";
            }
        }
        return self::urlExpandParts($parts);
    }

    /**
     * Removes trailing URL delimiter(s) ('?' and '&')
     *
     * @param string $url    URL from which to remove trailing delimiter.
     *
     * @return string Returns URL with trailing delimiter removed.
     */
    public static function urlRemoveDelimiter($url) {
        // Sample pattern: '/[\?\&]+\s*$/i'
        return preg_replace('~[\\?' . preg_quote(self::urlGetArgDelimiter()) . ']+\s*$~i', "", $url);
    }

    /**
     * URL function to append a URL delimiter ('?' or '&')
     *
     * @param string $url    URL for which to append delimiter.
     *
     * @return string Returns URL with delimiter appended.
     */
    public static function urlAddDelimiter($url) {
        $url = self::urlRemoveDelimiter($url);
        $delim = '?';
        if(strrchr($url, $delim)) {
            $delim = self::urlGetArgDelimiter();
        }
        return $url . $delim;
    }

    /**
     * Removes the hash fragment (bookmark) at end of the URL e.g. '#my_bookmark'
     *
     * @param string $uri  The uri from which to remove fragment.
     *
     * @return string Returns The uri with any fragment removed.
     */
    public static function urlRemoveHashFragment($uri) {
        if(!empty($uri) && strlen(trim($uri)) && false !== ($offset = strrpos($uri, '#'))) {
            $qry_offset = strrpos($uri, '?');
            if(false === $qry_offset) {
                $qry_offset = -1;
            }
            if($qry_offset < $offset) {
                $uri = substr($uri, 0, $offset);
            }
        }
        return $uri;
    }

    /**
     * Removes query arguments and fragment from the tail of a URL e.g. '?arg0=1&arg1=2#fragment'
     *
     * @param string $uri  The uri from which to remove query arguments and fragment.
     *
     * @return string    Returns The uri with any query arguments and fragment removed.
     */
    public static function urlRemoveQuery($uri) {
        if(!empty($uri) && strlen(trim($uri)) && false !== ($offset = strrpos($uri, '?'))) {
            $uri = substr($uri, 0, $offset);
        }
        return $uri;
    }

    /**
     * Parses arguments from a url string.
     *
     * @param string $url    URL from which to parse arguments.
     *
     * @return array Returns an array of zero or more name=>value argument pairs.
     */
    public static function urlGetArgs($url) {
        $args = [];
        if(is_null($url)) {
            return $args;
        }
        if(!is_string($url)) {
            return [$url => ""];
        }
        $url = trim($url);
        if(!strlen($url)) {
            return $args;
        }
        $url = urldecode($url);
        if(false !== strpos($url, '#')) {
            $url = self::urlRemoveHashFragment($url);
            if(!strlen($url)) {
                return $args;
            }
        }
        if(false !== ($value = strstr($url, '?')) || false !== ($value = strstr($url, '&'))) {
            $url = trim(substr($value, 1));
            if(strlen($url) < 1) {
                return $args;
            }
            parse_str(htmlspecialchars_decode($url), $args);
        }
        return $args;
    }

    /**
     * Parses the address part from a url string.
     *
     * @param string $url    URL from which to parse address.
     *
     * @return string   Returns address part of URL.
     */
    public static function urlGetAddress($url) {
        if(!isset($url) || !strlen($url = trim($url))) {
            return "";
        }
        if(false === ($value = strstr($url, '?')) && false === ($value = strstr($url, '&'))) {
            return $url;
        }
        $len = strlen($url) - strlen($value);
        if($len < 1) {
            return "";
        }
        return trim(substr($url, 0, $len));
    }

    /**
     * Build a url and optionally append additional query arguments.
     *
     * @param string $url         URL optionally with query arguments.
     * @param string $extra_args  Array of one or more name=>value pairs added to query arguments.
     * @param string $overwrite   Overwrite existing arguments.
     * @param string $secure      Specify secure HTTPS.
     *
     * @return string   Returns a built url.
     */
    public static function url($url, $extra_args = null, $overwrite = true, $secure = false) {
        $args = self::urlGetArgs($url);
        if(is_array($extra_args)) {
            // Note that array_merge() is case-sensitive so 'id=>1' and 'ID=>1' are different.
            $args = $overwrite ? array_merge($args, $extra_args) : array_merge($extra_args, $args);
        }
        $args = self::urlBuildArgList($args, false);
        $location = self::urlGetAddress($url);
        if(strlen($args)) {
            $location .= '?' . $args;
        }
        return $location;
    }

    /**
     * Builds HTML hidden element scripts or URL query arguments from an array of name=>value pairs.
     *
     * @param array   $args          Array of argument name=>value pairs.
     * @param boolean $form_elements When TRUE, HTML form elements are returned, else URL query args.
     * @param boolean $return_array  When TRUE an array of individual argument strings is returned.
     *
     * @return mixed Returns element script string or an array ($return_array=true).
     */
    public static function urlBuildArgList($args, $form_elements = true, $return_array = false) {
        $result = [];
        if(!Types::isBlank($args)) {
            foreach($args as $key => $val) {
                if($form_elements) {
                    // For 'htmlspecialchars()'  the default 'quote_style' parameter is
                    // ENT_COMPAT that converts double-quotes (and common entities) and leave single-quotes alone.
                    $result[] = '<input type="hidden" name="' . htmlspecialchars($key)
                        . '" value="' . htmlspecialchars($val) . '" />';
                }
                else {
                    $result[] = urlencode($key) . "=" . urlencode($val);
                }
            }
        }
        if($return_array) {
            return $result;
        }
        return count($result) ? implode($form_elements ? "\n" : self::urlGetArgDelimiter(), $result) : "";
    }

    /**
     * Parses a uri and returns an array with the following elements (items missing in the uri are blank):
     *
     * @param string $url    URL from which to parse parts.
     *
     * @return array    Returns array of URL parts.
     *   [scheme]    http
     *   [host]      www.myserver.com
     *   [port]      80
     *   [user]      username
     *   [pass]      password
     *   [path]      the dir and filename
     *   [query]     after the question mark ?
     *   [fragment]  after the hashmark #
     */
    public static function urlParse($uri) {
        if(is_string($uri) && strlen($uri = trim($uri))) {
            /*  PHP's URL parser returns something like:
              [scheme]    http
              [host]		www.myserver.com
              [port]		80
              [user]		username
              [pass]		password
              [path]		the dir, filename & extension
              [query]		after the question mark ?
              [fragment]	after the hashmark #
             */
            $parts = parse_url(self::urlComplete($uri));
            if(false === $parts || !is_array($parts)) {
                $valid = false;
                $parts = [];
            }
            else {
                $valid = true;
            }
        }
        else {
            $valid = false;
            $parts = [];
        }
        // Fill in missing parts with a blank string.
        foreach(self::$_url_parts_keys as $key) {
            if(!isset($parts[$key])) {
                $parts[$key] = "";
            }
        }
        if($valid) {
            // $parts["path"] = str_replace("\\", "/", $parts["path"]) ;
            $parts["query"] = is_null($parts["query"]) ? "" : trim(strval($parts["query"]));
            $parts["fragment"] = is_null($parts["fragment"]) ? "" : trim(strval($parts["fragment"]));
            $parts["scheme"] = strtolower(self::urlParseScheme($parts["scheme"]));
        }
        return $parts;
    }

    /**
     * Expands an array of URI path parts like those returned by 'urlParse()' into a complete URI.
     * Specify array of path parts in '$default_parts ' when you want to fill in items missing from
     * the '$parts' parameter.
     *
     * @param array $parts           URL parts array to expand.
     * @param mixed $default_parts   Default URL parts used when missing parts found in '$parts'
     *
     * @return string    Returns URL.
     */
    public static function urlExpandParts($parts, $default_parts = null) {
        // Convert keys to same case; lower case.
        $parts = is_array($parts) ? array_change_key_case($parts) : [];

        // Fill in missing url parts with values from '$default_parts' or assign blank
        // string to missing or null parts.
        // Convert keys to same case; lower case.
        $default_parts = (is_array($default_parts) && !empty($default_parts)) ?
            array_change_key_case($default_parts) : [];
        foreach(self::$_url_parts_keys as $key) {
            if(!isset($parts[$key]) || Types::isBlank($parts[$key])) {
                $parts[$key] = (isset($default_parts[$key]) && !empty($default_parts[$key])) ? $default_parts[$key] : "";
            }
        }

        if(empty($parts["port"])) {
            $parts["port"] = "";
        }
        else {
            // Specify the port if the port number is not the default port for the scheme e.g. port 80 for 'http'
            $parts["port"] = self::urlValidatePort($parts["port"]);
            if(false === $parts["port"]) {
                // Error: invalid port.
                trigger_error("In " . __FUNCTION__ . " line " . __LINE__
                    . ": invalid 'port' value in '\$parts' parameter '"
                    . Types::getVartype($parts["port"])
                    . "'", E_USER_WARNING);
                $parts["port"] = "";
            }
            else {
                $parts["port"] = ":" . $parts["port"];
            }
        }

        $parts["scheme"] = empty($parts["scheme"]) ? "" : ($parts["scheme"] . "://");

        // Authorization - not secure in over http, more secure
        // over https, but still not fully secure.
        if(strlen($parts["pass"])) {
            $parts["user"] .= ":" . $parts["pass"] . "@";
        }
        if(strlen($parts["query"])) {
            $parts["query"] = '?' . $parts["query"];
        }
        if(strlen($parts["fragment"])) {
            $parts["fragment"] = '#' . $parts["fragment"];
        }
        return $parts["scheme"] . $parts["user"] . $parts["host"] . $parts["port"]
            . str_replace("\\", "/", $parts["path"]) . $parts["query"] . $parts["fragment"];
    }

    public static function urlValidatePort($port) {
        if(!is_int($port)) {
            if(is_float($port)) {
                return ($port < 1.0 || $port > 65535.0) ? false : intval($port);
            }
            if(!is_string($port)) {
                return false;
            }
            $port = trim($port);
            $len = strlen($port);
            if($len < 1 || $len > 5 || !ctype_digit($port)) {
                return false;
            }
            $port = intval($port);
        }
        return ($port < 1 || $port > 65535) ? false : $port;
    }

    /**
     * Return standard/default port number for the specified protocol scheme.
     *
     * @param string $scheme    Protocol scheme for which to return standard/default port.
     *
     * @return mixed    Returns integer standard/default port or NULL if scheme unrecognized.
     */
    public static function urlGetSchemePort($scheme) {
        if(!is_string($scheme)) {
            return null;
        }
        $lower = strtolower(trim($scheme));
        // Find the standard/default port for the scheme.
        return isset(self::$_scheme_ports[$lower]) ? self::$_scheme_ports[$lower] : null;
    }

    public static function urlParseScheme($scheme) {
        return (!is_string($scheme) || !strlen($scheme = trim($scheme))) ? "" : preg_replace('/^[ \\t]*([a-zA-Z]+)[ \\t]*[\\:].*$/', "$1", $scheme);
    }

    /**
     * Builds an error message describing an invalid function parameter.
     *
     * @param int    $errno         Error number.
     * @param string $name          The name of the invalid parameter argument.
     * @param string $value         The value of the invalid parameter argument.
     * @param string $extra_msg     An extra message appended to the error message.
     *
     * @return string Error message.
     *
     * @example
     * _buildParameterErrorMsg(Constant::E_PARAMETER_EMPTY, 'filename', null,
     *     $extra_msg = 'expecting a filename')
     *
     * returns:
     *
     * empty 'filename' function parameter '(empty)' - parameter required: expecting a filename
     */
    public static function buildParameterErrorMsg($errno, $name, $value, $extra_msg = "") {
        if(! is_numeric($errno)) {
            $errno = Constant::E_PARAMETER_INVALID;
        }
        if(! is_string($name) || ! strlen($name = trim($name))) {
            $name = '(???)';
        }
        elseif(strlen($name) > 64) {
            $name = substr($name, 0, 60) . '...';
        }
        $value = Types::getVartype($value);
        if(!is_scalar($extra_msg) || is_bool($extra_msg)) {
            $extra_msg = Types::getVartype($extra_msg, 64);
        }
        else {
            $extra_msg = strval($extra_msg);
            if(strlen($extra_msg) > 64) {
                $name = substr($extra_msg, 0, 64) . '...';
            }
        }

        switch($errno) {
        case Constant::E_PARAMETER_EMPTY:
            // empty function parameter - parameter required
            // empty '%s' function parameter '%s' - parameter required
            $errstr = sprintf(Constant::T_PARAMETER_EMPTY, $name, $value);
            break;
        case Constant::E_TYPE_MISMATCH:
            // function parameter type mismatch
            // parameter type mismatch '%s' for function parameter '%s'
            $errstr = sprintf(Constant::T_PARAMETER_TYPE_MISMATCH, $value, $name);
            break;
        default: // Constant::E_PARAMETER_INVALID
            // invalid function parameter
            // invalid '%s' parameter '%s'
            $errstr = sprintf(Constant::T_PARAMETER_INVALID, $name, $value);
        }
        if(!empty($extra_msg)) {
            $errstr .= ": " . $extra_msg;
        }
        return $errstr;
    }
}
