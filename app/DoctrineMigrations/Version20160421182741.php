<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Table IDs and position, guest seating link
 */
class Version20160421182741 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `seating_block` (
              `id`          INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
              `workingName` VARCHAR(32)     NULL,
              `themeName`   VARCHAR(32)     NULL,
              `gridX`       INT             NOT NULL,
              `gridY`       INT             NOT NULL,
              `enabled`     TINYINT(1)      NOT NULL DEFAULT 1
            )
              ENGINE = InnoDB
              DEFAULT CHARSET = utf8mb4
              COLLATE = utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `party_seating` (
              userId INT PRIMARY KEY NOT NULL,
              blockId INT NULL
            ) ENGINE = InnoDB
              DEFAULT CHARSET = utf8mb4
              COLLATE = utf8mb4_unicode_ci;"
        );

        $this->addSql(
            "INSERT INTO `seating_block` (`id`,`workingName`, `themeName`, `gridX`, `gridY`) 
              VALUES (1,'Top table', NULL, 0, 0)"
        );
        $this->addSql(
            "INSERT INTO `seating_block` (`id`,`workingName`, `themeName`, `gridX`, `gridY`) VALUES (2,NULL, NULL, 0, 1)"
        );
        $this->addSql(
            "INSERT INTO `seating_block` (`id`,`workingName`, `themeName`, `gridX`, `gridY`) VALUES (3,NULL, NULL, 1, 1)"
        );
        $this->addSql(
            "INSERT INTO `seating_block` (`id`,`workingName`, `themeName`, `gridX`, `gridY`) VALUES (4,NULL, NULL, 2, 1)"
        );
        $this->addSql(
            "INSERT INTO `seating_block` (`id`,`workingName`, `themeName`, `gridX`, `gridY`) VALUES (5,NULL, NULL, 3, 1)"
        );
        $this->addSql(
            "INSERT INTO `seating_block` (`id`,`workingName`, `themeName`, `gridX`, `gridY`) VALUES (6,NULL, NULL, 0, 2)"
        );
        $this->addSql(
            "INSERT INTO `seating_block` (`id`,`workingName`, `themeName`, `gridX`, `gridY`) VALUES (7,NULL, NULL, 1, 2)"
        );
        $this->addSql(
            "INSERT INTO `seating_block` (`id`,`workingName`, `themeName`, `gridX`, `gridY`) VALUES (8, NULL, NULL, 2, 2)"
        );
        $this->addSql(
            "INSERT INTO `seating_block` (`id`,`workingName`, `themeName`, `gridX`, `gridY`) VALUES (9, NULL, NULL, 3, 2)"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE IF EXISTS `seating_block`");
        $this->addSql("DROP TABLE IF EXISTS `party_seating`");
    }
}
