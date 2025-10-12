<?php

namespace App\Command\RequestButcher;

use App\Entity\RequestButcher\Task;
use App\Modules\RequestButcher\Metrics\RunWithTimerCollector;
use App\Repository\RequestButcher\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rb:run',
    description: 'Запуск обработки',
)]
class RunCommand extends Command
{
    public function __construct(
        private readonly string $url,
        private readonly string $session,
        private readonly TaskRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Client $client,
        private readonly RunWithTimerCollector $collector
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('sessionId', InputArgument::REQUIRED, 'Идентификатор сессии')
            ->addOption('threads', 't', InputOption::VALUE_OPTIONAL, 'Количество потоков', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sessionId = $input->getArgument('sessionId');
        $threads = $input->getOption('threads');

        $tasks = $this->repository->findNewBySessionId($sessionId);
        $steps = ceil(count($tasks) / $threads);

        $io->title(sprintf("Запуск обработки сессии № %d\nКол-во потоков: %d", $sessionId, $threads));
        $question = new Question(sprintf('Запустить обработку [%d] шагов? [y/n]', $steps), 'y');

        if ($io->askQuestion($question) !== 'y') {
            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, $steps);
        $progressBar->setFormat('very_verbose');
        $progressBar->start();
        $success = 0;

        foreach (array_chunk($tasks, $threads) as $chunk) {
            $promises = [];

            /** @var Task $task */
            foreach ($chunk as $task) {
                $this->collector->start((string) $task->getId());
                $task->setStatus('pending');
                $this->entityManager->flush();

                $jar = CookieJar::fromArray(['session_id' => $this->session], 'zaymigo.ru');
                $promise = $this->client->postAsync(
                    $this->url,
                    [
                        'http_errors' => false,
                        'form_params' => $task->getRequestBody(),
                        'cookies' => $jar
                    ]
                );
                $promise->then(
                    function (Response $response) use ($task, &$success) {
                        $this->collector->stop((string) $task->getId());
                        try {
                            $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                            if ($json['status'] === 'success') {
                                $success++;
                                $task->setStatus('done');
                                $this->collector->incDone($task->getSession(), $task, true);
                            } else {
                                $task->setStatus('error');
                                $task->setLastError($json['message']);
                                $this->collector->incDone($task->getSession(), $task, false);
                            }
                        } catch (\Throwable $throwable) {
                            $task->setStatus('error');
                            $task->setLastError('Wrong response');
                            $this->collector->incDone($task->getSession(), $task, false);
                        }
                    },
                    function (\Throwable $e) use ($task) {
                        $this->collector->stop((string) $task->getId());
                        $task->setStatus('error');
                        $task->setLastError($e->getMessage());
                        $this->collector->incDone($task->getSession(), $task, false);
                    }
                );
                $promises[] = $promise;
            }

            Utils::settle($promises)->wait();

            $this->entityManager->flush();
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->entityManager->flush();

        // Уведомление об успехе
        $io->newLine(2);
        $io->writeln(sprintf('Успешно обработано %d/%d шагов', $success, count($tasks)));
        $io->success('Обработка завершена');

        return Command::SUCCESS;
    }
}
