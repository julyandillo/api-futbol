<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230106175835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipos_estadios (equipo_id INT NOT NULL, estadio_id INT NOT NULL, en_uso BOOL DEFAULT false, INDEX IDX_ASD (equipo_id), INDEX IDX_ASDW (estadio_id), PRIMARY KEY(equipo_id, estadio_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE equipos_estadios ADD CONSTRAINT FK_1234 FOREIGN KEY(equipo_id) REFERENCES equipo(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipos_estadios ADD CONSTRAINT FK_12345 FOREIGN KEY(estadio_id) REFERENCES estadio(id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipos_estadios DROP FOREIGN KEY FK_1234');
        $this->addSql('ALTER TABLE equipos_estadios DROP FOREIGN KEY FK_12345');
        $this->addSql('DROP TABLE equipos_estadios');
    }
}
