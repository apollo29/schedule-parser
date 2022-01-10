<?php
namespace ScheduleParser;


use Cake\Database\Connection;
use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use ParseCsv\Csv;
use PDO;
use Psr\Log\LoggerInterface;
use ScheduleParser\Domain\CustomSchedule\Data\CustomScheduleData;
use ScheduleParser\Domain\CustomSchedule\Repository\CustomScheduleRepository;
use ScheduleParser\Domain\Schedule\Data\ScheduleData;
use ScheduleParser\Domain\Schedule\Repository\ScheduleRepository;
use ScheduleParser\Factory\QueryFactory;
use ScheduleParser\Support\Hydrator;

class ScheduleParser {

    protected ScheduleRepository $repository;
    protected CustomScheduleRepository $customRepository;
    protected LoggerInterface $logger;
    protected Csv $csv;

    protected bool $nonUtf8Encoding;
    protected string $encoding;

    public function __construct(string $dir, bool $nonUtf8Encoding = false, string $encoding = ""){
        $dotenv = Dotenv::createImmutable($dir);
        $dotenv->load();

        $this->repository = $this->createRepository();
        $this->customRepository = $this->createCustomRepository();
        $this->logger = $this->logger("ScheduleParser", $dir);
        $this->csv = new Csv();

        $this->nonUtf8Encoding=$nonUtf8Encoding;
        $this->encoding=$encoding;
    }

    private function queryFactory(): QueryFactory {
        // Database settings
        $settings['db'] = [
            'driver' => \Cake\Database\Driver\Mysql::class,
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            // Enable identifier quoting
            'quoteIdentifiers' => true,
            // Set to null to use MySQL servers timezone
            'timezone' => null,
            // Disable meta data cache
            'cacheMetadata' => false,
            // Disable query logging
            'log' => false,
            // PDO options
            'flags' => [
                // Turn off persistent connections
                PDO::ATTR_PERSISTENT => false,
                // Enable exceptions
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Emulate prepared statements
                PDO::ATTR_EMULATE_PREPARES => true,
                // Set default fetch mode to array
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Convert numeric values to strings when fetching.
                // Since PHP 8.1 integers and floats in result sets will be returned using native PHP types.
                // This option restores the previous behavior.
                PDO::ATTR_STRINGIFY_FETCHES => true,
            ],
        ];
        $settings['db']['host'] = getenv('MYSQL_HOST');
        $settings['db']['database'] = getenv('MYSQL_DATABASE');
        $settings['db']['username'] = getenv('MYSQL_USER');
        $settings['db']['password'] = getenv('MYSQL_PASS');

        $connection = new Connection($settings['db']);
        return new QueryFactory($connection);
    }

    private function createRepository(): ScheduleRepository {
        return new ScheduleRepository($this->queryFactory(), new Hydrator());
    }

    private function createCustomRepository(): CustomScheduleRepository {
        return new CustomScheduleRepository($this->queryFactory(), new Hydrator());
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

    public function parse(array $schedules) {
        if (is_array($schedules) && is_array($schedules['schedules'])){
            $Vereinsnummer = $schedules['settings']['Vereinsnummer'];

            foreach ($schedules['schedules'] as $key => $schedule){
                $file = $this->contents($schedule, $schedules['settings']);
                if (!empty($file)) {
                    if ($this->nonUtf8Encoding) {
                        $this->csv->encoding($this->encoding, 'UTF-8');
                    }
                    $this->csv->auto($file);

                    $this->execute($key, $schedule, $Vereinsnummer);
                } else {
                    $message = "{$key} - {$Vereinsnummer} :: FILE SIZE ZERO";
                    $this->logger->warning($message);
                    mail($schedules['notification'], $message, 'FILESIZE ZERO @' . date('d.m.Y'));
                }
            }
        }
    }

    public function contents(array $schedule, array $settings): string
    {
        return file_get_contents($schedule['url']);
    }

    protected function execute(string $key, array $schedule, string $Vereinsnummer){
        $custom = array_key_exists('custom', $schedule) ? $schedule['custom'] : false;

        // SETUP
        if ($custom && array_key_exists('table', $schedule)){
            $this->customRepository->table($schedule['table']);
        }

        // RESET
        $games = $this->reset($key, $Vereinsnummer, $custom);

        // STORE
        foreach ($this->csv->data as $game) {
            // PREPARE
            $game['TeamA'] = $this->TeamA($game);
            $game['TeamB'] = $this->TeamB($game);
            $game['Spieldatum'] = $this->Spieldatum($game);

            $schedule = new ScheduleData($game);
            if ($custom){
                $schedule = new CustomScheduleData($game);
            }

            if (in_array($game['Spielnummer'], $games)){
                // UPDATE
                if (!$custom) {
                    $this->repository->update($schedule);
                }
                else {
                    $this->customRepository->update($schedule);
                }
            }
            else {
                // INSERT
                if (!$custom) {
                    $this->repository->insert($schedule);
                }
                else {
                    $this->customRepository->insert($schedule);
                }
            }
        }

        $this->logger->info("{$key} - {$Vereinsnummer} :: SCHEDULE DONE");
    }

    private function reset(string $key, string $Vereinsnummer, bool $custom = false) :  array {
        // REMOVE old values
        if (!$custom){
            if (date("w")==5) {
                $this->repository->reset();
                $this->logger->info("{$key} - {$Vereinsnummer} :: RESET DONE, CLEARED OLD VALUES");
            }

            // GATHER all games
            return $this->repository->findAll($Vereinsnummer);
        }
        else {
            $this->customRepository->reset();
            $this->logger->info("{$key} - {$Vereinsnummer} :: RESET DONE, CLEARED OLD CUSTOM VALUES");
            return array();
        }
    }

    private function Spieldatum(array $game) : string {
        return date("Y-m-d", strtotime($game["Spieldatum"]));
    }

    private function TeamA(array $game) : string {
        return $this->Team($game, "A");
    }

    private function TeamB(array $game) : string {
        return $this->Team($game, "B");
    }

    private function Team(array $game, string $type) : string {
        $Team = $game["Teamname A"] . $game["TeamLiga A"];
        if ($type=="B") {
            $Team = $game["Teamname B"] . $game["TeamLiga B"];
        }
        $Team = preg_replace('/[^A-Za-z0-9\-]/', '', $Team);

        return $Team;
    }
}