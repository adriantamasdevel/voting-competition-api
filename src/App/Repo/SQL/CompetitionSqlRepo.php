<?php

namespace App\Repo\SQL;

use App\Exception\ContentNotFoundException;
use App\Model\ImageEntryCountInfo;
use App\Model\Entity\Competition;
use App\Model\CompetitionStats;
use App\Model\Entity\ImageEntry;
use App\Order\CompetitionOrder;
use App\Repo\Mock\ImageEntryMockRepo;
use App\Model\Filter\CompetitionFilter;
use App\Model\Patch\CompetitionPatch;
use App\Repo\CompetitionRepo;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Query\QueryBuilder;


class CompetitionSqlRepo implements CompetitionRepo
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param int $competitionId The Id of the competition.
     * @return Competition
     */
    public function getCompetition($competitionId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $this->addSelectToQuery($queryBuilder);
        $queryBuilder->where('c.competition_id = '.$queryBuilder->createNamedParameter($competitionId));
        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll();

        if (count($rows) == 0) {
            throw new ContentNotFoundException("Competition not found");
        }

        return Competition::fromDbData($rows[0]);
    }


    /**
     * @param int $competitionId The Id of the competition.
     * @return Competition
     */
    public function getCompetitionByImageId($imageId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $this->addSelectToQuery($queryBuilder);
        $queryBuilder->leftJoin('c', 'image_entry', 'ie', 'c.competition_id = ie.competition_id');

        $queryBuilder->where('ie.image_id = '.$queryBuilder->createNamedParameter($imageId));
        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll();

        if (count($rows) == 0) {
            throw new ContentNotFoundException("Competition not found");
        }

        return Competition::fromDbData($rows[0]);
    }



    private function addSelectToQuery(QueryBuilder $queryBuilder)
    {
        $queryBuilder->select(
            'c.competition_id',
            'c.title',
            'c.description',
            'c.date_entries_close',
            'c.date_votes_close',
            'c.initial_status_of_images',
            'c.status'
        );
        $queryBuilder->from('competition', 'c');
    }


    private function addFilterInfo(
        QueryBuilder $queryBuilder,
        CompetitionFilter $competitionFilter
    ) {

        // @TODO - implement
//        if ($competitionFilter->allowedCompetitionIds !== null) {
//            $inExpr = $queryBuilder->expr()->in('ie.competition_id', $imageEntryWithScoreFilter->allowedCompetitionIds);
//            $queryBuilder->where($inExpr);
//        }
//
        if (count($competitionFilter->statuses) !== 0) {
            $orExpr = $queryBuilder->expr()->orx();
            foreach ($competitionFilter->statuses as $status) {
                $orExpr->add($queryBuilder->expr()->eq('c.status', '"'.$status.'"'));
            }

            $queryBuilder->where($orExpr);
        }
    }

    /**
     * @param $offset
     * @param $limit
     * @param $competitionOrder CompetitionOrder
     * @param $competitionFilter CompetitionFilter
     * @return Competition[]
     */
    public function getCompetitions(
        $offset,
        $limit,
        CompetitionOrder $competitionOrder,
        CompetitionFilter $competitionFilter
    ) {
        $queryBuilder = $this->connection->createQueryBuilder();
        $this->addSelectToQuery($queryBuilder);
        $this->addOrderInfo($queryBuilder, $competitionOrder);
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);
        $this->addFilterInfo($queryBuilder, $competitionFilter);

        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $competitions = [];
        foreach ($rows as $row) {
            $competitions[] = Competition::fromDbData($row);
        }

        return $competitions;
    }

    private function addOrderInfo(
        QueryBuilder $queryBuilder,
        CompetitionOrder $imageEntryOrder
    ) {
        $orderColumns = [
            CompetitionOrder::ID                 => 'c.competition_id',
            CompetitionOrder::DATE_ENTRIES_CLOSE => 'c.date_entries_close',
            CompetitionOrder::DATE_VOTES_CLOSE   => 'c.date_votes_close'
        ];
        $sortOrderArray = $imageEntryOrder->getOrder();
        foreach ($sortOrderArray as $type => $order) {
            if (array_key_exists($type, $orderColumns) == false) {
                throw new \Exception("Unknown sorting of type $type");
            }
            $orderColumn = $orderColumns[$type];
            $queryBuilder->addOrderBy($orderColumn, $order);
        }
    }

    /**
     * @param $competitionFilter CompetitionFilter
     * @return int
     */
    public function getCompetitionTotal(CompetitionFilter $competitionFilter)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('count(*) as comp_count');
        $queryBuilder->from('competition', 'c');
        $statement = $queryBuilder->execute();
        $row = $statement->fetch();

        return $row['comp_count'];
    }

    public function create(Competition $competition)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->insert('competition');
        $data = [];
        $dbData = $competition->toDbData();
        $data['title'] = $queryBuilder->createNamedParameter($dbData['title'], Type::STRING);
        $data['description'] = $queryBuilder->createNamedParameter($dbData['description'], Type::STRING);
        $data['date_entries_close'] = $queryBuilder->createNamedParameter($dbData['date_entries_close'], Type::DATETIME);
        $data['date_votes_close'] = $queryBuilder->createNamedParameter($dbData['date_votes_close'], Type::DATETIME);
        $data['initial_status_of_images'] = $queryBuilder->createNamedParameter($dbData['initial_status_of_images'], Type::STRING);
        $data['status'] = $queryBuilder->createNamedParameter($dbData['status'], Type::STRING);

        $queryBuilder->values($data);
        $result = $queryBuilder->execute();

        if (!$result) {
            throw new \Exception("Failed to insert competition.");
        }

        $insertId = $this->connection->lastInsertId();

        $competition->setCompetitionId($insertId);

        return $competition;
    }

    public function update($competitionId, CompetitionPatch $competitionPatch)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->update('competition', 'c');

        if (($newTitle = $competitionPatch->getTitle()) !== null) {
            $queryBuilder->set('c.title', $queryBuilder->createNamedParameter($newTitle));
        }

        if (($newDescription = $competitionPatch->getDescription()) !== null) {
            $queryBuilder->set('c.description', $queryBuilder->createNamedParameter($newDescription));
        }

        if (($newStatus = $competitionPatch->getStatus()) !== null) {
            $queryBuilder->set('c.status', $queryBuilder->createNamedParameter($newStatus));
        }

        if (($newDateEntriesClose = $competitionPatch->getDateEntriesClose()) !== null) {
            $queryBuilder->set('c.date_entries_close', $queryBuilder->createNamedParameter($newDateEntriesClose, Type::DATETIME));
        }

        if (($newDateVotesClose = $competitionPatch->getDateVotesClose()) !== null) {
            $queryBuilder->set('c.date_votes_close', $queryBuilder->createNamedParameter($newDateVotesClose, Type::DATETIME));
        }

        if (($newInitialStatusOfImages = $competitionPatch->getInitialStatusOfImages()) !== null) {
            $queryBuilder->set('c.initial_status_of_images', $queryBuilder->createNamedParameter($newInitialStatusOfImages));
        }

        $queryBuilder->where('c.competition_id = '.$queryBuilder->createNamedParameter($competitionId));
        $queryBuilder->execute();
        // @TODO - check number of rows affected == 1
    }


    private function calculateVoteCount($competitionId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select(
            "ie.competition_id",
            "sum(1) AS 'voteCount'"
        );

        $queryBuilder->from('image_entry', 'ie');
        $queryBuilder->innerJoin('ie', 'vote', 'v', 'ie.image_id = v.image_id');
        $queryBuilder->where('ie.competition_id = '.$queryBuilder->createNamedParameter($competitionId));
        $queryBuilder->groupBy('ie.competition_id');

        $statement = $queryBuilder->execute();
        $row = $statement->fetch();
        $voteCount = $row['voteCount'];

        if ($voteCount === null) {
            return 0;
        }

        return $voteCount;
    }

    private function calculateUniqueVoteCount($competitionId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select(
            "COUNT(DISTINCT v.ip_address) AS 'totalVoters'"
        );

        $queryBuilder->from('image_entry', 'ie');
        $queryBuilder->leftJoin('ie', 'vote', 'v', 'ie.image_id = v.image_id');
        $queryBuilder->where('ie.competition_id = '.$queryBuilder->createNamedParameter($competitionId));

        $statement = $queryBuilder->execute();
        $row = $statement->fetch();

        if ($row === false) {
            return 0;
        }

        $voteCount = $row['totalVoters'];


        return $voteCount;
    }

    private function calculateImageEntryCountInfo($competitionId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select(
            "c.competition_id",
            "sum(CASE WHEN ie.status IS NOT NULL THEN 1 ELSE 0 END) as 'imageEntryCount'",
            "SUM(CASE WHEN ie.status = '" . ImageEntry::STATUS_UNMODERATED . "' THEN 1 ELSE 0 END) AS 'imageEntryUnmoderatedCount'",
            "SUM(CASE WHEN ie.status = '" . ImageEntry::STATUS_VERIFIED . "' THEN 1 ELSE 0 END) AS 'imageEntryVerifiedCount'",
            "SUM(CASE WHEN ie.status = '" . ImageEntry::STATUS_HIDDEN . "' THEN 1 ELSE 0 END) AS 'imageEntryHiddenCount'",
            "SUM(CASE WHEN ie.status = '" . ImageEntry::STATUS_BLOCKED . "' THEN 1 ELSE 0 END) AS 'imageEntryBlockedCount'"
        );

        $queryBuilder->from('competition', 'c');
        $queryBuilder->leftJoin('c', 'image_entry', 'ie', 'c.competition_id = ie.competition_id');
        $queryBuilder->groupBy('c.competition_id');
        $queryBuilder->where('c.competition_id = '.$queryBuilder->createNamedParameter($competitionId));

        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll();
        $row = $rows[0];

        return new ImageEntryCountInfo(
            $row['imageEntryCount'],
            $row['imageEntryUnmoderatedCount'],
            $row['imageEntryVerifiedCount'],
            $row['imageEntryHiddenCount'],
            $row['imageEntryBlockedCount']
        );
    }


    /**
     * @param $competitionId
     */
    public function getCompetitionStats($competitionId)
    {
        $imageEntryCountInfo = $this->calculateImageEntryCountInfo($competitionId);
        $voteCount = $this->calculateVoteCount($competitionId);
        $uniqueUserVoterCount = $this->calculateUniqueVoteCount($competitionId);

        return new CompetitionStats(
            $imageEntryCountInfo->imageEntryCount,
            $imageEntryCountInfo->imageEntryUnmoderatedCount,
            $imageEntryCountInfo->imageEntryVerifiedCount,
            $imageEntryCountInfo->imageEntryHiddenCount,
            $imageEntryCountInfo->imageEntryBlockedCount,
            $voteCount,
            $uniqueUserVoterCount
        );
    }
}
