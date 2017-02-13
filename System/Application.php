<?php

namespace DongPHP\System;

use DongPHP\System\Libraries\Input;
use DongPHP\System\Libraries\Output;

if (!defined('APP_PATH')) {
    throw new \Exception('APP_PATH NOT defined!');
}

require_once __DIR__ . '/init.php';

class Application
{
    /**
     * 程序开始时间
     * @var mixed
     */
    protected $script_start_time;

    /**
     * 程序结束时间
     * @var mixed
     */
    protected $script_end_time;

    /**
     * 异常处理器
     * @var callable
     */
    protected $exceptions_capture;

    /**
     * 路由器
     * @var
     */
    protected $dispatcher;

    /**
     * controller的命名空间
     * @var
     */
    protected $controller_namespace = 'Application\Controller';

    /**
     * 默认控制器
     * @var
     */
    protected $default_controller = null;

    /**
     * 默认方法
     * @var
     */
    protected $default_method = null;

    /**
     * 日志类
     * @var \Monolog\Logger
     */
    public $logger;

    /**
     * 开始前要执行的方法
     * @callable
     */
    protected $before_callback;

    /**
     * 结束后要执行的方法
     * @callable
     */
    protected $end_callback;

    /**
     * 运行环境
     * @var
     */
    protected $environment;

    private $muti_version = null;

    public function __construct($namespace = '', $route = '')
    {
        require_once SYS_PATH . 'Config/' . ENVIRONMENT . '/constant.php';
        if ($namespace) {
            $this->setNamespace($namespace);
        }

        $this->script_start_time = microtime(true);
        $this->default_method    = 'index';

        if ($route) {
            $this->dispatcher = new Dispatcher($route, $this->controller_namespace);
        }

        $this->setLogger();
    }

    public function setNamespace($namespace)
    {
        $this->controller_namespace = ucfirst($namespace) . '\Controller';
        $this->default_controller   = $this->controller_namespace . '\IndexController';
    }

    public function setDispatcher($route)
    {
        if (IS_CLI) {
            return true;
        }
        $this->dispatcher = new Dispatcher($route, $this->controller_namespace);
    }


    public function setDefaultMethod($controller, $method)
    {
        $this->default_controller = $this->controller_namespace . '\\' . $controller;
        $this->default_method     = $method;
    }

    public function setLogger($logger = null)
    {
        if (is_null($logger)) {
            $logger = Logger::get('system');
        }
        $this->logger = $logger;
    }

    public function setExceptionsCapture(callable $callback)
    {
        $this->exceptions_capture = $callback;
    }

    public function beforeCallback(callable $callback)
    {
        $this->before_callback = $callback;
    }

    public function endCallback(callable $callback)
    {
        $this->end_callback = $callback;
    }

    public function setMutiVersion($version)
    {
        $this->muti_version = $version;
    }


    
    public function run()
    {
        if (!defined('ENVIRONMENT')) {
            throw new \Exception('ENVIRONMENT not defined');
        }

        //注册错误函数
        register_shutdown_function(function(){
            $error = error_get_last();
            if ($error['type'] === E_ERROR) {
                $this->logger->error(json_encode($error));
            }
        });


        $this->logger->debug('start:' . $this->script_start_time);
        if (is_callable($this->before_callback) && $before_callback = $this->before_callback) {
            $before_callback();
        }

        try {
            $route      = $this->dispatch();
            $controller = explode('\\', $route[0][0]);
            $path       = '';
            if (count($controller) > 2) {
                $path = array_slice($controller, 2, count($controller) - 3);
            }
            define('ROUTE_CLASS', str_replace('Controller', '', end($controller)));
            define('ROUTE_PATH', implode("/", $path));
            define('ROUTE_METHOD', $route[0][1]);
            $return = $this->execute($route);
            if ($return) {
                if (IS_CLI) {
                    echo json_encode(['code' => 200, 'data' => $return]);
                } else {
                    Output::json(['code' => 200, 'data' => $return]);
                }
            }
        } catch (\Exception $e) {
            if (is_callable($this->exceptions_capture)) {
                $capture = $this->exceptions_capture;
                $capture($e);
            } else {
                throw new $e;
            }
        }

        if (is_callable($this->end_callback) && $end_callback = $this->end_callback) {
            $end_callback();
        }

        $this->script_end_time = microtime(true);

        $script_use_time = $this->script_end_time - $this->script_start_time;

        if (IS_CLI === false) {
            $log = 'TIMEUSED:' . $script_use_time . '; SERVER_NAME:' . $_SERVER['SERVER_NAME'] . ';METHOD:' . $_SERVER['REQUEST_METHOD'] . '; URI:' . $_SERVER['REQUEST_URI'] . ';RAW:' . json_encode($_REQUEST);
            if ($script_use_time > 0.5) {
                $this->logger->alert($log);
            } else {
                $this->logger->debug($log);
            }
        }

        $this->logger->debug('end:' . $this->script_end_time);
    }

