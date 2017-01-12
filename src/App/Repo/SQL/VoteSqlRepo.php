<?php

namespace App\Repo\SQL;

use App\Repo\VoteRepo;
use App\Exception\AlreadyVotedException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class VoteSqlRepo implements VoteRepo
{
    /** @var Connection */
    private $connection;

    const ALREADY_VOTED_EXCEPTION = "Your IP address has already voted on an image.";

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $imageId
     * @param $ipAddress
     * @return mixed
     * @throws \App\Exception\AlreadyVotedException
     */
    public function addVote($imageId, $ipAddress)
    {
        // @TODO - do we need a check on the $imageId or is the foreign key a good enough check.
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->insert('vote');
        $data = [];

        $data['image_id'] = $queryBuilder->createNamedParameter($imageId, Type::STRING);
        $data['ip_address'] = $queryBuilder->createNamedParameter($ipAddress, Type::STRING);

        $queryBuilder->values($data);

        try {
            $result = $queryBuilder->execute();
        }
        catch (UniqueConstraintViolationException $ucve) {
            throw new AlreadyVotedException(self::ALREADY_VOTED_EXCEPTION);
        }
        catch(\Doctrine\DBAL\DBALException $e) {
            $pdoException = $e->getPrevious();
            /** @var $pdoException \PDOException */
            if ($pdoException !== null) {
                if (intval($pdoException->getCode()) === 23000) {
                    throw new AlreadyVotedException(self::ALREADY_VOTED_EXCEPTION);
                }
            }

            throw $e;
        }
    }
}
