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

    private static $header = array(
        "SpielTyp",
        "Spielstatus",
        "Bezeichnung",
        "Spielnummer",
        "TagKurz",
        "Spieldatum",
        "Spielzeit",
        "Teamname A",
        "TeamLiga A",
        "Vereinsnummer A",
        "Teamname B",
        "TeamLiga B",
        "Vereinsnummer B",
        "Spielort",
        "Sportanlage",
        "Ort",
        "Wettspielfeld"
    );

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
                    "custom" => getenv('spielplan_custom')
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
                if (!$schedule['isCustom']) {
                    $file = file_get_contents($schedule['url']);
                    if (!empty($file)) {
                        // RESET
                        $sql = "DELETE FROM " . $schedule['table'];
                        $this->db->query($sql);

                        $this->csv->encoding('windows-1252', 'UTF-8');
                        $this->csv->auto($file);

                        // FILE
                        $sql = "INSERT INTO " . $schedule['table'] .
                            " (Team,SpielTyp,Spielstatus,Bezeichnung,Spielnummer,TagKurz,Spieldatum,Spielzeit,TeamnameA,VereinsnummerA,TeamLigaA,TeamnameB,VereinsnummerB,TeamLigaB,Spielort,Sportanlage,Ort,Wettspielfeld) VALUES " .
                            " (:Team,:SpielTyp,:Spielstatus,:Bezeichnung,:Spielnummer,:TagKurz,:Spieldatum,:Spielzeit,:TeamnameA,:VereinsnummerA,:TeamLigaA,:TeamnameB,:VereinsnummerB,:TeamLigaB,:Spielort,:Sportanlage,:Ort,:Wettspielfeld)";

                        $this->db->beginTransaction();

                        foreach ($this->csv->data as $game) {
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
                        $this->db->commit();
                        $this->logger->info("SCHEDULE DONE");
                    } else {
                        $this->logger->warning("FILE SIZE ZERO");
                        mail($this->config['notification'], '[' . $key . '] FILESIZE ZERO', 'FILESIZE ZERO @' . date('d.m.Y'));
                    }
                }
            }
        }
    }
}