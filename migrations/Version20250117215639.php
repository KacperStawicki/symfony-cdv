<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250117215639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add author relationship to articles';
    }

    public function up(Schema $schema): void
    {
        // Add the author column
        $this->addSql('ALTER TABLE articles ADD author_id INT');

        // Update existing articles with the first user's ID
        $this->addSql('WITH first_user AS (SELECT id FROM "user" ORDER BY id ASC LIMIT 1) UPDATE articles SET author_id = (SELECT id FROM first_user)');

        // Make the column not nullable
        $this->addSql('ALTER TABLE articles ALTER COLUMN author_id SET NOT NULL');

        // Add the foreign key constraint
        $this->addSql('ALTER TABLE articles ADD CONSTRAINT FK_BFDD3168F675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BFDD3168F675F31B ON articles (author_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE articles DROP CONSTRAINT FK_BFDD3168F675F31B');
        $this->addSql('DROP INDEX IDX_BFDD3168F675F31B');
        $this->addSql('ALTER TABLE articles DROP author_id');
    }
}
