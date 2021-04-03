<?php
namespace ScheduleParser;


use Dotenv\Dotenv;
use ParseCsv\Csv;
use PDO;
use Psr\Log\LoggerInterface;

class ScheduleParser {

    private $config;
    private $logger;
    private $csv;
    private $db;

    public function __construct($env = __DIR__, LoggerInterface $logger = null){
        $dotenv = Dotenv::createImmutable($env);
        $dotenv->load();

        $this->config = array(
            "Vereinsnummer" => getenv('PARSER_VEREINSNUMMER'),
            "VereinsId" => getenv('PARSER_VEREINSID'),
            "notification" => getenv('PARSER_NOTIFICATION'),
            "db" => array(
                "host" => getenv('MYSQL_HOST'),
                "user" => getenv('MYSQL_USER'),
                "secret" => getenv('MYSQL_PASS'),
                "database" => getenv('MYSQL_DATABASE'),
                "tables" => array(
                    "default" => getenv('PARSER_TABLE_DEFAULT'),
                    "custom" => getenv('PARSER_TABLE_CUSTOM')
                )
            )
        );
        $this->logger = $logger;
        $this->csv = new Csv();
        $this->db = new PDO('mysql:host='.$this->config['db']['host'].';dbname='.$this->config['db']['database'], $this->config['db']['user'], $this->config['db']['secret']);
    }

    public function parse(array $schedules) {
        if (is_array($schedules)){
            foreach ($schedules as $key => $schedule){
                $file = file_get_contents($schedule['url']);
                if (!empty($file)) {
                    $table = $schedule['table'];
                    if (empty($table)){
                        if (!$schedule['isCustom']) {
                            $table = $this->config['db']['tables']['default'];
                        }
                        else {
                            $table = $this->config['db']['tables']['custom'];
                        }
                    }

                    // RESET
                    $sql = "SELECT Spielnummer FROM ".$table." WHERE VereinsnummerA = :Vereinsnummer OR VereinsnummerB = :Vereinsnummer ORDER BY Spieldatum";
                    $values = array(":Vereinsnummer" => $this->config['Vereinsnummer']);
                    $statement = $this->db->prepare($sql);
                    $statement->execute($values);
                    $games = $statement->fetchAll(PDO::FETCH_COLUMN);

                    $this->csv->encoding('windows-1252', 'UTF-8');
                    $this->csv->auto($file);

                    // STORE
                    $this->db->beginTransaction();

                    foreach ($this->csv->data as $game) {
                        if (!$schedule['isCustom']) {
                            $this->parseDefault($table, $game, in_array($game['Spielnummer'], $games));
                        }
                        else {
                            $this->parseCustom($table, $game, in_array($game['Spielnummer'], $games));
                        }
                    }
                    $this->db->commit();
                    $this->logger->info("SCHEDULE DONE");
                } else {
                    $this->logger->warning("FILE SIZE ZERO");
                    mail($this->config['notification'], '[' . $key . '] FILESIZE ZERO', 'FILESIZE ZERO @' . date('d.m.Y'));
                }
            }
        }
    }

