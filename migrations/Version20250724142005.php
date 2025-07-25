<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250724142005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE orders SET package_title = CONCAT(package_title, " ", package_level)');
        $this->addSql('ALTER TABLE orders DROP package_level, CHANGE package_title product_name VARCHAR(255) NOT NULL, CHANGE package_price product_price INT NOT NULL');
        $this->addSql('ALTER TABLE orders ADD product_type VARCHAR(255) NOT NULL AFTER product_name');
        $this->addSql('UPDATE orders SET product_type = "package"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders ADD package_title VARCHAR(255) NOT NULL, CHANGE product_name package_level VARCHAR(255) NOT NULL, CHANGE product_price package_price INT NOT NULL');
        $this->addSql('ALTER TABLE orders DROP product_type');
    }
}
