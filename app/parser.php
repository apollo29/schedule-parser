<?php

use ScheduleParser\SFVScheduleParser;

require '../vendor/autoload.php';

$schedules = array(
    "settings" => array(
        "Vereinsnummer" => "10311",
        "VereinsId" => "1343",
        "notification" => "notify@me.com",
    ),
    "schedules" => array(
        "default" => array(
            "url" => "http://www.football.ch/portaldata/1/nisrd/WebService/verein/calendar.asmx/Verein?v=1343&away=1&sp=de&format=csv",
            "custom" => false
        ),
        "custom" => array(
            "url" => "custom.csv",
            "custom" => true
        )
    )
);

$parser = new SFVScheduleParser(__DIR__);

$parser->parse($schedules);