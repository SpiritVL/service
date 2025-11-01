<?php

namespace App\Repository\RequestButcher;

use App\Entity\RequestButcher\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
