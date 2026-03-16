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
    name: 'parse:array-diff',
    description: 'Ищет отличия в массиве строк из файлов. Можно искать отличия или вхождения.',
)]
class ArrayDiffCommand extends Command
{
    public const MODULE_NAME = 'parse';
    public const DIFF = 'diff';
    public const INTERSECT = 'intersect';

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
        $question = new ChoiceQuestion('Файл, в котором есть лишние записи', $files);
        $question->setErrorMessage('Файл %s не существует.');
        $firstFile = $helper->ask($input, $output, $question);

        // Предотвращаем дублирование файлов
        $key = array_search($firstFile, $files);
        if ($key !== false) {
            unset($files[$key]);
        }

        $question = new ChoiceQuestion('Файл, в котором этих записей не должно быть', $files);
        $question->setErrorMessage('Файл %s не существует.');
        $secondFile = $helper->ask($input, $output, $question);

        $question = new ChoiceQuestion('Выберите вариант сравнения', [
            self::DIFF => 'Отличия',
            self::INTERSECT => 'Совпадения'
        ]);
        $question->setErrorMessage('Файл %s не существует.');
        $mode = $helper->ask($input, $output, $question);

        $firstFilePath = $this->finder->getFilePath($firstFile, self::MODULE_NAME);
        $secondFilePath = $this->finder->getFilePath($secondFile, self::MODULE_NAME);

        $first = explode(PHP_EOL, $this->filesystem->readFile($firstFilePath));
        $second = explode(PHP_EOL, $this->filesystem->readFile($secondFilePath));

        $first = $this->normalize($first);
        $second = $this->normalize($second);

        $result = [];
        if ($mode === self::DIFF) {
            $result = array_diff($first, $second);
        }

        if ($mode === self::INTERSECT) {
            $result = array_intersect($first, $second);
        }

        $this->filesystem->dumpFile(
            sprintf('%s/result-%s.csv', $this->finder->getProjectPath(self::MODULE_NAME), $mode),
            implode(PHP_EOL, $result)
        );

        $io->newLine();
        $io->success(
            sprintf('Найдено записей: %d', count($result))
        );

        return Command::SUCCESS;
    }

    private function normalize(array $array): array
    {
        $array = array_unique($array, SORT_REGULAR);
        sort($array);

        return $array;
    }
}
