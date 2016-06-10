ALTER TABLE wedding.contacts_party ADD userId INT NULL;
ALTER TABLE wedding.contacts_party ADD rsvp ENUM('ATTENDING', 'NOT ATTENDING') DEFAULT NULL NULL;
ALTER TABLE wedding.contacts_guest ADD rsvp ENUM('ATTENDING', 'NOT ATTENDING') DEFAULT NULL NULL;

ALTER TABLE notifications_sent ADD COLUMN email TEXT
AFTER userId;
ALTER TABLE notifications_sent MODIFY COLUMN userId INT NULL;

ALTER TABLE contacts_party
ADD COLUMN requirements TEXT NULL DEFAULT NULL AFTER rsvp;
ALTER TABLE contacts_guest
ADD COLUMN requirements TEXT NULL DEFAULT NULL AFTER rsvp;

ALTER TABLE notifications_sent
MODIFY COLUMN `sent` TIMESTAMP NULL DEFAULT NULL;

-- /////////

ALTER TABLE contacts_guest
ADD COLUMN `userId` INT NULL
AFTER id;

UPDATE contacts_guest cg
SET userId =
(SELECT userId
 FROM contacts_party
 WHERE id = cg.contactPartyId);

ALTER TABLE contacts_guest
ADD COLUMN `isPrimary` TINYINT NOT NULL DEFAULT 0
AFTER userId;

INSERT INTO contacts_guest
  SELECT
    NULL AS id,
    userId,
    1    AS `isPrimary`,
    id   AS contactPartyId,
    contactName,
    contactEmail,
    rsvp,
    requirements
  FROM contacts_party;

RENAME TABLE contacts_party TO _contacts_party;

ALTER TABLE contacts_guest DROP COLUMN contactPartyId;


CREATE TABLE wedding.settings (
  id      INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  settingKey TEXT,
  settingValue TEXT
);