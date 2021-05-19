<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210501135654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE option (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, value CLOB NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A8600B05E237E06 ON option (name)');
        $this->addSql('CREATE TABLE task (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, task VARCHAR(120) NOT NULL, args CLOB NOT NULL, status INTEGER NOT NULL, result CLOB DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE option');
        $this->addSql('DROP TABLE task');
    }
}
