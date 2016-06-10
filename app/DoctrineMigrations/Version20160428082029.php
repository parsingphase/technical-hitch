<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add profile functions
 */
class Version20160428082029 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        //TODO support gravtar / file upload?
        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `guest_profile` (
              `guestId`          INT PRIMARY KEY NOT NULL,
              `allowMessages`     TINYINT(1)      NOT NULL DEFAULT 0,
              `facebook`     VARCHAR(128)          NULL,
              `twitter`      VARCHAR(128)          NULL,
              `photoUrl`     VARCHAR(128)          NULL,
              `otherLinkUrl`     VARCHAR(128)      NULL,
              `otherLinkName`     VARCHAR(128)     NULL
            )
              ENGINE = InnoDB
              DEFAULT CHARSET = utf8mb4
              COLLATE = utf8mb4_unicode_ci"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE IF EXISTS `guest_profile`");
    }
}
