<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216102020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE faq_article (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, position INT NOT NULL, is_published TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, organization_id INT NOT NULL, INDEX IDX_194B653D32C8A3DE (organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE satisfaction_rating (id INT AUTO_INCREMENT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, ticket_id INT NOT NULL, UNIQUE INDEX UNIQ_8C7CDF25700047D2 (ticket_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sla_policy (id INT AUTO_INCREMENT NOT NULL, priority VARCHAR(20) NOT NULL, response_hours INT NOT NULL, resolution_hours INT NOT NULL, organization_id INT NOT NULL, INDEX IDX_CCC0B99332C8A3DE (organization_id), UNIQUE INDEX UNIQ_CCC0B99332C8A3DE62A6DC27 (organization_id, priority), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE faq_article ADD CONSTRAINT FK_194B653D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE satisfaction_rating ADD CONSTRAINT FK_8C7CDF25700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE sla_policy ADD CONSTRAINT FK_CCC0B99332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE ticket ADD first_response_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE faq_article DROP FOREIGN KEY FK_194B653D32C8A3DE');
        $this->addSql('ALTER TABLE satisfaction_rating DROP FOREIGN KEY FK_8C7CDF25700047D2');
        $this->addSql('ALTER TABLE sla_policy DROP FOREIGN KEY FK_CCC0B99332C8A3DE');
        $this->addSql('DROP TABLE faq_article');
        $this->addSql('DROP TABLE satisfaction_rating');
        $this->addSql('DROP TABLE sla_policy');
        $this->addSql('ALTER TABLE ticket DROP first_response_at');
    }
}
