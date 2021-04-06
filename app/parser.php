<?php

require '../vendor/autoload.php';

$schedules = array(
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

$parser = new \ScheduleParser\ScheduleParser(__DIR__);

$parser->parse($schedules);