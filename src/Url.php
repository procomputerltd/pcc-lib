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
    Description : Helper to build URI strings.
*/
namespace Procomputer\Pcclib;

/**
 * Helper to build URI strings.
 */
class Url {

    public $protocol = "";            // http
    public $scheme = "";              // (alias for protocol)
    public $host = "";                // example.com
    public $port = "";                // 80
    public $user = "";                // user name embedded in the url
    public $password = "";            // password embedded in the url
    public $pathname = "";            // Script path: /phpMyAdmin/phpinfo.php
    public $query = "";               // arguments that follow the question mark after a url.
    public $fragment = "";            // Hashmark '#'.
    public $host_and_port = "";       // example.com:4280
    public $protocol_host_and_port = ""; // http://example.com:4280
    public $server_url = "";          // (alias for above)
    public $href = "";                // Full script href: http://example.com:4280/basedir/index.php
    // The $_SERVER variables are always stored here during class construction.
    public $ServerUrl = "";           // http://www.example.com | http://localhost:4280
    public $ScriptHref = "";          // http://www.example.com:4280/folder/index.php
    public $ScriptAbsPath = "";       // /public/www/html/images/index.php  | D:/INETPUB/091/images/index.php (Win2K)
    public $ServerAbsPath = "";       // /public/www/html | D:/INETPUB/091 (Win2K)
    public $ScriptDirAbsPath = "";    // /public/www/html/images | D:/INETPUB/091/images (Win2K)
    public $ScriptVirtualPath = "";   // /images/index.php

    const DEFAULT_HTTP_PORT = 80;     // Default HTTP port number.
    const DEFAULT_HTTPS_PORT = 443;   // Default HTTPS SSL port number.

    /**
     * Constructor
     */

    public function __construct($uri = null) {
        if(!empty($uri)) {
            $this->assign($uri);
        }
        /* ------------------------------------------------------------
          Retrieve the the absolute and relative paths for the current
          script name and the root, script name paths.

          Winblow-IIS sample SERVER variable values

          HTTP_HOST            example.com
          HTTPS				 off
          PATH_INFO           	 /phpMyAdmin/phpinfo.php
          PATH_TRANSLATED     	 D:\\INETPUB\\091\\phpMyAdmin\\phpinfo.php
          SCRIPT_NAME         	 /phpMyAdmin/phpinfo.php
          SERVER_NAME          pccglobal.com
          SERVER_PORT         	 80
          SERVER_PORT_SECURE  	 0
          SERVER_PROTOCOL     	 HTTP/1.1
          SERVER_SOFTWARE     	 Microsoft-IIS/5.0
          PHP_SELF             /phpMyAdmin/phpinfo.php

          Linux/Apache sample SERVER variable values

          HTTP_HOST			 example.com
          PATH            		 /usr/local/bin:/usr/bin:/bin
          DOCUMENT_ROOT   		 /www/g/u/example.com/htdocs
          REDIRECT_URL    		 /basedir/index.php
          SCRIPT_FILENAME 		 /www/g/u/example.com/htdocs/basedir/index.php
          SCRIPT_NAME     		 /basedir/index.php
          SERVER_NAME     	 example.com
          SERVER_PORT     		 80
          SERVER_PROTOCOL 		 HTTP/1.1
          REQUEST_URI     		 /basedir/
          PATH_INFO       		 no value
          PATH_TRANSLATED 		 no value
          ORIG_PATH_TRANSLATED /www/g/u/example.com/htdocs/basedir/index.php
          ORIG_PATH_INFO       /basedir/index.php
          ORIG_SCRIPT_NAME     /powweb-bin/php
          ORIG_SCRIPT_FILENAME /powweb/web/cgi-bin/php
          PHP_SELF             /basedir/index.php
          ------------------------------------------------------------ */
        $this->ScriptAbsPath = $path = System::getScriptAbsPath(); // E.g. '/var/www/html/phpMyAdmin/phpinfo.php'
        $this->ScriptDirAbsPath = dirname($path); // E.g. '/var/www/html/phpMyAdmin/'
        $this->ScriptVirtualPath = System::getScriptVirtualPath(); // E.g. '/phpMyAdmin/phpinfo.php'
        $this->ServerAbsPath = System::getServerAbsPath(); // E.g. '/var/www/html' and 'D:/INETPUB/091'
        $this->ServerUrl = System::getServerUrl(); // E.g. 'http://www.example.com'
        $this->ScriptHref = System::getScriptHref(); // E.g. 'http://www.example.com/myfolder/index.php'
    }

