<?php

namespace App\Repository\RequestButcher;

use App\Entity\RequestButcher\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @return Task[]
     */
    public function findNewBySessionId(int $sessionId): array
    {
        return self::findBy([
            'session' => [
                'id' => $sessionId
            ],
            'status' => 'new'
        ]);
    }

    /**
     * @return Task[]
     */
    public function findNewChunkBySessionId(int $sessionId, int $size): array
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(Task::class, 't');

        $sql = '
            SELECT DISTINCT ON (t.identifier) ' . $rsm->generateSelectClause() . '
            FROM request_butcher_task t
            WHERE t.session_id = :sessionId
            AND t.status = :status
            LIMIT :limit
        ';

        return $this->getEntityManager()
                    ->createNativeQuery($sql, $rsm)
                    ->setParameter('sessionId', $sessionId)
                    ->setParameter('status', 'new')
                    ->setParameter('limit', $size)
                    ->getResult();
    }

    public function resetStatus(?string $sessionId = null): int
    {
        $qb = $this->createQueryBuilder('t');
        $qb->update()
           ->set('t.status', ':newStatus')
           ->setParameter('newStatus', 'new')
           ->where($qb->expr()->notIn('t.status', ':excludedStatuses'))
           ->setParameter('excludedStatuses', ['new', 'done']);

        if ($sessionId) {
            $qb->andWhere('t.session = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        return $qb->getQuery()->execute();
    }
}
