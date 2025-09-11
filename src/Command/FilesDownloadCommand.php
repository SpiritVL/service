<?php

namespace App\Command;

use App\Modules\Common\Csv\CsvReaderInterface;
use App\Modules\Common\Csv\Exception\WrongRowFormatException;
use App\Modules\Files\FilesDownloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'files:download',
    description: 'Скачивает файлы. Нужен csv файл, в котором 2 колонки: новое имя файла, полный путь до файла',
)]
class FilesDownloadCommand extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly CsvReaderInterface $nameUrlCsvReader,
        private readonly FilesDownloader $downloader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Скачивает файлы из CSV файла, переименовывает их и складывает в папку')
            ->addArgument('csv', InputArgument::REQUIRED, 'Путь до CSV файла');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pathToCsv = $input->getArgument('csv');

        try {
            // Пробуем получить строки
            $rows = $this->nameUrlCsvReader->read($pathToCsv);

            $outputDir = explode('.', $pathToCsv)[0];
            $this->filesystem->mkdir($outputDir);
        } catch (WrongRowFormatException|FileNotFoundException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->writeln(sprintf('Найдено %d записей в CSV файле', iterator_count($rows)));
        $io->writeln(sprintf('Скачиваю файлы в папку: %s', realpath($outputDir)));
        $io->newLine();

        $successCount = 0;

        $progressBar = new ProgressBar($output, iterator_count($rows));
        $progressBar->start();

        $this->downloader->downloadFiles($rows, $outputDir, function () use (&$progressBar, &$successCount) {
            $successCount++;
            $progressBar->advance();
        });

        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf('Успешно скачено %d файлов', $successCount));

        return Command::SUCCESS;
    }
}
