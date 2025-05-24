<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250413124907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trade (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, agent_id INT DEFAULT NULL, trade_size INT NOT NULL, lot_count INT NOT NULL, pnl NUMERIC(20, 8) NOT NULL, payout NUMERIC(15, 8) NOT NULL, used_margin NUMERIC(15, 8) NOT NULL, entry_rate NUMERIC(15, 8) NOT NULL, close_rate NUMERIC(15, 8) NOT NULL, date_created DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', date_closed DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(50) NOT NULL, position VARCHAR(20) NOT NULL, stop_loss INT NOT NULL, take_profit INT NOT NULL, userID BIGINT NOT NULL, agentID BIGINT DEFAULT NULL, INDEX IDX_7E1A43665FD86D04 (userID), INDEX IDX_7E1A43662C856E89 (agentID), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE trade ADD CONSTRAINT FK_7E1A43665FD86D04 FOREIGN KEY (userID) REFERENCES user (id)');
        $this->addSql('ALTER TABLE trade ADD CONSTRAINT FK_7E1A43662C856E89 FOREIGN KEY (agentID) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE trade');
    }
}
