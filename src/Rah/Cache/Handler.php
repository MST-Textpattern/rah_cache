<?php

/**
 * Cache handler.
 *
 * Gets the page from cache and sets options. This file should
 * be included and used via Textpattern's config.php file.
 *
 * @example
 * new Rah_Cache_Handler(array(
 *     'path' => './cache',
 *     'skip' => array('file_download/')
 * ));
 */

class Rah_Cache_Handler
{
    /**
     * Constructor.
     *
     * @param array $opt Options
     */

    public function __construct($opt)
    {
        global $rah_cache;
        $rah_cache = $opt;

        if (txpinterface != 'public' || !empty($_POST) || !empty($_GET) || !empty($_COOKIE['txp_login_public']))
        {
            return;
        }

        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        $md5 = md5($request_uri);
        $filename = $file = $rah_cache['path'] . '/' . $md5 . '.rah';
        $encoding = $this->encoding();

        if ($encoding)
        {
            $filename = $file . '.gz';
        }

        if (file_exists($filename))
        {
            $modified = filemtime($filename);

            if (
                $modified > time()-2592000 && 
                $modified >= (int) @file_get_contents($rah_cache['path'] . '/_lastmod.rah')
            )
            {
                header('Content-type: text/html; charset=utf-8');
                
                if ($encoding)
                {
                    header('Content-Encoding: '.$encoding);
                }

                die(file_get_contents($filename));
            }
        }

        if (
            !file_exists($rah_cache['path']) || 
            !is_dir($rah_cache['path']) || 
            !is_writeable($rah_cache['path'])
        )
        {
            return;
        }

        $rah_cache['file'] = $file;
        $rah_cache['request_uri'] = $request_uri;
        $rah_cache['cache_key'] = $md5;
    }

    /**
     * Check accepted encoding headers.
     *
     * @return bool
     */

    public function encoding()
    {
        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || headers_sent())
        {
            return false;
        }

        $accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];

        if (strpos($accept_encoding, 'x-gzip') !== false)
        {
            return 'x-gzip';
        }

        if (strpos($accept_encoding, 'gzip') !== false)
        {
            return 'gzip';
        }

        return false;
    }
}