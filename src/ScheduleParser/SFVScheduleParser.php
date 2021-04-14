<?php
namespace ScheduleParser;


use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use ParseCsv\Csv;
use PDO;
use Psr\Log\LoggerInterface;

class SFVScheduleParser extends ScheduleParser {

    private static $url = "http://www.football.ch/portaldata/1/nisrd/WebService/verein/calendar.asmx/Verein?v={VEREINSID}&away=1&sp=de&format=csv";

    public function parse(array $schedules) {
        if (is_array($schedules) && is_array($schedules['schedules'])){
            $Vereinsnummer = $schedules['Vereinsnummer'];
            $VereinsId = $schedules['VereinsId'];

            foreach ($schedules['schedules'] as $key => $schedule){
                $file = $this->contents($schedule, $VereinsId);
                if (!empty($file)) {
                    $this->csv->encoding('windows-1252', 'UTF-8');
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

    private function contents(array $schedule, string $VereinsId) : string {
        if (!$schedule['custom']) {
            $url = str_replace("{VEREINSID}", $VereinsId, self::$url);
        }
        else {
            $url = $schedule['url'];
        }
        return file_get_contents($url);
    }
}