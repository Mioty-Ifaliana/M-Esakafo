<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250206164355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282CD73DB560 FOREIGN KEY (plat_id) REFERENCES plats (id)');
        $this->addSql('CREATE INDEX IDX_35D4282CD73DB560 ON commandes (plat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282CD73DB560');
        $this->addSql('DROP INDEX IDX_35D4282CD73DB560 ON commandes');
    }
}
