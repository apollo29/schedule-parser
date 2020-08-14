<?php

require __DIR__ . '/../../vendor/autoload.php';

use ParseCsv\Csv;

$config = array(
  "Vereinsnummer" => "10311",
  "VereinsId" => "1343"
);

$header = array(
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
$url = "http://www.football.ch/portaldata/1/nisrd/WebService/verein/calendar.asmx/Verein?v=".$config['VereinsId']."&away=1&sp=de&format=csv";
//$schedule = file_get_contents($url);
$schedule = file_get_contents('data.csv');

if (!empty($schedule)){
    //$mysqli->set_charset("utf8");
    //$mysqli->query('DELETE FROM spielplan');

    $csv = new ParseCsv\Csv();
    $csv->encoding('windows-1252', 'UTF-8');
    $csv->auto($schedule);

    // MySQL
    $sql = "INSERT INTO spielplan (Team,SpielTyp,Spielstatus,Bezeichnung,Spielnummer,TagKurz,Spieldatum,Spielzeit,TeamnameA,VereinsnummerA,TeamLigaA,TeamnameB,VereinsnummerB,TeamLigaB,Spielort,Sportanlage,Ort) VALUES ";
    foreach($csv->data as $game){

        $Spieldatum = date("Y-m-d",strtotime($game["Spieldatum"]));

        $Team = $game["Teamname A"] . $game["TeamLiga A"];
        if ($game["Vereinsnummer B"]==$config["Vereinsnummer"]){
            $Team = $game["Teamname B"] . $game["TeamLiga B"];
        }
        $Team = preg_replace('/[^A-Za-z0-9\-]/', '', $Team);

        $sql_spielplan = " ('".$Team."','".$game["SpielTyp"]."','".$game["Spielstatus"]."','".$game["Bezeichnung"]."','".$game["Spielnummer"]."','".$game["TagKurz"]."','".$Spieldatum."','".$game["Spielzeit"]."','".$game["Teamname A"]."','".$game["Vereinsnummer A"]."','".$game["TeamLiga A"]."','".$game["Teamname B"]."','".$game["Vereinsnummer B"]."','".$game["TeamLiga B"]."','".$game["Spielort"]."','".$game["Sportanlage"]."','".$game["Ort"]."')";
        //$mysqli->query($sql . $sql_spielplan);
    }
    echo "SCHEDULE DONE";
}
else {
    echo "FILE SIZE ZERO";
    mail('me@mail.com','[Spielplan] FILESIZE ZERO','FILESIZE ZERO @'.date('d.m.Y'));
}

