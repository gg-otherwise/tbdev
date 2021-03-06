<?php

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// | This file is part of TBDevYSE. TBDevYSE is based on TBDev,               |
// | originally by RedBeard of TorrentBits, extensively modified by           |
// | Gartenzwerg.                                                             |
// |                                                                          |
// | TBDevYSE is free software; you can redistribute it and/or modify         |
// | it under the terms of the GNU General Public License as published by     |
// | the Free Software Foundation; either version 2 of the License, or        |
// | (at your option) any later version.                                      |
// |                                                                          |
// | TBDevYSE is distributed in the hope that it will be useful,              |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with TBDevYSE; if not, write to the Free Software Foundation,      |
// | Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

# IMPORTANT: Do not edit below unless you know what you are doing!
if (!defined('IN_ANNOUNCE'))
    die('Hacking attempt!');

require_once($rootpath . 'include/config.php');
require_once($rootpath . 'include/secrets.php');

function err($msg) {
    benc_resp(array("failure reason" => array('type' => 'string', 'value' => $msg)));
    exit();
}

function benc_resp($d) {
    benc_resp_raw(benc(array('type' => 'dictionary', 'value' => $d)));
}

function benc_resp_raw($x) {
    header('Content-Type: text/plain');
    header('Pragma: no-cache');
    print($x);
}

function get_date_time($timestamp = 0) {
    if ($timestamp)
        return date('Y-m-d H:i:s', $timestamp); else
        return date('Y-m-d H:i:s');
}

function gmtime() {
    return strtotime(get_date_time());
}

function strip_magic_quotes($arr) {
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            $arr[$k] = strip_magic_quotes($v);
        } else {
            $arr[$k] = stripslashes($v);
        }
    }

    return $arr;
}

function mksize($bytes) {
    if ($bytes < 1000 * 1024)
        return number_format($bytes / 1024, 2) . ' kB'; elseif ($bytes < 1000 * 1048576)
        return number_format($bytes / 1048576, 2) . ' MB';
    elseif ($bytes < 1000 * 1073741824)
        return number_format($bytes / 1073741824, 2) . ' GB';
    else
        return number_format($bytes / 1099511627776, 2) . ' TB';
}

function emu_getallheaders() {
    foreach ($_SERVER as $name => $value)
        if (substr($name, 0, 5) == 'HTTP_')
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
    return $headers;
}

function portblacklisted($port) {
    if ($port >= 411 && $port <= 413)
        return true;
    if ($port >= 6881 && $port <= 6889)
        return true;
    if ($port == 1214)
        return true;
    if ($port >= 6346 && $port <= 6347)
        return true;
    if ($port == 4662)
        return true;
    if ($port == 6699)
        return true;
    return false;
}

function validip($ip) {
    if (!empty($ip) && $ip == long2ip(ip2long($ip))) {
        $reserved_ips = array(array('0.0.0.0', '2.255.255.255'), array('10.0.0.0', '10.255.255.255'), array('127.0.0.0', '127.255.255.255'), array('169.254.0.0', '169.254.255.255'), array('172.16.0.0', '172.31.255.255'), array('192.0.2.0', '192.0.2.255'), array('192.168.0.0', '192.168.255.255'), array('255.255.255.0', '255.255.255.255'));

        foreach ($reserved_ips as $r) {
            $min = ip2long($r[0]);
            $max = ip2long($r[1]);
            if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max))
                return false;
        }
        return true;
    } else return false;
}

function getip() {
    return $_SERVER['REMOTE_ADDR'];
}

function dbconn() {
    global $mysql_host, $mysql_user, $mysql_pass, $mysql_db, $mysql_charset;
    if (!@mysql_connect($mysql_host, $mysql_user, $mysql_pass)) {
        err('dbconn: mysql_connect: ' . mysql_error());
    }
    mysql_select_db($mysql_db) or err('dbconn: mysql_select_db: ' . mysql_error());

    mysql_query('SET NAMES ' . $mysql_charset);

    register_shutdown_function('mysql_close');

}

function sqlesc($value) {
    // Stripslashes
    /*if (get_magic_quotes_gpc()) {
        $value = stripslashes($value);
    }*/
    // Quote if not a number or a numeric string
    if (!is_numeric($value)) {
        $value = "'" . mysql_real_escape_string($value) . "'";
    }
    return $value;
}

function hash_pad($hash) {
    return str_pad($hash, 20);
}

// Was used long long ago, now not needed.
// Disabled, but not deleted for quick restore in case of regression

/*function hash_where($name, $hash) {
    $shhash = preg_replace('/ *$/s', "", $hash);
    return "($name = " . sqlesc($hash) . " OR $name = " . sqlesc($shhash) . ")";
}*/

function unesc($x) {
    if (get_magic_quotes_gpc())
        return stripslashes($x);
    return $x;
}

function gzip() {
    if (@extension_loaded('zlib') && @ini_get('zlib.output_compression') != '1' && @ini_get('output_handler') != 'ob_gzhandler') {
        @ob_start('ob_gzhandler');
    }
}

// Check open port, requires --enable-sockets
function check_port($host, $port, $timeout) {
    if (function_exists('socket_create')) {
        // Create a TCP/IP socket.
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket == false) {
            return false;
        }
        //
        if (socket_set_nonblock($socket) == false) {
            socket_close($socket);
            return false;
        }
        //
        @socket_connect($socket, $host, $port); // will return FALSE as it's async, so no check
        //
        if (socket_set_block($socket) == false) {
            socket_close($socket);
            return false;
        }

        switch (socket_select($r = array($socket), $w = array($socket), $f = array($socket), $timeout)) {
            case 2:
                // Refused
                $result = false;
                break;
            case 1:
                $result = true;
                break;
            case 0:
                // Timeout
                $result = false;
                break;
        }

        // cleanup
        socket_close($socket);
    } else {
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$socket)
            $result = false; else {
            $result = true;
            @fclose($socket);
        }
    }

    return $result;
}

?>