    /**
     *  Assigns a new url to this object.
     */
    public function assign($uri) {
        $parts = $this->parse($uri);
        $this->host = $parts["host"];
        $this->port = $parts["port"];
        $this->protocol = $parts["scheme"];
        $this->scheme = $this->protocol; // Alias for protocol
        $this->user = $parts["user"];
        $this->password = $parts["pass"];
        $this->pathname = $parts["path"];
        $this->query = $parts["query"];
        $this->fragment = $parts["fragment"];
        if(strlen($this->host) && strlen($this->protocol)) {
            // pccglobal.com:4280
            $this->host_and_port = $this->host . ((empty($this->port) || 80 == $this->port) ? "" : (":" . $this->port));
            // http://pccglobal.com:4280
            $this->protocol_host_and_port = $this->protocol . "://" . $this->host_and_port;
            $this->server_url = $this->protocol . "://" . $this->host_and_port;
            // http://pccglobal.com:4280/basedir/index.php
            $this->href = $this->protocol_host_and_port . $this->pathname;
        }
    }

    /**
     * Expands this class's path parts into a complete URI.
     */
    public function expand() {
        $parts = array(
            "scheme" => $this->protocol,
            "host" => $this->host,
            "port" => $this->port,
            "user" => $this->user,
            "pass" => $this->password,
            "path" => $this->pathname,
            "query" => $this->query,
            "fragment" => $this->fragment);
        return $this->expand_parts($parts);
    }

    /**
     * Expands an array of URI path parts into a complete URI.
     * Specify default_parts array of path parts when you want to
     * fill in missing items in the 'parts' parameter.
     */
    public static function expand_parts($parts, $default_parts = null) {
        if(!is_array($parts)) {
            $parts = array();
        }
        $ary = array("scheme", "host", "port", "user", "pass", "path", "query", "fragment");
        foreach($ary as $key) {
            $$key = isset($parts[$key]) ? $parts[$key] : "";
        }

        if(!is_null($default_parts) && is_array($default_parts)) {
            if(!strlen($host) && isset($default_parts["host"])) {
                $host = $default_parts["host"];
            }
            if(!strlen($port) && isset($default_parts["port"])) {
                $port = $default_parts["port"];
            }
            if(!strlen($scheme) && isset($default_parts["scheme"])) {
                $scheme = $default_parts["scheme"];
            }
        }

        $scheme = strlen($scheme) ? strtolower(self::_parseScheme($scheme)) : "http";

        // Specify the port if the port number is not the default port for the scheme e.g. port 80 for 'http'
        $port = self::_getPort($port, $scheme);
        $port = empty($port) ? "" : (":" . $port);

        // Authorization - not secure in over http, more secure
        // over https, but still not fully secure.
        if(strlen(trim($user))) {
            $user .= ":" . $pass . "@";
        }
        if(strlen($query)) {
            $query = "?" . $query;
        }
        if(strlen($fragment)) {
            $fragment = "#" . $fragment;
        }
        return $scheme . "://" . $user . $host . $port . str_replace("\\", "/", $path) . $query . $fragment;
    }

    /**
     * parse
     *
     * Parses a uri and returns an array with the following elements (items missing in the uri are blank):
     * [scheme]    - http
     * [host]      - www.myserver.com
     * [port]      - 80
     * [user]      - username
     * [pass]      - password
     * [path]      - the dir and filename
     * [query]     - after the question mark ?
     * [fragment]  - after the hashmark #
     */
    public static function parse($uri) {
        if(isset($uri) && is_string($uri) && strlen($uri = trim($uri))) {
            /*  PHP's parse_url() returns:
              [scheme]	http
              [host]		www.myserver.com
              [port]		80
              [user]		username
              [pass]		password
              [path]		the dir, filename & extension
              [query]		after the question mark ?
              [fragment]	after the hashmark #
             */

            // Fix bad syntax like: http:////p.example.net\images\pretty.png
            // Allow syntax like: file:///C:\Windows\pretty.png
            $uri = preg_replace('~////+~', '//', str_replace("\\", "/", $uri));
            $parts = parse_url($uri);
            if(false === $parts || !is_array($parts)) {
                unset($parts);
            }
        }
        if(isset($parts)) {
            $valid = true;
        }
        else {
            $parts = array();
        }
        $ary = array("scheme", "host", "port", "user", "pass", "path", "query", "fragment");
        foreach($ary as $key) {
            if(!isset($parts[$key])) {
                $parts[$key] = "";
            }
        }

        if(isset($valid)) {
            $parts["path"] = str_replace("\\", "/", $parts["path"]);
            $parts["query"] = self::_trim_mixed($parts["query"]);
            $parts["fragment"] = self::_trim_mixed($parts["fragment"]);
            $parts["scheme"] = strtolower(self::_parseScheme($parts["scheme"]));
        }
        return $parts;
    }

