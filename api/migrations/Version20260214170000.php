<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_log and notification tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT NOT NULL, action VARCHAR(50) NOT NULL, changes JSON DEFAULT NULL, created_at DATETIME NOT NULL, performed_by_id INT NOT NULL, INDEX IDX_F6E1C0F256D7BC41 (performed_by_id), INDEX IDX_F6E1C0F2C412EE02 (entity_type, entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, message VARCHAR(500) NOT NULL, link VARCHAR(255) DEFAULT NULL, is_read TINYINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F256D7BC41 FOREIGN KEY (performed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F256D7BC41');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE notification');
    }
}
