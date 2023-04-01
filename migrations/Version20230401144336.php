<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230401144336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE plantilla (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plantilla_jugador (id INT AUTO_INCREMENT NOT NULL, jugador_id INT NOT NULL, plantilla_id INT NOT NULL, dorsal SMALLINT NOT NULL, INDEX IDX_D9174142B8A54D43 (jugador_id), INDEX IDX_D9174142A08F3969 (plantilla_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE plantilla_jugador ADD CONSTRAINT FK_D9174142B8A54D43 FOREIGN KEY (jugador_id) REFERENCES jugador (id)');
        $this->addSql('ALTER TABLE plantilla_jugador ADD CONSTRAINT FK_D9174142A08F3969 FOREIGN KEY (plantilla_id) REFERENCES plantilla (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plantilla_jugador DROP FOREIGN KEY FK_D9174142B8A54D43');
        $this->addSql('ALTER TABLE plantilla_jugador DROP FOREIGN KEY FK_D9174142A08F3969');
        $this->addSql('DROP TABLE plantilla');
        $this->addSql('DROP TABLE plantilla_jugador');
    }
}
