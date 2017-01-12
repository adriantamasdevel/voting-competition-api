<?php

namespace App\Repo\SQL;

use App\Model\Entity\ImageEntryWithScore;
use App\Order\ImageEntryWithScoreOrder;
use App\Repo\ImageEntryWithScoreRepo;
use App\Model\Filter\ImageEntryWithScoreFilter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use App\Exception\ContentNotFoundException;
use App\Exception\RepoException;

class ImageEntryWithScoreSqlRepo implements ImageEntryWithScoreRepo
{

    /** @var Connection */
    private $connection;

    /** @var \PDO */
    private $pdo;

    public function __construct(Connection $connection, \PDO $pdo)
    {
        $this->connection = $connection;
        $this->pdo = $pdo;
    }
    /**
     * @param int $imageEntryId The ID of the team.
     * @return ImageEntryWithScore
     */
    public function getImageEntryWithScore($imageEntryId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $this->addSelectToQuery($queryBuilder);
        $queryBuilder->where('ie.image_id = '.$queryBuilder->createNamedParameter($imageEntryId, \PDO::PARAM_STR));
        $statement = $queryBuilder->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (count($rows) == 0) {
            throw new ContentNotFoundException("ImageEntry not found");
        }

        return ImageEntryWithScore::fromDbData($rows[0]);
    }


    private function addSelectToQuery(QueryBuilder $queryBuilder)
    {
        $queryBuilder->select(
            'ie.image_id',
            'ie.first_name',
            'ie.last_name',
            'ie.email',
            'ie.description',
            'ie.status',
            'ie.date_submitted',
            'ie.ip_address',
            'ie.image_extension',
            'ie.competition_id',
            'ie.third_party_opt_in',
            'count(v.vote_id) as score'
        );
        $queryBuilder->from('image_entry', 'ie');
        $queryBuilder->leftJoin('ie', 'vote', 'v', 'ie.image_id = v.image_id');
        $queryBuilder->groupBy('ie.image_id');
    }

    private function addFilterInfo(
        QueryBuilder $queryBuilder,
        ImageEntryWithScoreFilter $imageEntryWithScoreFilter
    ) {
        $whered = false;

        if ($imageEntryWithScoreFilter->allowedCompetitionIds !== null) {
            $orCompetitionExpr = $queryBuilder->expr()->orx();
            foreach ($imageEntryWithScoreFilter->allowedCompetitionIds as $allowedCompetitionId) {
                $orCompetitionExpr->add(
                    $queryBuilder->expr()->eq(
                        'ie.competition_id',
                        $queryBuilder->createNamedParameter($allowedCompetitionId, \PDO::PARAM_INT)
                    )
                );
            }

            $whered = true;
            $queryBuilder->where($orCompetitionExpr);
        }

        if (count($imageEntryWithScoreFilter->allowedStatuses) !== 0) {
            $orExpr = $queryBuilder->expr()->orx();
            foreach ($imageEntryWithScoreFilter->allowedStatuses as $allowedStatus) {
                $orExpr->add(
                    $queryBuilder->expr()->eq(
                        'ie.status',
                        $queryBuilder->createNamedParameter($allowedStatus)
                    )
                );
            }

            if ($whered == true) {
                $queryBuilder->andWhere($orExpr);
            }
            else {
                $queryBuilder->where($orExpr);
            }
        }
    }

    public function getTotalImageEntries(ImageEntryWithScoreFilter $imageEntryWithScoreFilter)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('count(*) as image_entry_count');
        $queryBuilder->from('image_entry', 'ie');
        $this->addFilterInfo($queryBuilder, $imageEntryWithScoreFilter);
        $statement = $queryBuilder->execute();
        $row = $statement->fetch();

