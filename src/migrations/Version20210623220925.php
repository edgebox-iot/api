<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210623220925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON user (username)');
        $this->addSql('DROP INDEX UNIQ_5A8600B05E237E06');
        $this->addSql('CREATE TEMPORARY TABLE __temp__option AS SELECT id, name, created, updated, value FROM option');
        $this->addSql('DROP TABLE option');
        $this->addSql('CREATE TABLE option (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL COLLATE BINARY, created DATETIME NOT NULL, updated DATETIME NOT NULL, value CLOB NOT NULL)');
        $this->addSql('INSERT INTO option (id, name, created, updated, value) SELECT id, name, created, updated, value FROM __temp__option');
        $this->addSql('DROP TABLE __temp__option');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A8600B05E237E06 ON option (name)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, task, status, created, updated, args, result FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, task VARCHAR(120) NOT NULL COLLATE BINARY, status INTEGER NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, args CLOB DEFAULT NULL, result CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO task (id, task, status, created, updated, args, result) SELECT id, task, status, created, updated, args, result FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP INDEX UNIQ_5A8600B05E237E06');
        $this->addSql('CREATE TEMPORARY TABLE __temp__option AS SELECT id, name, value, created, updated FROM option');
        $this->addSql('DROP TABLE option');
        $this->addSql('CREATE TABLE option (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, value CLOB NOT NULL COLLATE BINARY)');
        $this->addSql('INSERT INTO option (id, name, value, created, updated) SELECT id, name, value, created, updated FROM __temp__option');
        $this->addSql('DROP TABLE __temp__option');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A8600B05E237E06 ON option (name)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, task, args, status, result, created, updated FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, task VARCHAR(120) NOT NULL, status INTEGER NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, args CLOB DEFAULT NULL COLLATE BINARY, result CLOB DEFAULT NULL COLLATE BINARY)');
        $this->addSql('INSERT INTO task (id, task, args, status, result, created, updated) SELECT id, task, args, status, result, created, updated FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
    }
}
