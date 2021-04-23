<?php
namespace ScheduleParser;


use Dotenv\Dotenv;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use ParseCsv\Csv;
use PDO;
use Psr\Log\LoggerInterface;

class ScheduleParser {

    protected LoggerInterface $logger;
    protected Csv $csv;
    private PDO $db;

    protected bool $nonUtf8Encoding;
    protected string $encoding = "windows-1252";

    public function __construct($dir = __DIR__, bool $nonUtf8Encoding = false, string $encoding = ""){
        $dotenv = Dotenv::createImmutable($dir);
        $dotenv->load();

        $this->logger = $this->logger("ScheduleParser", $dir);
        $this->csv = new Csv();
        $this->db = new PDO('mysql:host='.getenv('MYSQL_HOST').';dbname='.getenv('MYSQL_DATABASE'), getenv('MYSQL_USER'), getenv('MYSQL_PASS'));

        $this->nonUtf8Encoding=$nonUtf8Encoding;
        $this->encoding=$encoding;
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
            $Vereinsnummer = $schedules['Vereinsnummer'];

            foreach ($schedules['schedules'] as $key => $schedule){
                $file = file_get_contents($schedule['url']);
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

    protected function execute(string $key, array $schedule, string $Vereinsnummer){
            $custom = array_key_exists('custom', $schedule) ? $schedule['custom'] : false;

            // RESET
            $games = $this->reset($key, $schedule['table'], $Vereinsnummer, $custom);

            // STORE
            $this->db->beginTransaction();

            foreach ($this->csv->data as $game) {
                $sql = $this->statement($schedule['table'], $custom, in_array($game['Spielnummer'], $games));
                $values = $this->values($game, $Vereinsnummer, $custom);

                $statement = $this->db->prepare($sql);
                $statement->execute($values);
            }

            $this->db->commit();
            $this->logger->info("{$key} - {$Vereinsnummer} :: SCHEDULE DONE");
    }

    private function reset(string $key, string $table, string $Vereinsnummer, bool $custom = false) :  array {
        // REMOVE old values
        if (!$custom){
            if (date("w")==5) {
                $previous_week = date("Y-m-d", strtotime("-1 week"));
                $sql = "DELETE FROM " . $table . " WHERE Spieldatum < :datum";
                $values = array(":datum" => $previous_week);
                $statement = $this->db->prepare($sql);
                $statement->execute($values);

                $this->logger->info("{$key} - {$Vereinsnummer} :: RESET DONE, CLEARED OLD VALUES {$previous_week}");
            }

            // GATHER all games
            $sql = "SELECT Spielnummer FROM ".$table." WHERE VereinsnummerA = :Vereinsnummer OR VereinsnummerB = :Vereinsnummer ORDER BY Spieldatum";
            $values = array(":Vereinsnummer" => $Vereinsnummer);
            $statement = $this->db->prepare($sql);
            $statement->execute($values);
            return $statement->fetchAll(PDO::FETCH_COLUMN);
        }
        else {
            $sql = "DELETE FROM " . $table;
            $this->db->query($sql);

            $this->logger->info("{$key} - {$Vereinsnummer} :: RESET DONE, CLEARED OLD CUSTOM VALUES");
            return array();
        }
    }

    private function statement(string $table, bool $custom, bool $update) : string {
        if (!$custom){
            if (!$update) {
                $sql = "INSERT INTO " . $table .
                    " (Team,SpielTyp,Spielstatus,Bezeichnung,Spielnummer,TagKurz,Spieldatum,Spielzeit,TeamnameA,VereinsnummerA,TeamLigaA,TeamnameB,VereinsnummerB,TeamLigaB,Spielort,Sportanlage,Ort,Wettspielfeld) VALUES " .
                    " (:Team,:SpielTyp,:Spielstatus,:Bezeichnung,:Spielnummer,:TagKurz,:Spieldatum,:Spielzeit,:TeamnameA,:VereinsnummerA,:TeamLigaA,:TeamnameB,:VereinsnummerB,:TeamLigaB,:Spielort,:Sportanlage,:Ort,:Wettspielfeld)";
            }
            else {
                $sql = "UPDATE " . $table . " SET ".
                    "Team = :Team, SpielTyp = :SpielTyp, Spielstatus = :Spielstatus, Bezeichnung = :Bezeichnung, TagKurz = :TagKurz, Spieldatum = :Spieldatum, Spielzeit = :Spielzeit, TeamnameA = :TeamnameA, VereinsnummerA = :VereinsnummerA, TeamLigaA = :TeamLigaA, TeamnameB = :TeamnameB, VereinsnummerB = :VereinsnummerB, TeamLigaB = :TeamLigaB, Spielort = :Spielort, Sportanlage = :Sportanlage, Ort = :Ort, Wettspielfeld = :Wettspielfeld ".
                    "WHERE Spielnummer = :Spielnummer";
            }
        }
        else {
            $sql = "INSERT INTO " . $table .
                " (Team,SpielTyp,Spielstatus,Bezeichnung,TagKurz,Spieldatum,Spielzeit,TeamnameA,VereinsnummerA,TeamLigaA,TeamnameB,VereinsnummerB,TeamLigaB,Spielort,Sportanlage,Ort,Wettspielfeld,bemerkungen) VALUES " .
                " (:Team,:SpielTyp,:Spielstatus,:Bezeichnung,:TagKurz,:Spieldatum,:Spielzeit,:TeamnameA,:VereinsnummerA,:TeamLigaA,:TeamnameB,:VereinsnummerB,:TeamLigaB,:Spielort,:Sportanlage,:Ort,:Wettspielfeld,:bemerkungen)";
        }
        return $sql;
    }

    private function values(array $game, string $vereinsnummer, bool $custom) : array {
        $Team = $this->Team($game, $vereinsnummer);
        $Spieldatum = $this->Spieldatum($game);

        if (!$custom){
            $values = array(
                ":Team" => $Team,
                ":SpielTyp" => $game['SpielTyp'],
                ":Spielstatus" => self::emptyAsNull($game['Spielstatus']),
                ":Bezeichnung" => self::emptyAsNull($game['Bezeichnung']),
                ":Spielnummer" => $game['Spielnummer'],
                ":TagKurz" => $game['TagKurz'],
                ":Spieldatum" => $Spieldatum,
                ":Spielzeit" => $game['Spielzeit'],
                ":TeamnameA" => $game['Teamname A'],
                ":TeamLigaA" => $game['TeamLiga A'],
                ":VereinsnummerA" => $game['Vereinsnummer A'],
                ":TeamnameB" => $game['Teamname B'],
                ":TeamLigaB" => $game['TeamLiga B'],
                ":VereinsnummerB" => $game['Vereinsnummer B'],
                ":Spielort" => $game['Spielort'],
                ":Sportanlage" => $game['Sportanlage'],
                ":Ort" => $game['Ort'],
                ":Wettspielfeld" => $game['Wettspielfeld']
            );
        }
        else {
            $values = array(
                ":Team" => $Team,
                ":SpielTyp" => $game['SpielTyp'],
                ":Spielstatus" => self::emptyAsNull($game['Spielstatus']),
                ":Bezeichnung" => self::emptyAsNull($game['Bezeichnung']),
                ":TagKurz" => $game['TagKurz'],
                ":Spieldatum" => $Spieldatum,
                ":Spielzeit" => $game['Spielzeit'],
                ":TeamnameA" => $game['Teamname A'],
                ":TeamLigaA" => $game['TeamLiga A'],
                ":VereinsnummerA" => $game['Vereinsnummer A'],
                ":TeamnameB" => $game['Teamname B'],
                ":TeamLigaB" => $game['TeamLiga B'],
                ":VereinsnummerB" => $game['Vereinsnummer B'],
                ":Spielort" => $game['Spielort'],
                ":Sportanlage" => $game['Sportanlage'],
                ":Ort" => $game['Ort'],
                ":Wettspielfeld" => $game['Wettspielfeld'],
                ":bemerkungen" => $game['bemerkungen']
            );
        }
        return $values;
    }

    private function Spieldatum(array $game) : string {
        return date("Y-m-d", strtotime($game["Spieldatum"]));
    }

    private function Team(array $game, string $vereinsnummer) : string {
        $Team = $game["Teamname A"] . $game["TeamLiga A"];
        if ($game["Vereinsnummer B"] == $vereinsnummer) {
            $Team = $game["Teamname B"] . $game["TeamLiga B"];
        }
        $Team = preg_replace('/[^A-Za-z0-9\-]/', '', $Team);

        return $Team;
    }

    private static function emptyAsNull(string $string) : ?string {
        if (!empty($string)){
            return $string;
        }
        return null;
    }
}