<?php

namespace App\Command\RequestButcher;

use App\Modules\RequestButcher\Metrics\RunWithTimerCollector;
use App\Repository\RequestButcher\SessionRepository;
use App\Repository\RequestButcher\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'rb:run-background',
    description: 'Запуск обработки в фоновом режиме',
)]
class RunBackgroundCommand extends Command
{
    public function __construct(
        private readonly string $url,
        private readonly string $session,
        private readonly TaskRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Client $client,
        private readonly RunWithTimerCollector $collector,
        private readonly SessionRepository $sessionRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('session', 's', InputOption::VALUE_OPTIONAL, 'ID сессии', 0)
            ->addOption('threads', 't', InputOption::VALUE_OPTIONAL, 'Количество потоков', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sessionId = $input->getOption('session');
        $threads = $input->getOption('threads');
        $session = $this->sessionRepository->find($sessionId);

        while (true) {
            $chunk = $this->repository->findNewChunkBySessionId($sessionId, $threads);
            if (empty($chunk)) {
                break;
            }

            $promises = [];

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

                            $this->logger->error($throwable->getMessage(), [
                                'status' => $response->getStatusCode(),
                                'body' => $response->getBody()->getContents(),
                            ]);
                        }
                    },
                    function (\Throwable $throwable) use ($task) {
                        $this->collector->stop((string) $task->getId());
                        $task->setStatus('error');
                        $task->setLastError($throwable->getMessage());
                        $this->collector->incDone($task->getSession(), $task, false);
                    }
                );
                $promises[] = $promise;
            }

            Utils::settle($promises)->wait();

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $session->setPid(null);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
