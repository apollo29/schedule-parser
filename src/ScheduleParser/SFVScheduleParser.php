<?php
namespace ScheduleParser;

class SFVScheduleParser extends ScheduleParser {

    private static string $url = "http://www.football.ch/portaldata/1/nisrd/WebService/verein/calendar.asmx/Verein?v={VEREINSID}&away=1&sp=de&format=csv";

    public function __construct(string $dir){
        parent::__construct($dir,true,"windows-1252");
    }

    public function contents(array $schedule, array $settings) : string {
        $VereinsId = $settings['VereinsId'];
        if (!$schedule['custom']) {
            $url = str_replace("{VEREINSID}", $VereinsId, self::$url);
        }
        else {
            $url = $schedule['url'];
        }
        return file_get_contents($url);
    }
}