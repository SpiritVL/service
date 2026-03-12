<?php

namespace App\Command\Parse;

use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'parse:find-variables',
    description: 'Ищет все уникальные variables в файле по шаблону {{ ... }}',
)]
class FindVariablesCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion('Выберите файл для анализа', $this->getFiles());
        $question->setErrorMessage('Файл %s не существует.');
        $filePath = $this->projectDir . '/var/parse/' . $helper->ask($input, $output, $question);

        $content = $this->filesystem->readFile($filePath);
        preg_match_all('/{{(.*?)}}/s', $content, $matches);
        $variables = array_map('trim', $matches[1]);
        $variables = array_unique(array_filter($variables));
        sort($variables);

        $io->newLine();
        $io->writeln($variables);
        $io->success(
            sprintf('Найдено переменных: %d', count($variables))
        );

        return Command::SUCCESS;
    }

    private function getFiles(): array
    {
        $results = (new Finder())
            ->files()
            ->in($this->projectDir . '/var/parse/');

        $files = [];

        /** @var SplFileInfo $file */
        foreach ($results as $file) {
            $files[] = $file->getRelativePathname();
        }

        return $files;
    }
}
