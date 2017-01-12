<?php

namespace App\Repo\SQL;

use App\Exception\ContentNotFoundException;
use App\Model\Entity\ImageEntry;
use App\Model\Entity\ImageEntryPatch;
use App\Model\Entity\ImageEntryWithScore;
use App\Model\Filter\ImageEntryFilter;
use App\Repo\ImageEntryRepo;
use App\Order\ImageEntryOrder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Exception\ImageAlreadyEnteredException;
use App\Model\RandomOrderToken;
use App\Exception\RepoException;

function xrange($limit) {
    for ($i = 0; $i < $limit; $i ++) {
        yield $i;
    }
}


function getRandomOrder(RandomOrderToken $randomOrderToken, $maxItems)
{
    mt_srand($randomOrderToken->getSeed());
    $entries = [];
    $numberItems = $randomOrderToken->getNumberEntries();

    foreach (xrange($numberItems) as $i) {
        $entries[] = $i;
    }

    $randomEntries = $entries;
    foreach ($entries as $currentItem) {
        $itemToSwap = mt_rand(0, $numberItems - 1);
        $tmp = $randomEntries[$currentItem];
        $randomEntries[$currentItem] = $randomEntries[$itemToSwap];
        $randomEntries[$itemToSwap] = $tmp;
    }

    return $randomEntries;
}

