<?php

namespace ScheduleParser\Domain\Schedule\Repository;

use DomainException;
use PDO;
use ScheduleParser\Domain\Schedule\Data\ScheduleData;
use ScheduleParser\Factory\QueryFactory;
use ScheduleParser\Support\Hydrator;

/**
 * Repository.
 */
class ScheduleRepository
{
    private QueryFactory $queryFactory;

    private Hydrator $hydrator;

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     * @param Hydrator $hydrator The hydrator
     */
    public function __construct(QueryFactory $queryFactory, Hydrator $hydrator)
    {
        $this->queryFactory = $queryFactory;
        $this->hydrator = $hydrator;
    }

    /**
     * Insert schedule row.
     *
     * @param ScheduleData $data The schedule data
     *
     * @return int The new Id
     */
    public function insert(ScheduleData $data): int
    {
        return (int)$this->queryFactory->newInsert('schedule', $data->toRow())
            ->execute()
            ->lastInsertId();
    }

    /**
     * Get schedule by Spielnummer.
     *
     * @param int $spielnummer The spielnummer
     *
     * @throws DomainException
     *
     * @return ScheduleData The event
     */
    public function read(int $spielnummer): ScheduleData
    {
        $query = $this->queryFactory->newSelect('schedule');
        $query->select(
            [
                'TeamA',
                'TeamB',
                'SpielTyp',
                'Spielstatus',
                'Bezeichnung',
                'Spielnummer',
                'TagKurz',
                'Spieldatum',
                'Spielzeit',
                'TeamnameA',
                'TeamLigaA',
                'VereinsnummerA',
                'TeamnameB',
                'TeamLigaB',
                'VereinsnummerB',
                'Spielort',
                'Sportanlage',
                'Ort',
                'Wettspielfeld',
            ]
        );
        $query->andWhere(['Spielnummer' => $spielnummer]);

        $row = $query->execute()->fetch('assoc');

        if (!$row) {
            throw new DomainException(sprintf('Schedule not found: %s', $spielnummer));
        }

        return new ScheduleData($row);
    }

    /**
     * Update schedule row.
     *
     * @param ScheduleData $data The schedule data
     *
     * @return void
     */
    public function update(ScheduleData $data): void
    {
        $row = $data->toRow();

        $this->queryFactory->newUpdate('schedule', $row)
            ->andWhere(['Spielnummer' => $data->Spielnummer])
            ->execute();
    }

    /**
     * Delete schedule row.
     *
     * @param int $spielnummer The spielnummer
     *
     * @return void
     */
    public function delete(int $spielnummer): void
    {
        $this->queryFactory->newDelete('schedule')
            ->andWhere(['Spielnummer' => $spielnummer])
            ->execute();
    }

    /**
     * Delete old schedule row.
     *
     * @return void
     */
    public function reset(): void
    {
        $previous_week = date("Y-m-d", strtotime("-1 week"));
        $this->queryFactory->newDelete('schedule')
            ->where(['Spieldatum <' => $previous_week])
            ->execute();
    }

    /**
     * Find all schedules of a club.
     *
     * @return ScheduleData[] A list of events
     */
    public function findAll(string $vereinsnummer): array
    {
        $query = $this->queryFactory->newSelect('schedule');
        $query->select('Spielnummer')
            ->where(['OR' => ['VereinsnummerA' => $vereinsnummer, 'VereinsnummerB' => $vereinsnummer]])
            ->order('Spieldatum');

        return $query->execute()->fetchAll(PDO::FETCH_COLUMN);
    }
}