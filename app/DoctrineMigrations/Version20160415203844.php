<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add site operation settings to DB
 */
class Version20160415203844 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("INSERT IGNORE INTO settings (settingKey, settingValue) VALUES ('enableRsvp', '')");
        $this->addSql("INSERT IGNORE INTO settings (settingKey, settingValue) VALUES ('enableMenuResponses', '')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        //TODO do we actually want to remove these?
        $this->addSql("DELETE FROM settings WHERE settingKey='enableRsvp'");
        $this->addSql("DELETE FROM settings WHERE settingKey='enableMenuResponses'");
    }
}