class ImageEntrySqlRepo implements ImageEntryRepo
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

    private function selectRandomOrdering(
        $offset,
        $limit,
        ImageEntryOrder $imageEntryOrder,
        ImageEntryFilter $imageEntryFilter
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
    ie.third_party_opt_in
  from image_entry ie
    %s
order by
   ie.date_submitted
SQL;
        $whereCondition = '';
        $whereString = 'where ';

        if ($imageEntryFilter->allowedCompetitionIds !== null) {
            $whereCondition .= $whereString.sprintf(
                "ie.competition_id in (%s)",
                implode(", ", $imageEntryFilter->allowedCompetitionIds)
            );
            $whereString = ' and ';
        }

        if (count($imageEntryFilter->allowedStatuses) !== 0) {
            $addQuotes = function ($input) {
                return "'".$input."'";
            };
            $whereCondition .= $whereString.sprintf(
                "ie.status in (%s)",
                implode(", ", array_map($addQuotes, $imageEntryFilter->allowedStatuses))
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
        $randomOrderArray = getRandomOrder($imageEntryOrder->getRandomToken(), count($rows));

        $randomOrderedRows = [];
        for ($i=$offset; $i<$offset + $limit && $i < count($rows); $i++) {
            $index = $randomOrderArray[$i];
            if (array_key_exists($index, $rows) === true) {
                $row = $rows[$index];
                if (count($imageEntryFilter->allowedStatuses) !== 0) {
                    if (in_array($row['status'], $imageEntryFilter->allowedStatuses) == false) {
                        continue;
                    }
                }

                $randomOrderedRows[] = $row;
            }
        }

        return $randomOrderedRows;
    }

    /**
     * @param $imageEntryId int The ID of the imageEntry.
     * @return ImageEntry
     */
    public function getImageEntry($imageEntryId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $this->addSelectToQuery($queryBuilder);
        $queryBuilder->where('ie.image_id = '.$queryBuilder->createNamedParameter($imageEntryId));
        $statement = $queryBuilder->execute();
        $data = $statement->fetchAll();

        foreach ($data as $dataSet) {
            $imageEntry = ImageEntry::fromDbData($dataSet);
            return $imageEntry;
        }

        throw new ContentNotFoundException("Image entry with id $imageEntryId not found");
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
            'ie.third_party_opt_in'
        );

        $queryBuilder->from('image_entry', 'ie');
    }

    private function addFilterInfo(QueryBuilder $queryBuilder, ImageEntryFilter $imageEntryFilter)
    {
        $whered = false;
        if ($imageEntryFilter->allowedCompetitionIds !== null) {
            $orCompetitionExpr = $queryBuilder->expr()->orx();
            foreach ($imageEntryFilter->allowedCompetitionIds as $allowedCompetitionId) {
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

        if (count($imageEntryFilter->allowedStatuses) !== 0) {
            $orExpr = $queryBuilder->expr()->orx();
            foreach ($imageEntryFilter->allowedStatuses as $allowedStatus) {
                $orExpr->add($queryBuilder->expr()->eq(
                    'ie.status',
                    $queryBuilder->createNamedParameter($allowedStatus)
                ));
            }
            if ($whered == true) {
                $queryBuilder->andWhere($orExpr);
            }
            else {
                $queryBuilder->where($orExpr);
            }
        }
    }

    private function addOrderInfo(
        QueryBuilder $queryBuilder,
        ImageEntryOrder $imageEntryOrder
    ) {
        $orderColumns = [
            ImageEntryOrder::FIRST_NAME => 'ie.first_name',
            ImageEntryOrder::LAST_NAME => 'ie.last_name',
            ImageEntryOrder::STATUS => 'ie.status',
            ImageEntryOrder::DATE_SUBMITTED => 'ie.date_submitted',
        ];

        $sortOrderArray = $imageEntryOrder->getOrder();

        if (array_key_exists(ImageEntryOrder::RANDOM, $sortOrderArray) === true) {
            throw new \Exception("Random ordering is not compatible with other");
        }

        foreach ($sortOrderArray as $type => $order) {
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
        ImageEntryOrder $imageEntryOrder,
        ImageEntryFilter $imageEntryFilter
    ){
        $queryBuilder = $this->connection->createQueryBuilder();
        $this->addSelectToQuery($queryBuilder);
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        $this->addFilterInfo($queryBuilder, $imageEntryFilter);
        $this->addOrderInfo($queryBuilder, $imageEntryOrder);

        $statement = $queryBuilder->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $offset
     * @param $limit
     * @param ImageEntryOrder $imageEntryOrder
     * @param ImageEntryFilter $imageEntryFilter
     * @return ImageEntry[]
     */
    public function getImageEntries(
        $offset,
        $limit,
        ImageEntryOrder $imageEntryOrder,
        ImageEntryFilter $imageEntryFilter
    ) {

        if ($imageEntryOrder->isSortingByRandom()) {
            $rows = $this->selectRandomOrdering(
                $offset,
                $limit,
                $imageEntryOrder,
                $imageEntryFilter
            );
        }
        else {
            $rows = $this->selectNormalOrdering(
                $offset,
                $limit,
                $imageEntryOrder,
                $imageEntryFilter
            );
        }

        $imageEntries = [];
        foreach ($rows as $row) {
            $imageEntries[] = ImageEntry::fromDbData($row);
        }

        return $imageEntries;
    }

    public function getTotalImageEntries(ImageEntryFilter $imageEntryFilter)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('count(*) as image_entry_count');
        $queryBuilder->from('image_entry', 'ie');
        $this->addFilterInfo($queryBuilder, $imageEntryFilter);
        $statement = $queryBuilder->execute();
        $row = $statement->fetch();

        return $row['image_entry_count'];
    }

    public function getImageInfoTotal()
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('count(*) as image_entry_count');
        $queryBuilder->from('image_entry', 'ie');
        $statement = $queryBuilder->execute();
        $row = $statement->fetch();

        return $row['image_entry_count'];
    }

    public function create(ImageEntry $imageEntry)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->insert('image_entry');
        $data = [];
        $dbData = $imageEntry->toDbData();

        $data["image_id"] = $queryBuilder->createNamedParameter($dbData['image_id'], \PDO::PARAM_STR);
        $data["competition_id"] = $queryBuilder->createNamedParameter($dbData["competition_id"], \PDO::PARAM_INT);
        $data["first_name"] = $queryBuilder->createNamedParameter($dbData['first_name'], \PDO::PARAM_STR);
        $data["last_name"] = $queryBuilder->createNamedParameter($dbData['last_name'], \PDO::PARAM_STR);
        $data["email"] = $queryBuilder->createNamedParameter($dbData['email'], \PDO::PARAM_STR);
        $data["description"] = $queryBuilder->createNamedParameter($dbData['description'], \PDO::PARAM_STR);
        $data["status"] = $queryBuilder->createNamedParameter($dbData['status'], \PDO::PARAM_STR);
        $data["date_submitted"] = $queryBuilder->createNamedParameter($dbData['date_submitted'], \PDO::PARAM_STR);
        $data["ip_address"] = $queryBuilder->createNamedParameter($dbData['ip_address'], \PDO::PARAM_STR);
        $data["image_extension"] = $queryBuilder->createNamedParameter($dbData['image_extension'], \PDO::PARAM_STR);
        $data["third_party_opt_in"] = $queryBuilder->createNamedParameter($dbData['third_party_opt_in'], \PDO::PARAM_INT);

        $queryBuilder->values($data);

        try {
            $result = $queryBuilder->execute();
        }
        catch (UniqueConstraintViolationException $ucve) {
            throw new ImageAlreadyEnteredException("Image with id of ".$imageEntry->getImageId()." has already been used. Please re-upload the image.");
        }
        catch(\Doctrine\DBAL\DBALException $e) {
            $pdoException = $e->getPrevious();
            /** @var $pdoException \PDOException */
            if ($pdoException !== null) {
                if (intval($pdoException->getCode()) === 23000) {
                    throw new ImageAlreadyEnteredException("Image with id of ".$imageEntry->getImageId()." has already been used. Please re-upload the image.");
                }
            }

            throw $e;
        }

        if (!$result) {
            $message = sprintf(
                "Failed to insert ImageEntry. ErrorCode: %d ErrorInfo: %s",
                $this->connection->errorCode(),
                implode(", ", $this->connection->errorInfo())
            );

            throw new \Exception($message);
        }

        return $imageEntry;
    }

    public function update($imageId, ImageEntryPatch $imageEntryPatch)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->update('image_entry', 'ie');

        if ($imageEntryPatch->status !== null) {
            $queryBuilder->set('ie.status', $queryBuilder->createNamedParameter($imageEntryPatch->status));
        }
        if ($imageEntryPatch->firstName !== null) {
            $queryBuilder->set('ie.first_name', $queryBuilder->createNamedParameter($imageEntryPatch->firstName));
        }
        if ($imageEntryPatch->lastName !== null) {
            $queryBuilder->set('ie.last_name', $queryBuilder->createNamedParameter($imageEntryPatch->lastName));
        }
        if ($imageEntryPatch->description !== null) {
            $queryBuilder->set('ie.description', $queryBuilder->createNamedParameter($imageEntryPatch->description));
        }

        $queryBuilder->where('ie.image_id = '.$queryBuilder->createNamedParameter($imageId));
        $queryBuilder->execute();
    }
}
