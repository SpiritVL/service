<?php

namespace App\Controller;

use App\Repository\RequestButcher\SessionRepository;
use App\Repository\RequestButcher\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class AdminController extends AbstractController
{
    public function __construct(
        private readonly SessionRepository $sessionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskRepository $taskRepository,
    ) {
    }

    #[Route('/rb/sessions', name: 'sessions')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'sessions' => $this->sessionRepository->findAll(),
            'processes' => $this->listProcesses(),
        ]);
    }

    #[Route('/rb/sessions/run', name: 'sessions_run')]
    public function run(
        #[MapQueryParameter] int $sessionId,
        #[MapQueryParameter] int $threads
    ): Response {
        $session = $this->sessionRepository->find($sessionId);

        // Формируем команду с nohup и & — процесс живёт сам по себе
        $command = sprintf(
            'nohup php /app/bin/console rb:run-background -s %d -t %d > /app/var/log/session_%d.log 2>&1 & echo $!',
            $sessionId,
            $threads,
            $sessionId,
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        // Первая строка вывода — это PID (echo $!)
        $pid = ! empty($output) ? (int) $output[0] : null;

        if (! $pid || $exitCode !== 0) {
            $this->addFlash('danger', 'Не удалось запустить процесс');

            return $this->redirect($this->generateUrl('sessions'));
        }

        // Проверяем что процесс реально жив
        if (! file_exists("/proc/{$pid}")) {
            $this->addFlash('danger', "Процесс PID {$pid} умер сразу после запуска. Смотри лог.");

            return $this->redirect($this->generateUrl('sessions'));
        }

        // Сохраняем
        $session->setPid($pid);
        $this->entityManager->flush();

        $this->addFlash('info', "Запущено. PID: {$pid}");

        return $this->redirect($this->generateUrl('sessions'));
    }

    #[Route('/rb/sessions/stop', name: 'sessions_stop')]
    public function stop(
        #[MapQueryParameter] int $pid
    ): Response {
        $session = $this->sessionRepository->findOneBy(['pid' => $pid]);

        if ($pid && file_exists("/proc/{$pid}")) {
            exec("kill -TERM {$pid}");
            sleep(1);

            // Если ещё жив — жёсткая
            if (file_exists("/proc/{$pid}")) {
                exec("kill -KILL {$pid}");
                sleep(1);
                $this->addFlash('warning', "Пришлось остановить жестко. PID: {$pid}");
            } else {
                $this->addFlash('info', "Процесс мягко остановлен. PID: {$pid}");
            }
        } else {
            $this->addFlash('danger', "Процесс не найден. PID: {$pid}");
        }

        if ($session) {
            $session->setPid(null);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('sessions'));
    }

    #[Route('/rb/sessions/reset', name: 'sessions_reset')]
    public function reset(#[MapQueryParameter] int $sessionId): Response
    {
        $affectedRaws = $this->taskRepository->resetStatus($sessionId);
        $this->addFlash('success', "Сессия успешно сброшена. Затронуто: {$affectedRaws}");

        return $this->redirect($this->generateUrl('sessions'));
    }

    private function listProcesses(): array
    {
        $processes = [];
        exec("pgrep -af 'rb:run'", $output);

        foreach ($output as $line) {
            if (preg_match('/^(\d+)\s+.+?-s\s+(\d+)\s+-t\s+(\d+)/', $line, $matches)) {
                $pid = (int) $matches[1];
                $sessionId = (int) $matches[2];
                $threads = (int) $matches[3];

                $processes[] = [
                    'pid' => $pid,
                    'sessionId' => $sessionId,
                    'threads' => $threads,
                ];
            }
        }

        return $processes;
    }
}
