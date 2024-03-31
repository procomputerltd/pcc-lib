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
    Description : Builds an HTML form element.
    ToDo:         Write 'assemble()' method to assemble a URL.
*/
namespace Procomputer\Pcclib;

/**
 * Parses file path and/or URL information from a string.
 */
class PathInfo {

    const URI_TYPE_IMAGE = 1;
    const URI_TYPE_MAIL = 2;
    const URI_TYPE_URL = 3;
    const URI_TYPE_PHOTO = 4;
    const URI_TYPE_VIDEO = 5;

    protected $_images = array('jpeg' => 'jpeg', 'jpg' => 'jpg', 'gif' => 'gif', 'png' => 'png');

    protected $_videoMediaSites = array('vimeo.com', 'youtube.com', 'youtu.be', 'vevo.com', 'veoh.com', 'metacafe.com', 'break.com', 'flickr.com');

    protected $_scheme = '';
    protected $_host = '';
    protected $_port = '';
    protected $_user = '';
    protected $_pass = '';
    protected $_path = '';
    protected $_query = '';
    protected $_fragment = '';
    protected $_extension = '';
    protected $_type = '';

    public function __construct($url = null) {
        if(null !== $url) {
            $this->setPath($url);
            $this->parseUrl($url);
        }
    }

    public function parseUrl($url) {
        $default = array(
            'scheme' => '',
            'host' => '',
            'port' => '',
            'user' => '',
            'pass' => '',
            'path' => '',
            'query' => '',
            'fragment' => '',
            'extension' => '',
            'type' => null
            );

        $parts = parse_url($url);
        if(! is_array($parts)) {
            return;
        }

        $elements = array_merge($default, $parts) ;

        if(strlen($elements['path'])) {
            $elements['extension'] = strtolower(pathinfo($elements['path'], PATHINFO_EXTENSION));
            if(isset($this->_images[$elements['extension']])) {
                $elements['type'] = self::URI_TYPE_IMAGE;
            }
        }

        if(null === $elements['type']) {
            if(strlen($elements['host'])) {
                $host = strtolower($elements['host']);
                if(preg_match('/^www[0-9]*\.(.*)$/', $host, $matches)) {
                    $host = $matches[1];
                }
                if( false !== array_search($host, $this->_videoMediaSites)) {
                    if('flickr.com' === $host && (false === strpos(strtolower($elements['path']), 'video'))) {
                        $elements['type'] = self::URI_TYPE_PHOTO;
                    }
                    else {
                        // https://www.flickr.com/explore/video/
                        $elements['type'] = self::URI_TYPE_VIDEO;
                    }
                }
            }
        }
        if(null === $elements['type']) {
            $elements['type'] = self::URI_TYPE_URL;
        }

        foreach($elements as $key => $val) {
            $set = 'set' . ucfirst($key);
            $this->$set($val);
        }
        return;
    }

    /**
     * Returns property '_scheme'
     *
     * @return string
     *
     */
    public function getScheme() {
        return $this->_scheme ;
    }

    /**
     * Sets property '_scheme'
     *
     * @param string $scheme
     *
     * @return PathInfo
     */
    public function setScheme($scheme) {
        $this->_scheme = $scheme ;
        return $this ;
    }

    /**
     * Returns property '_host'
     *
     * @return string
     *
     */
    public function getHost() {
        return $this->_host ;
    }

    /**
     * Sets property '_host'
     *
     * @param string $host
     *
     * @return PathInfo
     */
    public function setHost($host) {
        $this->_host = $host ;
        return $this ;
    }

    /**
     * Returns property '_port'
     *
     * @return string
     *
     */
    public function getPort() {
        return $this->_port ;
    }

    /**
     * Sets property '_port'
     *
     * @param string $port
     *
     * @return PathInfo
     */
    public function setPort($port) {
        $this->_port = $port ;
        return $this ;
    }

    /**
     * Returns property '_user'
     *
     * @return string
     *
     */
    public function getUser() {
        return $this->_user ;
    }

    /**
     * Sets property '_user'
     *
     * @param string $user
     *
     * @return PathInfo
     */
    public function setUser($user) {
        $this->_user = $user ;
        return $this ;
    }

    /**
     * Returns property '_pass'
     *
     * @return string
     *
     */
    public function getPass() {
        return $this->_pass ;
    }

    /**
     * Sets property '_pass'
     *
     * @param string $pass
     *
     * @return PathInfo
     */
    public function setPass($pass) {
        $this->_pass = $pass ;
        return $this ;
    }

    /**
     * Returns property '_path'
     *
     * @return string
     *
     */
    public function getPath() {
        return $this->_path ;
    }

    /**
     * Sets property '_path'
     *
     * @param string $path
     *
     * @return PathInfo
     */
    public function setPath($path) {
        $this->_path = $path ;
        return $this ;
    }

    /**
     * Returns property '_query'
     *
     * @return string
     *
     */
    public function getQuery() {
        return $this->_query ;
    }

    /**
     * Sets property '_query'
     *
     * @param string $query
     *
     * @return PathInfo
     */
    public function setQuery($query) {
        $this->_query = $query ;
        return $this ;
    }

    /**
     * Returns property '_fragment'
     *
     * @return string
     *
     */
    public function getFragment() {
        return $this->_fragment ;
    }

    /**
     * Sets property '_fragment'
     *
     * @param string $fragment
     *
     * @return PathInfo
     */
    public function setFragment($fragment) {
        $this->_fragment = $fragment ;
        return $this ;
    }

    /**
     * Returns property '_extension'
     *
     * @return string
     *
     */
    public function getExtension() {
        return $this->_extension ;
    }

    /**
     * Sets property '_extension'
     *
     * @param string $extension
     *
     * @return PathInfo
     */
    public function setExtension($extension) {
        $this->_extension = $extension ;
        return $this ;
    }

    /**
     * Returns the file content type, a URI_TYPE_* constant.
     *
     * @return int Returns a URI_TYPE_* constant e.g. URI_TYPE_URL
     *
     */
    public function getType() {
        return $this->_type ;
    }

    /**
     * Sets the file content type, a URI_TYPE_* constant.
     *
     * @param int $type A URI_TYPE_* constant e.g. URI_TYPE_URL
     *
     * @return PathInfo
     */
    public function setType($type) {
        $this->_type = $type ;
        return $this ;
    }

}