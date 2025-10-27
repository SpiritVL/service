<?php

namespace App\Command\RequestButcher;

use App\Entity\RequestButcher\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rb:reset',
    description: 'Сброс всех ошибочных статусов',
)]
class ResetCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('session', 's', InputOption::VALUE_OPTIONAL, 'ID сессии');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sessionId = $input->getOption('session');

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->update(Task::class, 't')
            ->set('t.status', ':new')
            ->setParameter('new', 'new')
            ->where($queryBuilder->expr()->notIn('t.status', ['new', 'done']));

        if ($sessionId) {
            $queryBuilder
                ->andWhere('t.session = :sessionId')
                ->setParameter('sessionId', $sessionId);
        }

        $affectedRows = $queryBuilder->getQuery()->execute();

        $io->success(
            sprintf('Сброс завершен. Затронуто [%d] записей', $affectedRows)
        );

        return Command::SUCCESS;
    }
}
