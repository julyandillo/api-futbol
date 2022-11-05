<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221105124647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipo (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, nombre_completo VARCHAR(255) NOT NULL, nombre_abreviado VARCHAR(5) NOT NULL, pais VARCHAR(255) NOT NULL, fundacion INT NOT NULL, presidente VARCHAR(255) NOT NULL, ciudad VARCHAR(255) DEFAULT NULL, web VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE equipos_competiciones (equipo_id INT NOT NULL, competicion_id INT NOT NULL, INDEX IDX_A778C29223BFBED (equipo_id), INDEX IDX_A778C292D9407152 (competicion_id), PRIMARY KEY(equipo_id, competicion_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE equipos_competiciones ADD CONSTRAINT FK_A778C29223BFBED FOREIGN KEY (equipo_id) REFERENCES equipo (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipos_competiciones ADD CONSTRAINT FK_A778C292D9407152 FOREIGN KEY (competicion_id) REFERENCES competicion (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipos_competiciones DROP FOREIGN KEY FK_A778C29223BFBED');
        $this->addSql('ALTER TABLE equipos_competiciones DROP FOREIGN KEY FK_A778C292D9407152');
        $this->addSql('DROP TABLE equipo');
        $this->addSql('DROP TABLE equipos_competiciones');
    }
}
