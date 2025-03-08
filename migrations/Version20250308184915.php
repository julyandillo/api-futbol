<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250308184915 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE jornada_partido (id INT AUTO_INCREMENT NOT NULL, jornada_id INT DEFAULT NULL, partido_id INT DEFAULT NULL, INDEX IDX_6E3D451E26E992D9 (jornada_id), INDEX IDX_6E3D451E11856EB4 (partido_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE jornada_partido ADD CONSTRAINT FK_6E3D451E26E992D9 FOREIGN KEY (jornada_id) REFERENCES jornada (id)');
        $this->addSql('ALTER TABLE jornada_partido ADD CONSTRAINT FK_6E3D451E11856EB4 FOREIGN KEY (partido_id) REFERENCES partido (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jornada_partido DROP FOREIGN KEY FK_6E3D451E26E992D9');
        $this->addSql('ALTER TABLE jornada_partido DROP FOREIGN KEY FK_6E3D451E11856EB4');
        $this->addSql('DROP TABLE jornada_partido');
    }
}
