<?php

namespace App\Entity\RequestButcher\Factory;
use App\Entity\RequestButcher\Session;
use App\Entity\RequestButcher\Task;

class TaskFactory
{
    public function create(Session $session, int $identifier, array $params): Task
    {
        return (new Task())
            ->setSession($session)
            ->setIdentifier($identifier)
            ->setStatus('new')
            ->setParams($params);
    }
}
