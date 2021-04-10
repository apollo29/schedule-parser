<?php

use ScheduleParser\ScheduleParser;

require '../vendor/autoload.php';

$example = array(
    "Vereinsnummer" => "10311",
    "VereinsId" => "1343",
    "notification" => "thomas.dascoli@gmail.com",
    "schedules" => array(
        "default" => array(
            "url" => "http://www.football.ch/portaldata/1/nisrd/WebService/verein/calendar.asmx/Verein?v=1343&away=1&sp=de&format=csv",
            "table" => "spielplan",
            "custom" => false
        ),
        "custom" => array(
            "url" => "custom.csv",
            "table" => "spielplan_custom",
            "custom" => true
        )
    )
);

$schedules = array(
    "Vereinsnummer" => "10330",
    "VereinsId" => "1362",
    "notification" => "thomas.dascoli@gmail.com",
    "schedules" => array(
        "default" => array(
            "url" => "http://www.football.ch/portaldata/1/nisrd/WebService/verein/calendar.asmx/Verein?v=1362&away=1&sp=de&format=csv",
            "table" => "spielplan",
            "custom" => false
        )
    )
);


$parser = new ScheduleParser(__DIR__);

$parser->parse($schedules);