    private function dispatch()
    {
        if (!$this->dispatcher) {
            if (IS_CLI === false) {
                $controller = ucfirst(Input::string('c'));
                $method     = Input::string('a', null);
            } else {
                set_time_limit(0);
                ini_set('memory_limit', '1024M');
                $opt        = getopt('c:a:d::');
                $tmp        = array_map('ucfirst', explode('/', $opt['c']));
                $controller = implode('\\', $tmp);
                $method     = isset($opt['a']) ? $opt['a'] : null;
            }

            if (!$controller) {
                $controller = $this->default_controller;
            } else {
                $controller = $this->controller_namespace . '\\' . $controller . 'Controller';
            }

            $method || $method = $this->default_method;

            return [[$controller, $method], ['vars' => []]];
        } else {
            $routeInfo = $this->dispatcher->dispatch($_SERVER['REQUEST_METHOD'], rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
            $this->logger->debug($_SERVER['REQUEST_METHOD'] . ', ' . rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) . ', routeInfo:' . json_encode($routeInfo));
            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                case Dispatcher::METHOD_NOT_ALLOWED:
                    if ($this->default_controller && $this->default_method) {
                        $routeInfo[1] = [$this->default_controller, $this->default_method];
                        $routeInfo[2] = [];
                    } else {
                        throw new \LogicException("Controller action method doesn't exist.");
                    }
                    break;
                case Dispatcher::FOUND:
                    break;
            }
            return [$routeInfo[1], ['vars' => $routeInfo[2]]];
        }
        return $routeInfo;
    }

    private function execute(array $route)
    {
        list($cb, $options) = $route;

        try {
            $rc = new \ReflectionClass($cb[0]);
        } catch (\Exception $e) {
            throw new \LogicException($e->getMessage(), $e->getCode());
        }

        $constructArgs = null;
        if (isset($options['constructor_args'])) {
            $constructArgs = $options['constructor_args'];
        }

        if (is_string($cb[0])) {
            $cb[0] = $controller = $constructArgs ? $rc->newInstanceArgs($constructArgs) : $rc->newInstance();
        } else {
            $controller = $cb[0];
        }

        // check controller action method
        if ($controller && !method_exists($controller, $cb[1])) {
            throw new \LogicException("Controller action method '{$cb[1]}' doesn't exist.");
        }

        $rps = $rc->getMethod($cb[1])->getParameters();

        $vars = isset($options['vars'])
            ? $options['vars']
            : array();

        $arguments = array();
        foreach ($rps as $param) {
            $n = $param->getName();
            if (isset($vars[$n])) {
                $arguments[] = $vars[$n];
            } elseif (!$param->isOptional() && !$param->allowsNull()) {
                throw new \LogicException('parameter is not defined.');
            }
        }

        if ($this->muti_version) {
            $cb = $this->mutiVersion($cb[0], $cb[1], $rc->getMethods(), $this->muti_version);
        }

        if (!IS_CLI && defined('ONLY_CLI')) {
            throw new \LogicException('only cli model can visit!');
        }

        return call_user_func_array($cb, $arguments);
    }

   
    public static function setEnvironment($environments = array())
    {
        if (defined('ENVIRONMENT')) {
            return true;
        }

        $environment = 'production';
        foreach ($environments as $key => $hosts) {
            foreach ((array)$hosts as $host) {
                if ($host == gethostname()) {
                    $environment = $key;
                }
            }
        }

        defined('ENVIRONMENT') || define('ENVIRONMENT', $environment);
    }

    protected function getEnvironment()
    {
        return $this->environment;
    }

    public function setErrorReporting($error_reporting = true)
    {
        if ($error_reporting === false && !IS_CLI) {
            ini_set("display_errors", "off");
            error_reporting(0);
        } else {
            ini_set("display_errors", "on");
            error_reporting(E_ALL);
        }
    }
}
