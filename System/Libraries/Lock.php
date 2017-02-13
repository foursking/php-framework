<?php


namespace DongPHP\System\Libraries;


use DongPHP\System\Data;
use DongPHP\System\Libraries\MemcacheException;

class Lock
{
    const M_LOCK = 'lock:';

    public static function add($key, $time=3)
    {
        
        for ($i = 1 ; $i < 4; $i++) {

            $flag = Data::memcache('xydb.lock')->add(self::M_LOCK . $key, 1, 0, $time);

            if(! $flag) {
                usleep(200000);
            }else{
                return $i;
            }
        }

        return false;
    }

    public static function del($key)
    {
        Data::memcache('xydb.lock')->delete(self::M_LOCK . $key);
    }



    public static function addOrderReq($key , $time) {
        return Data::memcache('xydb.lock')->add(self::M_LOCK . $key, 1, 0, $time);
    }



    
}
