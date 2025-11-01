<?php

namespace App\Command\RequestButcher;

use App\Entity\RequestButcher\Session;
use App\Repository\RequestButcher\SessionRepository;
use App\Repository\RequestButcher\TaskRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rb:reset',
    description: 'Сброс всех ошибочных статусов',
)]
class ResetCommand extends Command
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly SessionRepository $sessionRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sessions = $this->sessionRepository->findAll();
        $choices[0] = null;
        /** @var Session $session */
        foreach ($sessions as $session) {
            $choices[$session->getId()] = sprintf('%s (#%d)', $session->getTitle(), $session->getId());
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion('Выберите сессию для сброса (0 для всех)', $choices);
        $question->setErrorMessage('Сессии %s не существует.');
        $selectedValue = $helper->ask($input, $output, $question);
        $sessionId = array_search($selectedValue, $choices);

        $affectedRows = $this->taskRepository->resetStatus($sessionId);

        $io->success(
            sprintf('Сброс завершен. Затронуто [%d] записей', $affectedRows)
        );

        return Command::SUCCESS;
    }
}
