<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250206083736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recettes (id INT AUTO_INCREMENT NOT NULL, quantite DOUBLE PRECISION NOT NULL, plat_id INT NOT NULL, ingredient_id INT NOT NULL, INDEX IDX_EB48E72CD73DB560 (plat_id), INDEX IDX_EB48E72C933FE08C (ingredient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE recettes ADD CONSTRAINT FK_EB48E72CD73DB560 FOREIGN KEY (plat_id) REFERENCES plats (id)');
        $this->addSql('ALTER TABLE recettes ADD CONSTRAINT FK_EB48E72C933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recettes DROP FOREIGN KEY FK_EB48E72CD73DB560');
        $this->addSql('ALTER TABLE recettes DROP FOREIGN KEY FK_EB48E72C933FE08C');
        $this->addSql('DROP TABLE recettes');
    }
}
