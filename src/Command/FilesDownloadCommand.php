<?php

namespace App\Command;

use App\Modules\Common\Csv\CsvReaderInterface;
use App\Modules\Common\Csv\CsvWriterFactory;
use App\Modules\Common\Csv\Exception\WrongRowFormatException;
use App\Modules\Files\Events\DownloadErrorEvent;
use App\Modules\Files\FilesDownloader;
use App\Modules\Files\Subscribers\DownloadProgressSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
        private readonly CsvWriterFactory $writerFactory,
        private readonly FilesDownloader $downloader,
        private readonly EventDispatcherInterface $eventDispatcher
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

        $total = iterator_count($rows);
        $io->info(sprintf(
            "Найдено [%d] записей в CSV файле '%s'\nПапка с документами: '%s'",
            $total,
            $pathToCsv,
            realpath($outputDir)
        ));

        $progressBar = new ProgressBar($output, iterator_count($rows));
        $progressBar->setFormat('very_verbose');

        // Добавляем слушателя для обработки результатов
        $subscriber = new DownloadProgressSubscriber($progressBar);
        $this->eventDispatcher->addSubscriber($subscriber);

        // Начало обработки
        $progressBar->start();
        $this->downloader->downloadFiles($rows, $outputDir);
        $progressBar->finish();
        // Конец обработки

        // Уведомление об успехе
        $io->newLine();
        $io->success(sprintf(
            'Успешно скачено - [%d]/[%d]',
            $subscriber->getSuccessCount(),
            $total
        ));

        // Обработка ошибок
        $countErrors = count($subscriber->getErrors());
        if ($countErrors > 0) {
            $io->error(
                sprintf(
                    "Всего ошибок - [%s]\n%s",
                    $countErrors,
                    implode(', ', array_map(
                        fn(DownloadErrorEvent $event) => $event->name,
                        $subscriber->getErrors()
                    ))
                )
            );

            try {
                // Записываем ошибки в новый файл для дальнейшей обработки
                $writer = $this->writerFactory->createFromPath(
                    sprintf('%s_missed.csv', realpath($outputDir)), 'w'
                );
                /** @var DownloadErrorEvent $error */
                foreach ($subscriber->getErrors() as $error) {
                    $writer->insertOne([
                        $error->name,
                        $error->url
                    ]);
                }
            } catch (\Throwable $exception) {
                $io->error($exception->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
