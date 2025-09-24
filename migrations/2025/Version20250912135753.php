<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250912135753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE request_butcher_session (id SERIAL NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, params JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE request_butcher_task (id SERIAL NOT NULL, session_id INT NOT NULL, identifier INT NOT NULL, status VARCHAR(255) NOT NULL, params JSON NOT NULL, last_error TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E4BC23B7613FECDF ON request_butcher_task (session_id)');
        $this->addSql('ALTER TABLE request_butcher_task ADD CONSTRAINT FK_E4BC23B7613FECDF FOREIGN KEY (session_id) REFERENCES request_butcher_session (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_IDENTIFIER ON request_butcher_task (identifier)');
        $this->addSql('CREATE INDEX IDX_STATUS ON request_butcher_task (status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE request_butcher_task DROP CONSTRAINT FK_E4BC23B7613FECDF');
        $this->addSql('DROP TABLE request_butcher_session');
        $this->addSql('DROP TABLE request_butcher_task');
    }
}
