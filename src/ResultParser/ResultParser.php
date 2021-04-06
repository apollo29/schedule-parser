<?php
namespace ResultParser;


use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;

class ResultParser {

    private $logger;
    private $db;

    public function __construct($dir = __DIR__){
        $dotenv = Dotenv::createImmutable($dir);
        $dotenv->load();

        $this->logger = $this->logger("ResultParser", $dir);
        $this->db = new PDO('mysql:host='.getenv('MYSQL_HOST').';dbname='.getenv('MYSQL_DATABASE'), getenv('MYSQL_USER'), getenv('MYSQL_PASS'));
    }

    private function logger($name, $dir) : LoggerInterface {
        $loggerSettings = array(
            "path" => $dir . '/logs/app.log',
            "level" => Logger::DEBUG);
        $logger = new Logger($name);

        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
        $logger->pushHandler($handler);

        return $logger;
    }

}