<?php namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Database schema setup
 */
class Version20160415195439 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `fos_user` ( 
              `id` INT(11) NOT NULL AUTO_INCREMENT, 
              `username` VARCHAR(127) NOT NULL, 
              `username_canonical` VARCHAR(127) NOT NULL, 
              `email` VARCHAR(127) NOT NULL, 
              `email_canonical` VARCHAR(127) NOT NULL, 
              `enabled` TINYINT(1) NOT NULL, 
              `salt` VARCHAR(127) NOT NULL, 
              `password` VARCHAR(127) NOT NULL, 
              `last_login` DATETIME DEFAULT NULL, 
              `locked` TINYINT(1) NOT NULL, `expired` TINYINT(1) NOT NULL, 
              `expires_at` DATETIME DEFAULT NULL, 
              `confirmation_token` VARCHAR(127) DEFAULT NULL, 
              `password_requested_at` DATETIME DEFAULT NULL, 
              `roles` LONGTEXT NOT NULL COMMENT '(DC2Type:array)', 
              `credentials_expired` TINYINT(1) NOT NULL, 
              `credentials_expire_at` DATETIME DEFAULT NULL, 
              PRIMARY KEY (`id`), 
              UNIQUE KEY `UNIQ_957A647992FC23A8` (`username_canonical`), 
              UNIQUE KEY `UNIQ_957A6479A0D96FBF` (`email_canonical`) 
              ) 
              ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `contacts_guest` (
              `id` INT(11) NOT NULL AUTO_INCREMENT, 
              `userId` INT(11) DEFAULT NULL, 
              `isPrimary` TINYINT(4) NOT NULL DEFAULT '0', 
              `contactName` TEXT, 
              `contactEmail` TEXT, 
              `rsvp` ENUM('ATTENDING', 'NOT ATTENDING') DEFAULT NULL, 
              `requirements` TEXT, 
              PRIMARY KEY (`id`) 
              ) 
              ENGINE = InnoDB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `contacts_update` (
              `id` INT(11) NOT NULL AUTO_INCREMENT, 
              `userId` INT(11) NOT NULL, 
              `data` TEXT, 
              `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
              PRIMARY KEY (`id`) 
              ) 
              ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `notifications_sent` ( 
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `userId` INT(11) NULL,
              `email` TEXT,
              `identifier` TEXT,
              `sent` TIMESTAMP NULL DEFAULT NULL,
              PRIMARY KEY (`id`) 
             ) 
             ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `notification_emails` ( 
              `id` INT(11) NOT NULL AUTO_INCREMENT,
             `identifier` TEXT,
             `subject` TEXT,
             `body` TEXT,
             `sent` TIMESTAMP NULL DEFAULT NULL,
             PRIMARY KEY (`id`) 
             )
             ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `menu_selection` ( 
              `id` INT(11) NOT NULL AUTO_INCREMENT,
             `guestId` INT(11) NOT NULL,
             `userId` INT(11) NOT NULL,
             `menu` TEXT,
             `starter` TEXT,
             `main` TEXT,
             `dessert` TEXT,
             `preferences` TEXT,
             PRIMARY KEY (`id`) 
             )
            ENGINE = InnoDB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `menu_selection_update` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
             `userId` INT(11) NOT NULL,
             `data` TEXT,
             `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
             PRIMARY KEY (`id`) ) 
             ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `settings` ( 
            id INT PRIMARY KEY NOT NULL AUTO_INCREMENT, 
            settingKey VARCHAR(31) UNIQUE NOT NULL, 
            settingValue TEXT 
            )
            ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE IF EXISTS `contacts_guest`");
        $this->addSql("DROP TABLE IF EXISTS `contacts_update`");
        $this->addSql("DROP TABLE IF EXISTS `fos_user`");
        $this->addSql("DROP TABLE IF EXISTS `menu_selection`");
        $this->addSql("DROP TABLE IF EXISTS `menu_selection_update`");
        $this->addSql("DROP TABLE IF EXISTS `migration_versions`");
        $this->addSql("DROP TABLE IF EXISTS `notification_emails`");
        $this->addSql("DROP TABLE IF EXISTS `notifications_sent`");
        $this->addSql("DROP TABLE IF EXISTS `settings`");
    }
} 