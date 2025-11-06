<?php

namespace App\Command\RequestButcher;

use App\Modules\Common\Widgets\ConsoleSessionIdChooser;
use App\Repository\RequestButcher\TaskRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rb:reset',
    description: 'Сброс всех ошибочных статусов',
)]
class ResetCommand extends Command
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly ConsoleSessionIdChooser $chooser,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->chooser->init($input, $output);

        $sessionId = $this->chooser->ask(true);

        $affectedRows = $this->taskRepository->resetStatus($sessionId);

        $io->success(
            sprintf('Сброс завершен. Затронуто [%d] записей', $affectedRows)
        );

        return Command::SUCCESS;
    }
}