    public static function urlGetSchemePort($scheme) {
        if(empty($scheme)) {
            return null; // null means can't determine.
        }
        switch(strtolower($scheme))
        {
            case 'http':
                return 80;
            case 'https':
                return 443;
            case 'ftp':
                return 21;
            case 'imap':
                return 143;
            case 'imaps':
                return 993;
            case 'pop3':
                return 110;
            case 'pop3s':
                return 995;
            default:
                return null; // Unknown scheme.
        }
    }

    /**
     * getDefaultPort
     *
     * Returns one of the following:
     *   Port number     The value in the 'port' parameter if valid or the default port for the 'scheme' parameter.
     *   FALSE           The 'port' parameter is invalid.
     *   Empty string    The 'scheme' parameter is unrecognized.
     *
     * Parameters
     *   port            Port number or null to get default port scheme.
     *   scheme          Protocol scheme e.g. 'http'
     *
     */
    public static function getDefaultPort($port, $scheme) {
        if(!empty($port)) {
            // false means invalid.
            // integer means valid.
            return (!is_numeric($port) || ($f = floatval($port)) < 1 || $f > 65535) ? false : intval($f);
        }
        // urlGetSchemePort returns either a number or null.
        return System::urlGetSchemePort($scheme);
    }

    /**
     * Complete
     *
     * Completes a uri when the uri is incomplete
     *
     * For normal url, adds the scheme(http://) if it's missing.
     *
     * When the argument is a Winblow filename, _complete_uri returns "file:///$uri" ;
     */
    public static function Complete($uri) {
        if(!isset($uri) || !strlen($uri = ltrim(strval($uri)))) {
            return "";
        }
        if(preg_match('/^[a-z]\:.*$/i', $uri)) {
            return "file:///" . str_replace("\\", "/", $uri);
        }
        $parts = self::parse($uri);
        return self::expand_parts($parts);
    }

