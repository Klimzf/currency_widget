<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512175843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE currency (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(10) NOT NULL, name VARCHAR(100) NOT NULL, nominal INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE exchange_rate (id INT AUTO_INCREMENT NOT NULL, currency_id INT NOT NULL, date DATE NOT NULL, value NUMERIC(10,4) NOT NULL, vunit_rate NUMERIC(10,4) DEFAULT NULL, INDEX IDX_RATE_CURRENCY (currency_id), UNIQUE INDEX unique_currency_date (currency_id, date), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE setting (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value TEXT DEFAULT NULL, UNIQUE INDEX UNIQ_SETTING_NAME (name), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE exchange_rate ADD CONSTRAINT FK_RATE_CURRENCY FOREIGN KEY (currency_id) REFERENCES currency (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exchange_rate DROP FOREIGN KEY FK_E9521FAB38248176');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE exchange_rate');
        $this->addSql('DROP TABLE setting');
    }
}
