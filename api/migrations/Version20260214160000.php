<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organization, invite tables; add organization_id to user and ticket';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_C1EE637C989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('CREATE TABLE invite (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(64) NOT NULL, email VARCHAR(255) DEFAULT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, organization_id INT NOT NULL, created_by_id INT NOT NULL, used_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_C7E210D777153098 (code), INDEX IDX_C7E210D732C8A3DE (organization_id), INDEX IDX_C7E210D7B03A8386 (created_by_id), INDEX IDX_C7E210D74C2B72A8 (used_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        // Add organization_id to user (nullable first for data migration)
        $this->addSql('ALTER TABLE `user` ADD organization_id INT DEFAULT NULL');

        // Create a default organization for existing data
        $this->addSql("INSERT INTO organization (name, slug, created_at) VALUES ('Default', 'default', NOW())");
        $this->addSql('UPDATE `user` SET organization_id = (SELECT id FROM organization WHERE slug = \'default\') WHERE organization_id IS NULL');

        // Now make it NOT NULL
        $this->addSql('ALTER TABLE `user` MODIFY organization_id INT NOT NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE INDEX IDX_8D93D64932C8A3DE ON `user` (organization_id)');

        // Add organization_id to ticket
        $this->addSql('ALTER TABLE ticket ADD organization_id INT DEFAULT NULL');
        $this->addSql('UPDATE ticket SET organization_id = (SELECT id FROM organization WHERE slug = \'default\') WHERE organization_id IS NULL');
        $this->addSql('ALTER TABLE ticket MODIFY organization_id INT NOT NULL');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE INDEX IDX_97A0ADA332C8A3DE ON ticket (organization_id)');

        // Invite FKs
        $this->addSql('ALTER TABLE invite ADD CONSTRAINT FK_C7E210D732C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE invite ADD CONSTRAINT FK_C7E210D7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE invite ADD CONSTRAINT FK_C7E210D74C2B72A8 FOREIGN KEY (used_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invite DROP FOREIGN KEY FK_C7E210D732C8A3DE');
        $this->addSql('ALTER TABLE invite DROP FOREIGN KEY FK_C7E210D7B03A8386');
        $this->addSql('ALTER TABLE invite DROP FOREIGN KEY FK_C7E210D74C2B72A8');
        $this->addSql('DROP TABLE invite');

        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA332C8A3DE');
        $this->addSql('ALTER TABLE ticket DROP INDEX IDX_97A0ADA332C8A3DE');
        $this->addSql('ALTER TABLE ticket DROP COLUMN organization_id');

        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64932C8A3DE');
        $this->addSql('ALTER TABLE `user` DROP INDEX IDX_8D93D64932C8A3DE');
        $this->addSql('ALTER TABLE `user` DROP COLUMN organization_id');

        $this->addSql('DROP TABLE organization');
    }
}
