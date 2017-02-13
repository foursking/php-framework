<?php

namespace DongPHP\System\Logger;
use Iframe\Controller\AbstractController;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Logger;

class RedisLogger extends AbstractLogger
{
    public function __construct()
    {
        $logger = new Logger(__CLASS__);
        $logger->pushHandler($this->getDebugHandler(Logger::DEBUG));
        $logger->pushHandler($this->getSocketHandler(Logger::ERROR));
        //$logger->pushHandler($this->getSocketHandler(MonologLogger::ERROR));
        $this->logger = $logger;
        return $this->logger;
    }


}
