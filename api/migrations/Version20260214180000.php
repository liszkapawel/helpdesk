<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create attachment table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE attachment (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, created_at DATETIME NOT NULL, uploaded_by_id INT NOT NULL, ticket_id INT DEFAULT NULL, comment_id INT DEFAULT NULL, INDEX IDX_795FD9BBA2B28FE8 (uploaded_by_id), INDEX IDX_795FD9BB700047D2 (ticket_id), INDEX IDX_795FD9BBF8697D13 (comment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BB700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE attachment ADD CONSTRAINT FK_795FD9BBF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBA2B28FE8');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BB700047D2');
        $this->addSql('ALTER TABLE attachment DROP FOREIGN KEY FK_795FD9BBF8697D13');
        $this->addSql('DROP TABLE attachment');
    }
}
