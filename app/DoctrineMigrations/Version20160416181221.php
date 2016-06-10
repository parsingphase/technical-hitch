<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add settings description field
 */
class Version20160416181221 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE `settings` ADD COLUMN `description` VARCHAR(64) NULL AFTER settingValue"
        );

        $this->addSql(
            "UPDATE `settings` SET `description` = 'Non-blank to allow guests to RSVP' WHERE `settingKey`='enableRsvp'"
        );

        $this->addSql(
            "UPDATE `settings` SET `description` = 'Non-blank to allow guests to choose menu options' 
              WHERE `settingKey`='enableMenuResponses'"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE `settings` DROP COLUMN `description`"
        );
    }
}
