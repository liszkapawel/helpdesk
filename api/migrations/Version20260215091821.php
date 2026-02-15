<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215091821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_F6E1C0F2C412EE02 ON audit_log');
        $this->addSql('ALTER TABLE audit_log RENAME INDEX idx_f6e1c0f256d7bc41 TO IDX_F6E1C0F52E65C292');
        $this->addSql('DROP INDEX UNIQ_64C19C15E237E06 ON category');
        $this->addSql('ALTER TABLE category ADD organization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE INDEX IDX_64C19C132C8A3DE ON category (organization_id)');
        $this->addSql('ALTER TABLE notification CHANGE is_read is_read TINYINT NOT NULL');
        $this->addSql('ALTER TABLE organization ADD description LONGTEXT DEFAULT NULL, ADD primary_color VARCHAR(7) DEFAULT NULL, ADD logo_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket ADD submitter_name VARCHAR(255) DEFAULT NULL, ADD submitter_email VARCHAR(255) DEFAULT NULL, ADD tracking_token VARCHAR(64) DEFAULT NULL, CHANGE created_by_id created_by_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97A0ADA3B46BB896 ON ticket (tracking_token)');
        $this->addSql('UPDATE category SET organization_id = (SELECT MIN(id) FROM organization) WHERE organization_id IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_F6E1C0F2C412EE02 ON audit_log (entity_type, entity_id)');
        $this->addSql('ALTER TABLE audit_log RENAME INDEX idx_f6e1c0f52e65c292 TO IDX_F6E1C0F256D7BC41');
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C132C8A3DE');
        $this->addSql('DROP INDEX IDX_64C19C132C8A3DE ON category');
        $this->addSql('ALTER TABLE category DROP organization_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C15E237E06 ON category (name)');
        $this->addSql('ALTER TABLE notification CHANGE is_read is_read TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE organization DROP description, DROP primary_color, DROP logo_path');
        $this->addSql('DROP INDEX UNIQ_97A0ADA3B46BB896 ON ticket');
        $this->addSql('ALTER TABLE ticket DROP submitter_name, DROP submitter_email, DROP tracking_token, CHANGE created_by_id created_by_id INT NOT NULL');
    }
}
