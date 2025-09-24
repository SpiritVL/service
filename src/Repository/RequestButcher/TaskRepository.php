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
}
