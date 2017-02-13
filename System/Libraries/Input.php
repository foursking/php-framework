<?php

namespace DongPHP\System\Libraries;

class Input
{

    public static function string($param, $default = null, $type = 'request')
    {
        $value = self::getValue($type);
        $tmp   = isset($value[$param]) ? trim($value[$param]) : (is_array($default) ? $default[0] : $default);
        if ( is_array($default) && !in_array($tmp, $default) ) {
            return $default[0];
        }
        return $tmp;
    }

    public static function int($param, $default = 0, $type = 'request')
    {
        $value = self::getValue($type);
        $tmp   = isset($value[$param]) ? intval($value[$param]) : (is_array($default) ? $default[0] : $default);
        if ( is_array($default) && !in_array($tmp, $default) ) {
            return $default[0];
        }
        return $tmp;
    }

    private static function getValue($type) {

        switch (strtoupper($type)) {
            case 'POST':
                $value = $_POST;
                break;
            case 'GET':
                $value = $_GET;
                break;
            case 'COOKIE':
                $value = $_COOKIE;
                break;
            case 'SESSION':
                $value = $_SESSION;
                break;
            default:
                $value = $_REQUEST;
                break;
        }
        return $value;
    }
} 
