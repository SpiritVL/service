<?php

namespace App\Command\RequestButcher;

use App\Entity\RequestButcher\Factory\SessionFactory;
use App\Entity\RequestButcher\Factory\TaskFactory;
use App\Modules\Common\Csv\CsvReaderInterface;
use App\Modules\Common\Csv\Exception\WrongRowFormatException;
use App\Modules\RequestButcher\Dto\BasicDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(
    name: 'rb:import',
    description: 'Импорт данных в БД из csv-файла',
)]
class ImportCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly CsvReaderInterface $requestButchCsvReader,
        private readonly EntityManagerInterface $entityManager,
        private readonly SessionFactory $sessionFactory,
        private readonly TaskFactory $taskFactory
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion('Выберите csv-файл для импорта', $this->getFiles());
        $question->setErrorMessage('Файл %s не существует.');
        $pathToCsv = $this->projectDir . '/var/butcher/' . $helper->ask($input, $output, $question);

        try {
            // Пробуем получить строки
            $rows = $this->requestButchCsvReader->read($pathToCsv);
        } catch (WrongRowFormatException|FileNotFoundException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $total = iterator_count($rows);
        $io->title(sprintf('Импорт из файла `%s`', $pathToCsv));
        $question = new Question(sprintf('Импортировать [%d] записей? [y/n]', $total), 'y');

        if ($io->askQuestion($question) !== 'y') {
            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, iterator_count($rows));
        $progressBar->setFormat('very_verbose');
        $progressBar->start();

        // Создаем сессию
        $session = $this->sessionFactory->create(
            'File: ' . $pathToCsv,
            'Консольный запуск'
        );
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        /** @var BasicDto $dto */
        foreach ($rows as $index => $dto) {
            // Создаем задание
            $task = $this->taskFactory->create(
                $session,
                $dto->id,
                $dto->toArray()
            );
            $this->entityManager->persist($task);
            $progressBar->advance();

            if ($index % 1000 === 0) {
                $this->entityManager->flush();
            }
        }

        $progressBar->finish();
        $this->entityManager->flush();

        // Уведомление об успехе
        $io->newLine(2);
        $io->writeln([
            sprintf('Создана сессия № %d (%s)', $session->getId(), $session->getSlug()),
            sprintf('Добавлено записей: %d', $total)
        ]);
        $io->success('Импорт завершен');

        return Command::SUCCESS;
    }

    private function getFiles(): array
    {
        $results = (new Finder())
            ->files()
            ->in($this->projectDir . '/var/butcher/')
            ->filter(fn ($file) => $file->getExtension() === 'csv');

        $files = [];

        /** @var SplFileInfo $file */
        foreach ($results as $file) {
            $files[] = $file->getRelativePathname();
        }

        return $files;
    }
}
