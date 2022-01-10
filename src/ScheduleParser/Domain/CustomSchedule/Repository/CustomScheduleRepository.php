<?php

namespace ScheduleParser\Domain\CustomSchedule\Repository;

use DomainException;
use ScheduleParser\Domain\CustomSchedule\Data\CustomScheduleData;
use ScheduleParser\Factory\QueryFactory;
use ScheduleParser\Support\Hydrator;

/**
 * Repository.
 */
class CustomScheduleRepository
{
    private QueryFactory $queryFactory;

    private Hydrator $hydrator;
    
    private string $table;

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     * @param Hydrator $hydrator The hydrator
     */
    public function __construct(QueryFactory $queryFactory, Hydrator $hydrator, string $table = 'custom_schedule')
    {
        $this->queryFactory = $queryFactory;
        $this->hydrator = $hydrator;
        $this->table = $table;
    }

    /**
     * Set the custom table name.
     *
     * @param string $table The custom table name
     */
    public function table(string $table): void
    {
        $this->table = $table;
    }

    /**
     * Insert custom_schedule row.
     *
     * @param CustomScheduleData $data The custom_schedule data
     *
     * @return int The new Id
     */
    public function insert(CustomScheduleData $data): int
    {
        return (int)$this->queryFactory->newInsert($this->table, $data->toRow())
            ->execute()
            ->lastInsertId();
    }

    /**
     * Get custom_schedule by Spielnummer.
     *
     * @param int $spielnummer The spielnummer
     *
     * @throws DomainException
     *
     * @return CustomScheduleData The event
     */
    public function read(int $spielnummer): CustomScheduleData
    {
        $query = $this->queryFactory->newSelect($this->table);
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
                'bemerkungen'
            ]
        );
        $query->andWhere(['Spielnummer' => $spielnummer]);

        $row = $query->execute()->fetch('assoc');

        if (!$row) {
            throw new DomainException(sprintf('Custom Schedule not found: %s', $spielnummer));
        }

        return new CustomScheduleData($row);
    }

    /**
     * Update custom_schedule row.
     *
     * @param CustomScheduleData $data The custom_schedule data
     *
     * @return void
     */
    public function update(CustomScheduleData $data): void
    {
        $row = $data->toRow();

        $this->queryFactory->newUpdate($this->table, $row)
            ->andWhere(['Spielnummer' => $data->Spielnummer])
            ->execute();
    }

    /**
     * Delete custom_schedule row.
     *
     * @param int $spielnummer The spielnummer
     *
     * @return void
     */
    public function delete(int $spielnummer): void
    {
        $this->queryFactory->newDelete($this->table)
            ->andWhere(['Spielnummer' => $spielnummer])
            ->execute();
    }

    /**
     * Delete custom_schedule.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->queryFactory->newDelete($this->table)
            ->execute();
    }

    /**
     * Find all custom_schedules of a club.
     *
     * @return CustomScheduleData[] A list of events
     */
    public function findAll(string $vereinsnummer): array
    {
        $query = $this->queryFactory->newSelect($this->table);
        $query->select('Spielnummer')
            ->where(['OR' => ['VereinsnummerA' => $vereinsnummer, 'VereinsnummerB <' => $vereinsnummer]])
            ->order('Spieldatum');

        $rows = $query->execute()->fetchAll('assoc') ?: [];

        // Convert to list of objects
        return $this->hydrator->hydrate($rows, CustomScheduleData::class);
    }
}