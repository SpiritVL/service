<?php

namespace App\Modules\Common\Widgets;

use App\Entity\RequestButcher\Session;
use App\Repository\RequestButcher\SessionRepository;
use Exception;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ConsoleSessionIdChooser
{
    public function __construct(
        private readonly SessionRepository $sessionRepository,
        private ?InputInterface $input = null,
        private ?OutputInterface $output = null
    ) {
    }

    public function init(InputInterface $input, OutputInterface $output): self
    {
        $this->input = $input;
        $this->output = $output;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function ask(): int
    {
        if (! $this->input || ! $this->output) {
            throw new Exception('Виджет не инициализирован');
        }

        $sessions = $this->sessionRepository->findAll();
        $choices = [];
        /** @var Session $session */
        foreach ($sessions as $session) {
            $choices[$session->getId()] = sprintf('%s (#%d)', $session->getTitle(), $session->getId());
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion('Выберите сессию', $choices);
        $question->setErrorMessage('Сессии %s не существует.');

        $selectedValue = $helper->ask($this->input, $this->output, $question);

        return array_search($selectedValue, $choices);
    }
}
