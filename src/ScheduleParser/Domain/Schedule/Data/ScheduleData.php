<?php

namespace ScheduleParser\Domain\Schedule\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Data Model.
 */
class ScheduleData
{
    public ?string $TeamA = null;
    public ?string $TeamB = null;
    public ?string $SpielTyp = null;
    public ?string $Spielstatus = null;
    public ?string $Bezeichnung = null;
    public ?int $Spielnummer = null;
    public ?string $TagKurz = null;
    public ?string $Spieldatum = null;
    public ?string $Spielzeit = null;
    public ?string $TeamnameA = null;
    public ?string $TeamLigaA = null;
    public ?int $VereinsnummerA = null;
    public ?string $TeamnameB = null;
    public ?string $TeamLigaB = null;
    public ?int $VereinsnummerB = null;
    public ?string $Spielort = null;
    public ?string $Sportanlage = null;
    public ?string $Ort = null;
    public ?string $Wettspielfeld = null;

    /**
     * The constructor.
     *
     * @param array $data The data
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);

        $this->TeamA = $reader->findString('TeamA');
        $this->TeamB = $reader->findString('TeamB');
        $this->SpielTyp = $reader->findString('SpielTyp');
        $this->Spielstatus = $reader->findString('Spielstatus');
        $this->Bezeichnung = $reader->findString('Bezeichnung');
        $this->Spielnummer = $reader->findInt('Spielnummer');
        $this->TagKurz = $reader->findString('TagKurz');
        $this->Spieldatum = $reader->findString('Spieldatum');
        $this->Spielzeit = $reader->findString('Spielzeit');
        $this->TeamnameA = $reader->findString('Teamname A');
        $this->TeamLigaA = $reader->findString('TeamLiga A');
        $this->VereinsnummerA = $reader->findInt('Vereinsnummer A');
        $this->TeamnameB = $reader->findString('Teamname B');
        $this->TeamLigaB = $reader->findString('TeamLiga B');
        $this->VereinsnummerB = $reader->findInt('Vereinsnummer B');
        $this->Spielort = $reader->findString('Spielort');
        $this->Sportanlage = $reader->findString('Sportanlage');
        $this->Ort = $reader->findString('Ort');
        $this->Wettspielfeld = $reader->findString('Wettspielfeld');
    }

    public function toRow(): array
    {
        return (array) $this;
    }
}