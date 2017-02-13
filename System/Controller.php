<?php
/**
 * this is part of xyfree
 *
 * @file Controller.php
 * @use   初始控制器
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2015-10-30 10:08
 *
 */

namespace DongPHP\System;

use Pimple\Container;

abstract class Controller extends Data
{
    public $logger;
    public $container;

    public function __construct()
    {
        $this->logger    = Logger::get('system');
        $this->container = new Container();
    }

    protected function outJson($data)
    {
        if (IS_DEBUG) {
            var_dump($this->toString($data));
        } else {
            $out = json_encode($this->toString($data));
            //header("Content-Length:" . strlen($out));
            header("Content-type: application/json;charset=utf-8");
            echo $out;
        }
    }

    protected function outError($msg, $code = 404)
    {
        throw new \Exception($msg, $code);
    }

    protected function outResult($result, $code = 200)
    {
        $data['code'] = $code;
        $data['data'] = $result;
        $data['time'] = time();
        $this->outJson($data);
    }

    protected function setProperty($property, $callable) {
        $this->container[$property] = $this->container->factory($callable);
        unset($this->$property);
    }

    public function __get($key)
    {
        static $obj;
        if ( !isset($obj[$key]) ) {
            $obj[$key] = $this->container[$key];
        }
        return $obj[$key];
    }


    protected function toString($data)
    {
        foreach ($data as &$val) {
            if (is_array($val)){
                $val = $this->toString($val);
            } else {
                $val = "$val";
            }
        }

        return $data;
    }
}
