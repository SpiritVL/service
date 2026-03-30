<?php

namespace App\Command\Parse;

use App\Modules\Common\Files\FilesFinder;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'parse:array-filter',
    description: 'Очищает массив от дублей и сортирует.',
)]
class ArrayFilterCommand extends Command
{
    public const MODULE_NAME = 'parse';

    public function __construct(
        private readonly FilesFinder $finder,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $files = $this->finder->find(self::MODULE_NAME);

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion('Файл для очистки', $files);
        $question->setErrorMessage('Файл %s не существует.');
        $file = $helper->ask($input, $output, $question);
        $filePath = $this->finder->getFilePath($file, self::MODULE_NAME);

        $data = explode(PHP_EOL, $this->filesystem->readFile($filePath));
        $result = $this->normalize($data);

        $this->filesystem->dumpFile(
            sprintf('%s/result-%s', $this->finder->getProjectPath(self::MODULE_NAME), $file),
            implode(PHP_EOL, $result)
        );

        $io->newLine();
        $io->success('Файл успешно сохранен');

        return Command::SUCCESS;
    }

    private function normalize(array $array): array
    {
        $array = array_unique($array, SORT_REGULAR);
        sort($array);

        return $array;
    }
}
