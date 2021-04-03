<?php

require '../vendor/autoload.php';

$schedules = array(
    "default" => array(
        "url" => "http://www.football.ch/portaldata/1/nisrd/WebService/verein/calendar.asmx/Verein?v=1343&away=1&sp=de&format=csv",
        "table" => "spielplan",
        "isCustom" => false
    ),
    "custom" => array(
        "url" => "custom.csv",
        "table" => "spielplan_custom",
        "isCustom" => true
    )
);

$parser = new \ScheduleParser\ScheduleParser(__DIR__,new \Psr\Log\Test\TestLogger());

$parser->parse($schedules);