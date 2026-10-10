<?php

namespace App\Command\Templates;

use DateTime;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'templates:upload',
    description: 'Заливает шаблоны с прода на тестовый стенд.',
)]
class DownloadCommand extends Command
{
    const PROD_PREFIX = 'PROD';
    const STAGE_PREFIX = 'STAGE';

    protected function configure(): void
    {
        $this
            ->addArgument('templates', InputArgument::OPTIONAL, 'Список шаблонов через запятую');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Получаем шаблоны для фильтрации
        if ($input->getArgument('templates')) {
            $keepTemplates = array_map('trim', explode(',', $input->getArgument('templates')));
        } else {
            $keepTemplates = [];
        }

        try {
            // Получаем шаблоны с прода
            $prodVersions = $this->getVersions(self::PROD_PREFIX);
            if (count($keepTemplates) !== 0) {
                $prodVersions = array_intersect_key($prodVersions, array_flip($keepTemplates));
            }

            // Получаем шаблоны со стейджа для маппинга
            $stageVersions = $this->getVersions(self::STAGE_PREFIX);
            if (count($keepTemplates) !== 0) {
                $stageVersions = array_intersect_key($stageVersions, array_flip($keepTemplates));
            }

            foreach ($prodVersions as $type => $data) {
                if (!isset($stageVersions[$type])) {
                    $io->error(sprintf('Stage version `%s` does not exist', $type));
                    continue;
                }

                // Убираем id-шник для импорта
                unset($data['id']);
                // Заменяем id шаблона на стейджевую
                $data['template_id'] = $stageVersions[$type]['template_id'];
                // Выставляем следующую по порядку версию на стейдже
                $data['version'] = $stageVersions[$type]['version'] + 1;
                // Добавляем к описанию текст о том, что это импорт
                $data['description'] = sprintf(
                    'Импорт версии %d. %s',
                    $data['version'],
                    $data['description']
                );
                // Меняем даты создания и публикации
                $data['created_at'] = (new DateTime())->format('Y-m-d H:i:s');
                $data['publish_at'] = (new DateTime())->format('Y-m-d H:i:s');

                // Создаем новую версию на стейдже
                $newId = $this->runInsert($data, self::STAGE_PREFIX);

                $io->writeln(sprintf('%s создан, id записи #%d', $type, $newId));
            }

            $io->success('Импорт успешно завершен!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Database error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function getVersions(string $prefix): array
    {
        // Получаем шаблоны
        $templates = $this->runSelect('
            SELECT id, string_id
            FROM template
        ', $prefix);

        // Приводим массив к виду [ id => string_id ]
        $templateIds = array_column($templates, 'string_id', 'id');

        // Получаем последние версии
        $versionsData = $this->runSelect('
            SELECT t1.*
            FROM template_version t1
            WHERE t1.version = (
                SELECT MAX(version)
                FROM template_version t2
                WHERE t2.template_id = t1.template_id
            )
            ORDER BY t1.template_id;
        ', $prefix);

        // Приводим массив к виду [ string_id => [ version ] ]
        $versions = [];
        foreach ($versionsData as $version) {
            $key = $templateIds[$version['template_id']];
            $versions[$key] = $version;
        }

        return $versions;
    }

    /**
     * @throws Exception
     */
    private function runSelect(string $sql, string $databasePrefix): array
    {
        $connection = DriverManager::getConnection([
            'dbname' => $_ENV[$databasePrefix . '_DB_NAME'],
            'user' => $_ENV[$databasePrefix . '_DB_USER'],
            'password' => $_ENV[$databasePrefix . '_DB_PASSWORD'],
            'host' => $_ENV[$databasePrefix . '_DB_HOST'],
            'driver' => 'pdo_mysql',
        ]);

        $result = $connection->fetchAllAssociative($sql);
        $connection->close();

        return $result;
    }

    public function runInsert(array $data, string $databasePrefix): int
    {
        $connection = DriverManager::getConnection([
            'dbname' => $_ENV[$databasePrefix . '_DB_NAME'],
            'user' => $_ENV[$databasePrefix . '_DB_USER'],
            'password' => $_ENV[$databasePrefix . '_DB_PASSWORD'],
            'host' => $_ENV[$databasePrefix . '_DB_HOST'],
            'driver' => 'pdo_mysql',
        ]);

        $connection->insert('template_version', $data);
        $id = $connection->lastInsertId();
        $connection->close();

        return $id;
    }
}
