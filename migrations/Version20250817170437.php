<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250817170437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993984584665A');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F529939882EA2E54');
        $this->addSql('DROP INDEX IDX_F529939882EA2E54 ON `order`');
        $this->addSql('DROP INDEX IDX_F52993984584665A ON `order`');
        $this->addSql('ALTER TABLE `order` ADD status VARCHAR(60) NOT NULL, ADD stripe_session_id VARCHAR(255) NOT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD total_cents INT NOT NULL, DROP commande_id, DROP product_id, DROP size, DROP quantity, DROP price, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F52993981A314A57 ON `order` (stripe_session_id)');
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL, ADD verification_token VARCHAR(100) DEFAULT NULL, ADD verification_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_F52993981A314A57 ON `order`');
        $this->addSql('ALTER TABLE `order` ADD product_id INT NOT NULL, ADD size VARCHAR(10) NOT NULL, ADD quantity INT NOT NULL, ADD price DOUBLE PRECISION NOT NULL, DROP status, DROP stripe_session_id, DROP created_at, CHANGE user_id user_id INT NOT NULL, CHANGE total_cents commande_id INT NOT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993984584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F529939882EA2E54 FOREIGN KEY (commande_id) REFERENCES `order` (id)');
        $this->addSql('CREATE INDEX IDX_F529939882EA2E54 ON `order` (commande_id)');
        $this->addSql('CREATE INDEX IDX_F52993984584665A ON `order` (product_id)');
        $this->addSql('ALTER TABLE `user` DROP is_verified, DROP verification_token, DROP verification_expires_at');
    }
}