    public static function urlRemoveHashFragment($uri) {
        if(!empty($uri) && strlen(trim($uri)) && false !== ($offset = strrpos($uri, "#"))) {
            $qry_offset = strrpos($uri, "?");
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
     * Removes the query parameters and #hashtag from a URI
     *
     * @param string $uri   String from which to remove query parameters.
     *
     * @return string
     */
    public static function urlRemoveQuery($uri) {
        if(!empty($uri) && strlen(trim($uri)) && false !== ($offset = strrpos($uri, "?"))) {
            $uri = substr($uri, 0, $offset);
        }
        return $uri;
    }

    /**
     * Assembles a URL query string.
     *
     * @param array   $params Query parameters
     * @param boolean $encode (optional) Encode the query string.
     *
     * @return string Returns assembled URL query string.
     */
    public static function urlAssembleQuery(array $params, $encode = false) {
        array_walk($params, function(&$v, $k) {$v = $k . '=' . $v;});
        $return = implode('&', $params);
        if($encode) {
            $return = urlencode($return);
        }
        return $return;
    }

    /**
     * Returns the server host full url like 'http://www.example.com'
     *
     * @param boolean $secure    (optional) If NULL the protocol is used: HTTP or HTTPS. If TRUE (or evaluates TRUE)
     *                                      protocol HTTPS is used. If FALSE (or evaluates FALSE) protocol HTTP is used.
     *
     * @param boolean $http_port (optional) If specified and valid (1-65535) this port is included in the URL. If
     *                                      unspecified the current port is used.
     *
     * @return string
     *
     * Sample server vars for UNIX/Linux server:       HTTPS enabled
     * ---------------------------------------------   ---------------------------------------
     * $_SERVER["HTTP_HOST"]   www.example.com         $_SERVER["HTTP_HOST"]   www.example.com
     * $_SERVER["SERVER_NAME"] www.example.com         $_SERVER["SERVER_NAME"] www.example.com
     * $_SERVER["HTTPS"]       (null)                  $_SERVER["HTTPS"]       on
     * getenv("HTTPS")         (false)                 getenv("HTTPS")         on
     * $_SERVER["SERVER_PORT"] 80                      $_SERVER["SERVER_PORT"] 443
     *
     * Sample server vars for WindBlow 2003 server:    HTTPS enabled
     * ---------------------------------------------   ---------------------------------------
     * $_SERVER["HTTP_HOST"]   localhost               $_SERVER["HTTP_HOST"]   localhost
     * $_SERVER["SERVER_NAME"] localhost               $_SERVER["SERVER_NAME"] localhost
     * $_SERVER["HTTPS"]       off                     $_SERVER["HTTPS"]       on
     * getenv("HTTPS")         (false)                 getenv("HTTPS")         (false)
     * $_SERVER["SERVER_PORT"] 80                      $_SERVER["SERVER_PORT"] 443
     */
    public static function getServerUrl($secure = null, $http_port = null) {
        static $host, $cur_secure, $cur_port;
        if(!isset($host)) {
            $host = System::getServerHost(); // E.g. 'www.example.com', 'locahost:3783'
            if(!empty($host) && false !== strpos($host, ":")) {
                // Remove the :nnnn port specifier.
                $host = preg_replace('/\s*\:[0-9]+\s*$/', "", $host);
            }
            if(empty($host)) {
                unset($host);
                // PHP is corrupted?
                return "";
            }

            $cur_secure = System::RequestIsHttps();

            if(isset($_SERVER["SERVER_PORT"])) {
                $cur_port = intval($_SERVER["SERVER_PORT"]);
            }
            if(!isset($cur_port)) {
                $cur_port = ($cur_secure ? self::DEFAULT_HTTPS_PORT : self::DEFAULT_HTTP_PORT);
            }
        }
        if(null === $secure) {
            $secure = $cur_secure;
            $port = $cur_port;
        }
        else {
            $secure = (bool)$secure;
            $port = ($secure == $cur_secure) ? $cur_port : ($secure ? self::DEFAULT_HTTPS_PORT : self::DEFAULT_HTTP_PORT);
        }
        if(!empty($http_port) && is_numeric($http_port)) {
            $port = $http_port;
            $use_port = true;
        }
        $uri = ($secure ? "https" : "http") . "://" . $host;
        if(isset($use_port) || $port != ($secure ? self::DEFAULT_HTTPS_PORT : self::DEFAULT_HTTP_PORT)) {
            $uri .= ":" . $port;
        }
        return $uri;
    }

    /**
     * Returns the current script's full server url like 'http://www.example.com/myfolder/index.php'
     *
     * @param boolean $secure            (optional) Specify secure protocol https.
     * @param int     $port              (optional) Port number
     * @param boolean $remove_hash       (optional) When TRUE #hashtag is removed.
     * @param boolean $remove_query      (optional) When TRUE query parameters are removed.
     *
     * @return string
     */
    public static function getScriptHref($secure = null, $port = null, $remove_hash = false, $remove_query = false) {
        $path = System::getScriptVirtualPath();
        if($remove_query || $remove_hash) {
            $path = $remove_query ? System::urlRemoveQuery($path) : System::urlRemoveHashFragment($path);
        }
        return System::getServerUrl($secure, $port) . "/" . preg_replace('@^/+@', '', $path);
    }

    /**
     * Return TRUE when the current request is secure HTTPS protocol.
     *
     * @staticvar boolean $https Holds TRUE when the current request is secure HTTPS protocol.
     *
     * @return boolean
     */
    public static function RequestIsHttps() {
        static $https;
        if(!isset($https)) {
            $https = isset($_SERVER["HTTPS"]) ? trim(strval($_SERVER["HTTPS"])) : "";
            if(empty($https)) {
                $https = getenv("HTTPS");
                $https = empty($https) ? "" : trim(strval($https));
            }
            if(empty($https)) {
                $https = false;
            }
            else {
                $https = is_numeric($https) ? (bool)intval($https) : (bool)(!strcmp(strtolower($https), "on"));
            }
        }
        return $https;
    }

    /**
     * GetScriptAbsPath
     *
     * Returns the current script's absolute server path like '/var/www/html/phpMyAdmin/phpinfo.php'
     */
    public static function getScriptAbsPath() {
        /* 	Name                    Non-MS sample           MS sample
          ----------------------  ----------------------  -----------------------------------
          SCRIPT_FILENAME	        /var/www/html/php.php   C:\Inetpub\wwwroot\php.php
          ORIG_PATH_TRANSLATED 	N/A                     C:\Inetpub\wwwroot\php.php
          PATH_TRANSLATED	        /var/www/html/php.php   C:\Inetpub\wwwroot
         */
        return self::_get_first_valid_server_value(array(
                "SCRIPT_FILENAME",
                "ORIG_PATH_TRANSLATED",
                "PATH_TRANSLATED"));
    }

    /**
     * GetScriptDirAbsPath
     *
     * Returns the current script's absolute directory path like '/var/www/html/phpMyAdmin'
     */
    public static function getScriptDirAbsPath() {
        return dirname(System::getScriptAbsPath());
    }

    /**
     * GetScriptVirtualPath
     *
     * Returns the current script's virtual path like '/phpMyAdmin/phpinfo.php'
     */
    public static function getScriptVirtualPath($remove_hash = false, $remove_query = false) {
        return self::_get_first_valid_server_value(array(
                "SCRIPT_NAME",
                "ORIG_PATH_INFO",
                "PATH_INFO",
                "PHP_SELF"));
    }

    /**
     * Returns the server's absolute directory path like '/var/www/html' and 'D:/INETPUB/091'
     *
     * @return string
     */
    public static function getServerAbsPath() {
        $path = System::getScriptAbsPath();
        $v_path = System::getScriptVirtualPath();
        return substr($path, 0, strlen($path) - strlen($v_path));
    }

    /**
     * Returns the current script's host name in $_SERVER[] values 'HTTP_HOST' or 'HTTP_X_FORWARDED_HOST'
     *
     * @return string
     */
    public static function getServerHost() {
        static $h;
        if(!isset($h)) {
            $h = "";
            $svr_keys = array("HTTP_X_FORWARDED_HOST", "HTTP_HOST");
            foreach($svr_keys as $key) {
                if(isset($_SERVER[$key])) {
                    $h = trim($_SERVER[$key]);
                    if(!empty($h)) {
                        break;
                    }
                }
            }
        }
        return $h;
    }

    /**
     * Returns the current script's host name (or COMPUTERNAME environment value in winblows Servers)
     *
     * @staticvar string $h
     *
     * @param boolean $omit_port
     *
     * @return string
     */
    public static function getHostName($omit_port = false) {
        static $h;
        if(!isset($h)) {
            /* 	Sample values returned by $_SERVER["HTTP_HOST"]:
              localhost
              COMPUTERNAME_123
              www.ci.anycity.wa.us
              www.treasury.gov.au
              www.example.com
              www.example.net
             */
            $h = System::getServerHost();
            if(empty($h)) {
                $h = array("", "");
            }
            else {
                $pos = strrpos($h, ":");
                if(false === $pos) {
                    $p = "";
                }
                else {
                    // 01234567890123456789
                    // www.example.com:3276
                    $p = substr($h, $pos); // INCLUDES THE COLON!
                    $h = substr($h, 0, $pos);
                }
                $a = explode(".", $h);
                while(count($a)) {
                    if(strcmp("www", strtolower($a[0]))) {
                        break;
                    }
                    // Omit 'www' subdomain(s).
                    array_shift($a);
                }
                $a = implode(".", $a);
                $h = array($a, $a . $p);
            }
        }
        return $omit_port ? $h[0] : $h[1];
    }

    /**
     * Returns the first valid non-blank SERVER value referenced by keys parameter.
     *
     * @param array $keys
     *
     * @return string
     */
    private static function _get_first_valid_server_value(array $keys) {
        foreach($keys as $key) {
            if(isset($_SERVER[$key])) {
                $value = trim(strval($_SERVER[$key]));
                if(strlen($value)) {
                    return $value;
                }
            }
        }
        return "";
    }

    /**
     * Returns a value convert to string and trimmed.
     * @param string $s
     * @return string
     */
    private static function _trim_mixed($s) {
        return is_null($s) ? "" : trim(strval($s));
    }

    protected function _parseScheme($scheme) {
        return $scheme;
    }
    
    /**
     * Returns one of the following:
     *   Port number     The value in the 'port' parameter if valid or the default port for the 'scheme' parameter.
     *   FALSE           The 'port' parameter is invalid.
     *   Empty string    The 'scheme' parameter is unrecognized or 'port' is the default port for the scheme.
     *
     * @param int    $port      Port number or null to get default port for scheme.
     * @param string $scheme    Protocol scheme e.g. 'http'
     *
     * @return string
     */
    private static function _getPort($port, $scheme) {
        $scheme = empty($scheme) ? "" : self::_parseScheme($scheme);
        $port = self::getDefaultPort($port, $scheme);
        // false means invalid port number.
        // empty string means invalid or empty scheme.
        if(false !== $port && is_int($port) && strlen($scheme)) {
            // $port is either a number or blank string.
            $std_port = System::urlGetSchemePort($scheme);
            if(is_int($std_port) && $std_port == $port) {
                // Return empty string when port is the standard port number.
                $port = "";
            }
        }
        return $port;
    }

}
