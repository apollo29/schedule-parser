<?php

namespace ScheduleParser\Domain\CustomSchedule\Data;

use ScheduleParser\Domain\Schedule\Data\ScheduleData;
use Selective\ArrayReader\ArrayReader;

/**
 * Data Model.
 */
class CustomScheduleData extends ScheduleData
{
    public ?string $bemerkungen = null;

    /**
     * The constructor.
     *
     * @param array $data The data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $reader = new ArrayReader($data);

        $this->bemerkungen = $reader->findString('bemerkungen');
    }
}