    private function parseDefault($table, $game, $update = false){
        if (!$update) {
            $sql = "INSERT INTO " . $table .
                " (Team,SpielTyp,Spielstatus,Bezeichnung,Spielnummer,TagKurz,Spieldatum,Spielzeit,TeamnameA,VereinsnummerA,TeamLigaA,TeamnameB,VereinsnummerB,TeamLigaB,Spielort,Sportanlage,Ort,Wettspielfeld) VALUES " .
                " (:Team,:SpielTyp,:Spielstatus,:Bezeichnung,:Spielnummer,:TagKurz,:Spieldatum,:Spielzeit,:TeamnameA,:VereinsnummerA,:TeamLigaA,:TeamnameB,:VereinsnummerB,:TeamLigaB,:Spielort,:Sportanlage,:Ort,:Wettspielfeld)";
        }
        else {
            $sql = "UPDATE " . $table . "SET ".
                "Team = :Team, SpielTyp = :SpielTyp, Spielstatus = :Spielstatus, Bezeichnung = :Bezeichnung, TagKurz = :TagKurz, Spieldatum = :Spieldatum, Spielzeit = :Spielzeit, TeamnameA = :TeamnameA, VereinsnummerA = :VereinsnummerA, TeamLigaA = :TeamLigaA, TeamnameB = :TeamnameB, VereinsnummerB = :VereinsnummerB, TeamLigaB = :TeamLigaB, Spielort = :Spielort, Sportanlage = :Sportanlage, Ort = :Ort, Wettspielfeld = :Wettspielfeld ".
                "WHERE Spielnummer = :Spielnummer";
        }        
        
        $Spieldatum = date("Y-m-d", strtotime($game["Spieldatum"]));

        $Team = $game["Teamname A"] . $game["TeamLiga A"];
        if ($game["Vereinsnummer B"] == $this->config["Vereinsnummer"]) {
            $Team = $game["Teamname B"] . $game["TeamLiga B"];
        }
        $Team = preg_replace('/[^A-Za-z0-9\-]/', '', $Team);

        $values = array(
            ":Team" => $Team,
            ":SpielTyp" => $game['SpielTyp'],
            ":Spielstatus" => $game['Spielstatus'],
            ":Bezeichnung" => $game['Bezeichnung'],
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

        $statement = $this->db->prepare($sql);
        $statement->execute($values);
        // add logging when error
    }

    private function parseCustom($table, $game, $update = false){
        if (!$update) {
            $sql = "INSERT INTO " . $table .
                " (Team,SpielTyp,Spielstatus,Bezeichnung,TagKurz,Spieldatum,Spielzeit,TeamnameA,VereinsnummerA,TeamLigaA,TeamnameB,VereinsnummerB,TeamLigaB,Spielort,Sportanlage,Ort,Wettspielfeld,bemerkungen) VALUES " .
                " (:Team,:SpielTyp,:Spielstatus,:Bezeichnung,:TagKurz,:Spieldatum,:Spielzeit,:TeamnameA,:VereinsnummerA,:TeamLigaA,:TeamnameB,:VereinsnummerB,:TeamLigaB,:Spielort,:Sportanlage,:Ort,:Wettspielfeld,:bemerkungen)";
        }
        else {
            $sql = "UPDATE " . $table . "SET ".
                "Team = :Team, SpielTyp = :SpielTyp, Spielstatus = :Spielstatus, Bezeichnung = :Bezeichnung, TagKurz = :TagKurz, Spieldatum = :Spieldatum, Spielzeit = :Spielzeit, TeamnameA = :TeamnameA, VereinsnummerA = :VereinsnummerA, TeamLigaA = :TeamLigaA, TeamnameB = :TeamnameB, VereinsnummerB = :VereinsnummerB, TeamLigaB = :TeamLigaB, Spielort = :Spielort, Sportanlage = :Sportanlage, Ort = :Ort, Wettspielfeld = :Wettspielfeld, bemerkungen = :bemerkungen ".
                "WHERE Spielnummer = :Spielnummer";
        }

        $Spieldatum = date("Y-m-d", strtotime($game["Spieldatum"]));

        $Team = $game["Teamname A"] . $game["TeamLiga A"];
        if ($game["Vereinsnummer B"] == $this->config["Vereinsnummer"]) {
            $Team = $game["Teamname B"] . $game["TeamLiga B"];
        }
        $Team = preg_replace('/[^A-Za-z0-9\-]/', '', $Team);

        $values = array(
            ":Team" => $Team,
            ":SpielTyp" => $game['SpielTyp'],
            ":Spielstatus" => $game['Spielstatus'],
            ":Bezeichnung" => $game['Bezeichnung'],
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

        $statement = $this->db->prepare($sql);
        $statement->execute($values);
        // add logging when error
    }
}