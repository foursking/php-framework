<?php

namespace DongPHP\System;

class Helper
{
    public static function load($name)
    {
        if (is_file(APP_PATH.'Helper/'.$name.'.php')) {
            return require_once APP_PATH.'Helper/'.$name.'.php';
        } elseif (is_file(dirname(__FILE__).'/Helper/'.$name.'.php')) {
            return require_once dirname(__FILE__).'/Helper/'.$name.'.php';
        }
    }
}