        return $row['image_entry_count'];
    }

    private function addOrderInfo(QueryBuilder $queryBuilder, ImageEntryWithScoreOrder $imageEntryOrder)
    {
        $orderColumns = [
            ImageEntryWithScoreOrder::FIRST_NAME => 'ie.first_name',
            ImageEntryWithScoreOrder::LAST_NAME => 'ie.last_name',
            ImageEntryWithScoreOrder::STATUS => 'ie.status',
            ImageEntryWithScoreOrder::DATE_SUBMITTED => 'ie.date_submitted',
            ImageEntryWithScoreOrder::SCORE => 'score',
        ];

        foreach ($imageEntryOrder->getOrder() as $type => $order) {
            if ($type == ImageEntryWithScoreOrder::RANDOM) {
                $queryBuilder->leftJoin('ie', 'image_entry_random', 'ier', 'ie.image_id = ier.image_id');
                $queryBuilder->addOrderBy("ier.random_id", $order);
                continue;
            }

            if (array_key_exists($type, $orderColumns) == false) {
                throw new \Exception("Unknown sorting of type $type");
            }
            $orderColumn = $orderColumns[$type];
            $queryBuilder->addOrderBy($orderColumn, $order);
        }
    }

    private function selectNormalOrdering(
        $offset,
        $limit,
        ImageEntryWithScoreOrder $imageEntryWithScoreOrder,
        ImageEntryWithScoreFilter $imageEntryWithScoreFilter
    ){
        $queryBuilder = $this->connection->createQueryBuilder();
        $this->addSelectToQuery($queryBuilder);
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        $this->addFilterInfo($queryBuilder, $imageEntryWithScoreFilter);
        $this->addOrderInfo($queryBuilder, $imageEntryWithScoreOrder);

        $statement = $queryBuilder->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function selectRandomOrdering(
        $offset,
        $limit,
        ImageEntryWithScoreOrder $imageEntryWithScoreOrder,
        ImageEntryWithScoreFilter $imageEntryWithScoreFilter
    ) {
        $sql = <<< SQL
select
    ie.image_id,
    ie.first_name,
    ie.last_name,
    ie.email,
    ie.description,
    ie.status,
    ie.date_submitted,
    ie.ip_address,
    ie.image_extension,
    ie.competition_id,
    ie.third_party_opt_in,
    count(v.vote_id) as score
from image_entry ie
left join vote as v
on ie.image_id = v.image_id
    %s
group by ie.image_id
order by
   ie.date_submitted
SQL;

        $whereCondition = '';
        $whereString = 'where ';

        if ($imageEntryWithScoreFilter->allowedCompetitionIds !== null) {
            $whereCondition .= $whereString.sprintf(
                    "ie.competition_id in (%s)",
                    implode(", ", $imageEntryWithScoreFilter->allowedCompetitionIds)
                );
            $whereString = ' and ';
        }

        if (count($imageEntryWithScoreFilter->allowedStatuses) !== 0) {
            $addQuotes = function ($input) {
                return "'".$input."'";
            };
            $whereCondition .= $whereString.sprintf(
                    "ie.status in (%s)",
                    implode(", ", array_map($addQuotes, $imageEntryWithScoreFilter->allowedStatuses))
                );
            $whereString = ' and ';
        }

        $sql = sprintf($sql, $whereCondition);
        $statement = $this->pdo->prepare($sql);

        $result = $statement->execute();
        if ($result !== true) {
            throw new RepoException("SQL to fetch random order failed without exception?");
        }

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $randomOrderArray = getRandomOrder($imageEntryWithScoreOrder->getRandomToken(), count($rows));

        $randomOrderedRows = [];
        for ($i=$offset; $i<$offset + $limit && $i < count($rows); $i++) {
            $index = $randomOrderArray[$i];
            if (array_key_exists($index, $rows) === true) {
                $row = $rows[$index];
                if (count($imageEntryWithScoreFilter->allowedStatuses) !== 0) {
                    if (in_array($row['status'], $imageEntryWithScoreFilter->allowedStatuses) == false) {
                        continue;
                    }
                }

                $randomOrderedRows[] = $row;
            }
        }

        return $randomOrderedRows;
    }

    /**
     * @param $offset
     * @param $limit
     * @param ImageEntryWithScoreOrder $imageEntryWithScoreOrder
     * @param ImageEntryWithScoreFilter $imageEntryWithScoreFilter
     * @return ImageEntryWithScore[]
     */
    public function getImageEntriesWithScore(
        $offset,
        $limit,
        ImageEntryWithScoreOrder $imageEntryWithScoreOrder,
        ImageEntryWithScoreFilter $imageEntryWithScoreFilter
    ) {
        $queryBuilder = $this->connection->createQueryBuilder();
        $this->addSelectToQuery($queryBuilder);
        $this->addFilterInfo($queryBuilder, $imageEntryWithScoreFilter);
        $this->addOrderInfo($queryBuilder, $imageEntryWithScoreOrder);

        if ($imageEntryWithScoreOrder->isSortingByRandom()) {
            $rows = $this->selectRandomOrdering(
                $offset,
                $limit,
                $imageEntryWithScoreOrder,
                $imageEntryWithScoreFilter
            );
        }
        else {
            $rows = $this->selectNormalOrdering(
                $offset,
                $limit,
                $imageEntryWithScoreOrder,
                $imageEntryWithScoreFilter
            );
        }

        $imageEntriesWithScore = [];
        foreach ($rows as $row) {
            $imageEntriesWithScore[] = ImageEntryWithScore::fromDbData($row);
        }

        return $imageEntriesWithScore;
    }
}
