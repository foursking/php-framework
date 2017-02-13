<?php

namespace DongPHP\System;

use DongPHP\System\Libraries\DataConfigLoader;
use DongPHP\System\Libraries\Memcache;
use DongPHP\System\Libraries\Redis;

class Data
{
    /**
     * @param $key
     * @param null $hash
     * @return \Redis
     */
    public static function redis($key, $hash=null)
    {
        $config = DataConfigLoader::redis($key, $hash);
        return Redis::getInstance($config['host'], $config['port'], $config['timeout'], $config['auth']);
    }

    /**
     * @param $key
     * @param null $hash
     * @return \Memcache
     */
    public static function memcache($key, $hash=null)
    {
        $config = DataConfigLoader::memcache($key, $hash);
        return Memcache::getInstance($config['host'], $config['port']);
    }
}
