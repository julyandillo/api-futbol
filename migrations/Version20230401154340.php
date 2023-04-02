<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230401154340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipo_competicion (id INT AUTO_INCREMENT NOT NULL, equipo_id INT NOT NULL, competicion_id INT NOT NULL, plantilla_id INT NOT NULL, INDEX IDX_880BD46123BFBED (equipo_id), INDEX IDX_880BD461D9407152 (competicion_id), INDEX IDX_880BD461A08F3969 (plantilla_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE equipo_competicion ADD CONSTRAINT FK_880BD46123BFBED FOREIGN KEY (equipo_id) REFERENCES equipo (id)');
        $this->addSql('ALTER TABLE equipo_competicion ADD CONSTRAINT FK_880BD461D9407152 FOREIGN KEY (competicion_id) REFERENCES competicion (id)');
        $this->addSql('ALTER TABLE equipo_competicion ADD CONSTRAINT FK_880BD461A08F3969 FOREIGN KEY (plantilla_id) REFERENCES plantilla (id)');
        $this->addSql('ALTER TABLE equipos_competiciones DROP FOREIGN KEY FK_A778C29223BFBED');
        $this->addSql('ALTER TABLE equipos_competiciones DROP FOREIGN KEY FK_A778C292D9407152');
        $this->addSql('DROP TABLE equipos_competiciones');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipos_competiciones (equipo_id INT NOT NULL, competicion_id INT NOT NULL, INDEX IDX_A778C29223BFBED (equipo_id), INDEX IDX_A778C292D9407152 (competicion_id), PRIMARY KEY(equipo_id, competicion_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE equipos_competiciones ADD CONSTRAINT FK_A778C29223BFBED FOREIGN KEY (equipo_id) REFERENCES equipo (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipos_competiciones ADD CONSTRAINT FK_A778C292D9407152 FOREIGN KEY (competicion_id) REFERENCES competicion (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipo_competicion DROP FOREIGN KEY FK_880BD46123BFBED');
        $this->addSql('ALTER TABLE equipo_competicion DROP FOREIGN KEY FK_880BD461D9407152');
        $this->addSql('ALTER TABLE equipo_competicion DROP FOREIGN KEY FK_880BD461A08F3969');
        $this->addSql('DROP TABLE equipo_competicion');
    }
}
