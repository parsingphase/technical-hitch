<?php
/**
 * Created by PhpStorm.
 * User: wechsler
 * Date: 29/12/2015
 * Time: 20:36
 */

namespace Phase\Wedding;


use Doctrine\DBAL\Connection;

class DataStore
{
    const MESSAGE_IDENTIFIER_GUESTLIST = 'GUESTLIST';

    /**
     * @var Connection
     */
    protected $dbConn;

    /**
     * DataStore constructor.
     * @param Connection $dbConn
     */
    public function __construct(Connection $dbConn)
    {
        $this->dbConn = $dbConn;
    }


    /**
     * Returns array of assoc arrays each representing a contact. Main contact is first
     *
     * @param $uid int
     * @return array of arrays (id, email, name, rsvp)
     */
    public function getContactRecordsByUserId($uid)
    {
        $contacts = [];

        $contactsRaw = $this->dbConn->fetchAll(
            'SELECT cg.*, ms.menu, ms.starter, ms.main, ms.dessert 
              FROM `contacts_guest` cg
              LEFT OUTER JOIN `menu_selection` ms ON cg.id=ms.guestId
              WHERE `cg`.`userId`=:userId ORDER BY `isPrimary` DESC',
            ['userId' => $uid]
        );

        foreach ($contactsRaw as $contactRaw) {
            $contacts[] = [
                'id' => $contactRaw['id'],
                'name' => $contactRaw['contactName'],
                'email' => $contactRaw['contactEmail'],
                'rsvp' => $contactRaw['rsvp'],
                'requirements' => $contactRaw['requirements'],
                'isPrimary' => (bool)$contactRaw['isPrimary'],
                'menu' => [
                    'type' => $contactRaw['menu'],
                    'starter' => $contactRaw['starter'],
                    'main' => $contactRaw['main'],
                    'dessert' => $contactRaw['dessert'],
                ]
            ];
        }

        return $contacts;
    }


    /**
     * Store an array of assoc records representing a user; first in array is main contact
     *
     * @param array $contactsArray
     * @param int $userId
     * @param array $errors
     * @return array of arrays (id, email, name, rsvp)
     */
    public function storeContactsArrayForUser($contactsArray, $userId, &$errors)
    {
        $rawArray = $contactsArray;

        $errors = [];
        $mainContact = array_shift($contactsArray);
        $mainName = trim($mainContact['name']);
        $mainEmail = trim($mainContact['email']);
        $mainRsvp = $mainContact['rsvp'] ?: null;
        $mainRequirements = $mainContact['requirements'] ?: null;

        $uncheckedContacts = $contactsArray;
        // now remove any empty rows
        $contactsArray = [];
        foreach ($uncheckedContacts as $guest) {
            $guestName = trim($guest['name']);
            $guestEmail = trim($guest['email']);
            $guestRsvp = $guest['rsvp'] ?: null;
            $guestRequirements = $guest['requirements'] ?: null;
            if ($guestName || $guestEmail) {
                $contactsArray[] = [
                    'name' => $guestName,
                    'email' => $guestEmail,
                    'rsvp' => $guestRsvp,
                    'requirements' => $guestRequirements,
                    'userId' => $userId
                ];
            }
        }

        if ($mainName && $mainEmail) {
            $this->dbConn->insert('contacts_update', ['userId' => $userId, 'data' => json_encode($rawArray)]);

            // enough data to store
            // does a record exist already?
            $existing = $this->getContactRecordsByUserId($userId);
            if ($existing) {
                $mainContact = array_shift($existing);
                $primaryId = $mainContact['id'];
                $primaryRecord = [
                    'contactName' => $mainName,
                    'contactEmail' => $mainEmail,
                    'rsvp' => $mainRsvp,
                    'requirements' => $mainRequirements
                ];
                $this->dbConn->update('contacts_guest', $primaryRecord, ['id' => $primaryId]);

                // and subsidiary records
                foreach ($contactsArray as $i => $updatedGuest) {
                    if (isset($existing[$i])) {
                        // we have an existing row we can revise
                        $existingRecordId = $existing[$i]['id'];
                        $guestRecord = [
                            'contactName' => $updatedGuest['name'],
                            'contactEmail' => $updatedGuest['email'],
                            'rsvp' => $updatedGuest['rsvp'],
                            'requirements' => $updatedGuest['requirements'],
                        ];
                        $this->dbConn->update('contacts_guest', $guestRecord, ['id' => $existingRecordId]);
                    } else {
                        $guestRecord = [
                            'contactName' => $contactsArray[$i]['name'],
                            'contactEmail' => $contactsArray[$i]['email'],
                            'rsvp' => $contactsArray[$i]['rsvp'],
                            'requirements' => $contactsArray[$i]['requirements'],
                            'userId' => $userId,
                            'isPrimary' => 0
                            //'contactPartyId' => $primaryId
                        ];
                        $this->dbConn->insert('contacts_guest', $guestRecord);
                    }
                }

                // now delete any existing records we had left over
                if (count($existing) > count($contactsArray)) {
                    $remaining = count($existing) - count($contactsArray);
                    $toRemove = array_slice($existing, 0 - $remaining);
                    foreach ($toRemove as $row) {
                        $this->dbConn->delete('contacts_guest', ['id' => $row['id']]);
                    }
                }

                //throw new NotImplementedException('Updates not implemented yet');
            } else {
                $primaryRecord = [
                    'contactName' => $mainName,
                    'contactEmail' => $mainEmail,
                    'rsvp' => $mainRsvp,
                    'requirements' => $mainRequirements,
                    'userId' => $userId,
                    'isPrimary' => 1
                ];
                $inserted = $this->dbConn->insert('contacts_guest', $primaryRecord);
                //$primaryId = $this->dbConn->lastInsertId();
                $guestsStored = 0;
                if ($inserted) {
                    $guests = $contactsArray;
                    foreach ($guests as $guest) {
                        $this->dbConn->insert('contacts_guest',
                            [
                                //'contactPartyId' => $primaryId,
                                'contactName' => $guest['name'],
                                'contactEmail' => $guest['email'],
                                'requirements' => $guest['requirements'],
                                'rsvp' => $guest['rsvp'],
                                'userId' => $userId,
                                'isPrimary' => 0
                            ]);
                        $guestsStored++;
                    }
                    //$contacts = $this->getUserContactsJson($dataStore, $user);
                    //$result = ['party' => $partyId, 'guests' => $guestsStored];
                } else {
                    $errors[] = 'Failed to store party';
                }
            }
        } else {
            $errors[] = "Main contact name and email must be included";
        }

        return !$errors; //true if no errors
    }


    /**
     * @param $uid
     * @return array|null
     */
    public function getUserContactsPacked($uid)
    {
        $contacts = null;
        $contactsArray = $this->getContactRecordsByUserId($uid);
        // rewrap as mainContact, otherGuests
        if ($contactsArray) {
            $mainContact = array_shift($contactsArray);
            $contacts = ['mainContact' => $mainContact, 'otherGuests' => $contactsArray];
        }
        return $contacts;
    }

    public function notificationAlreadySent($userId, $email, $identifier)
    {
        $row = $this->dbConn->fetchAssoc(
            'SELECT * FROM notifications_sent
            WHERE (userId=:userId OR email=:email)
            AND identifier=:identifier',
            ['userId' => $userId, 'email' => $email, 'identifier' => $identifier]
        );
        return (bool)$row;
    }

    public function markNotificationSent($userId, $email, $identifier)
    {
        $this->dbConn->insert(
            'notifications_sent',
            [
                'userId' => $userId,
                'email' => $email,
                'identifier' => $identifier
            ]
        );
    }

    public function getEmailAddressList()
    {
        /** @noinspection SqlResolve */
        $sql = 'SELECT * FROM (
                  SELECT IFNULL(contactName,username) AS contactName,
                  IFNULL(contactEmail,email) AS contactEmail,
                  rsvp,"primary" AS position FROM contacts_guest
                  RIGHT OUTER JOIN fos_user ON fos_user.id = contacts_guest.userId
                  AND contacts_guest.`isPrimary`=1
                    UNION
                  SELECT contactName,contactEmail,rsvp,"secondary" AS position FROM contacts_guest
                    WHERE contactEmail IS NOT NULL AND LENGTH(TRIM(contactEmail))>0
                    AND contacts_guest.`isPrimary`=0
                    ) AS contacts
                GROUP BY contactEmail';

        $users = $this->dbConn->fetchAll($sql);

        return $users;
    }

    public function getEmailTemplates()
    {
        $sql = 'SELECT * FROM notification_emails ORDER BY ID ASC';
        $users = $this->dbConn->fetchAll($sql);

        return $users;
    }

    public function storeMailerTemplate($message)
    {
        //var_dump($message); die();
        if ($message['id'] === 'new') {
            $data = $message;
            unset($data['id']);
            $this->dbConn->insert('notification_emails', $data);
            $newId = $this->dbConn->lastInsertId();
            $stored = $this->dbConn->fetchAssoc('SELECT * FROM notification_emails WHERE id=:id', ['id' => $newId]);
        } else {
            $data = $message;
            $ident = ['id' => $data['id']];
            unset($data['id']);
            $this->dbConn->update('notification_emails', $data, $ident);
            $stored = $this->dbConn->fetchAssoc('SELECT * FROM notification_emails WHERE id=:id', $ident);
        }
        return $stored;
    }

    public function getEmailTemplateById($templateId)
    {
        return $this->dbConn->fetchAssoc('SELECT * FROM notification_emails WHERE id=:id', ['id' => $templateId]);
    }

    public function getMenuChoicesByUserId($userId)
    {
        $sql = 'SELECT ms.* FROM menu_selection ms WHERE ms.userId=:uid';
        $rows = $this->dbConn->fetchAll($sql, ['uid' => $userId]);

        if (!$rows) {
            // see if we can create a set of data - we should have a primary guest but don't have choices yet
            $sql = 'SELECT id FROM contacts_guest WHERE userId=:uid AND `isPrimary`=1';
            $guestId = $this->dbConn->fetchColumn($sql, ['uid' => $userId]);
            if ($guestId) {
                $rows = [
                    [
                        'id' => null,
                        'guestId' => $guestId,
                        'menu' => 'Adult',
                        'starter' => null,
                        'main' => null,
                        'dessert' => null,
                        'preferences' => null,
                    ]
                ];
            } else {
                $rows = [];
            }
        }

        return $rows;
    }

    public function storeMenuChoicesForUser($choices, $userId)
    {
        $this->dbConn->insert('menu_selection_update', ['userId' => $userId, 'data' => json_encode($choices)]);

        foreach ($choices as $guestId => $choice) {
            // ensure this guest is allocated to this user
            $guestUserId = $this->dbConn->fetchColumn(
                'SELECT id FROM contacts_guest WHERE userId=:uid AND id=:guestId',
                ['uid' => $userId, 'guestId' => $guestId]
            );
            if (!$guestUserId) {
                return false;
            }

            //safe to save
            // see if there's an existing choice for this user
            $exists = $this->dbConn->fetchAssoc(
                'SELECT * FROM menu_selection WHERE guestId=:guestId',
                ['guestId' => $guestId]
            );
            if ($exists) {
                $this->dbConn->update(
                    'menu_selection',
                    $choice,
                    ['guestId' => $guestId]
                );
            } else {
                $choice['userId'] = $userId;
                $choice['guestId'] = $guestId;
                $this->dbConn->insert('menu_selection', $choice);
            }

        }
        return true;
    }

    public function getAllMenuChoices()
    {
        return $this->dbConn->fetchAll(
            'SELECT cg.contactName, cg.rsvp, ms.* FROM menu_selection ms INNER JOIN contacts_guest cg ON cg.id = ms.guestId'
        );
    }

    public function getSetting($key)
    {
        $conn = $this->dbConn;
        $value = $conn->fetchColumn('SELECT settingValue FROM settings WHERE settingKey=:key', ['key' => $key]);
        return $value;
    }

    public function settingExists($key)
    {
        $conn = $this->dbConn;
        $value = $conn->fetchColumn('SELECT 1 FROM settings WHERE settingKey=:key', ['key' => $key]);
        return (bool)$value;
    }

    public function getSettings($asArray = false)
    {
        $conn = $this->dbConn;
        $values = $conn->fetchAll('SELECT * FROM settings');
        $indexed = [];
        foreach ($values as $value) {
            $indexed[$value['settingKey']] = $asArray ? $value : $value['settingValue'];
        }
        return $indexed;
    }

    public function updateSettingValues($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException();
        }
        foreach ($data as $k => $v) {
            $this->updateSettingValue($k, $v);
        }
    }

    public function updateSettingValue($k, $v)
    {
        $conn = $this->dbConn;
        if ($this->settingExists($k)) {
            $conn->update('settings', ['settingValue' => $v], ['settingKey' => $k]);
        } else {
            $conn->insert('settings', ['settingValue' => $v, 'settingKey' => $k]);
        }
    }

    public function getParties()
    {
        $parties = [];
        $block = [];
        $conn = $this->dbConn;
        $sql = 'SELECT u.id AS userId, g.id AS guestId, g.contactName, m.menu, p.blockId
                  FROM fos_user u
                  INNER JOIN contacts_guest g ON u.id = g.userId
                  LEFT OUTER JOIN menu_selection m ON m.guestId = g.id
                  LEFT OUTER JOIN party_seating p ON u.id=p.userId
                  WHERE g.rsvp = \'ATTENDING\'
                  ORDER BY u.id, g.isPrimary DESC, g.contactName';

        $rows = $conn->fetchAll($sql);
        foreach ($rows as $row) {
            $row['userId'] = (int)$row['userId'];
            $partyId = $row['userId'];
            if (!isset($parties[$partyId])) {
                $parties[$partyId] = [];
            }

            if ($row['blockId']) {
                $row['blockId'] = (int)$row['blockId'];
            } else {
                $row['blockId'] = null;
            }

            $row['guestId'] = (int)$row['guestId'];
            $parties[$partyId][] = $row;
            $block[$partyId] = $row['blockId'];
        }

        $packed = [];

        foreach ($parties as $userId => $party) {
            $packed[] = [
                'userId' => $userId,
                'blockId' => $block[$userId],
                'guests' => $party
            ];
        }

        return $packed;
    }

    public function getSeatingGrid($withWorkingName = false)
    {
        $grid = [];
        $conn = $this->dbConn;

        $sql = 'SELECT * FROM `seating_block` ORDER BY gridY,gridX';
        $rows = $conn->fetchAll($sql);
        foreach ($rows as $row) {
            $x = (int)$row['gridX'];
            $y = (int)$row['gridY'];
            if (!array_key_exists($y, $grid)) {
                $grid[$y] = [];
            }
            $block = [
                'id' => (int)$row['id'],
                'themeName' => $row['themeName'],
                'gridX' => $x,
                'gridY' => $y,
                'enabled' => (bool)$row['enabled']
            ];
            if ($withWorkingName) {
                $block['workingName'] = $row['workingName'];
            }

            $grid[$y][$x] = $block;
        }

        //0-index for JS loops to work
        $grid = array_values($grid);
        foreach ($grid as $k => $row) {
            $grid[$k] = array_values($row);
        }

        return $grid;
    }

    public function savePartySeating($partySeating)
    {
        $conn = $this->dbConn;

        $conn->exec('DELETE FROM `party_seating`');
        foreach ($partySeating as $userId => $blockId) {
            $conn->insert('party_seating', ['userId' => $userId, 'blockId' => $blockId]);
        }
    }

    public function saveSeatingLayout($layout)
    {
        $conn = $this->dbConn;
        $safeKeys = ['workingName', 'themeName', 'gridX', 'gridY', 'enabled'];

        foreach ($layout as $table) {
            $cleanTable = [];
            foreach ($table as $k => $v) {
                if (in_array($k, $safeKeys)) {
                    $cleanTable[$k] = $v;
                }
            }

            $cleanTable['enabled'] = $cleanTable['enabled'] && ($cleanTable['enabled'] !== 'false') ? 1 : 0;
            //throw new \Exception(json_encode($cleanTable));

            $conn->update('seating_block', $cleanTable, ['id' => $table['id']]);
        }
    }

    public function fetchGuestData($guestId)
    {
        $sql = 'SELECT
                  u.username,
                  u.id AS userId,
                  cg.contactName,
                  cg.contactEmail,
                  cg.id as guestId,
                  gp.twitter,
                  gp.facebook,
                  gp.photoUrl,
                  gp.otherLinkName,
                  gp.otherLinkUrl,
                  gp.allowMessages
                FROM
                  fos_user u
                  INNER JOIN contacts_guest cg ON u.id = cg.userId
                  LEFT OUTER JOIN guest_profile gp ON cg.id = gp.guestId
                WHERE cg.id = :guestId';

        return $this->dbConn->fetchAssoc($sql, ['guestId' => $guestId]);
    }

    public function fetchAllContactsNaturalOrder()
    {
        $sql = "SELECT
                  u.username,
                  u.id AS userId,
                  cg.id AS profileId,
                  cg.contactName,
                  cg.contactEmail,
                  gp.*
                FROM
                  fos_user u
                  INNER JOIN contacts_guest cg ON u.id = cg.userId
                  LEFT OUTER JOIN guest_profile gp ON cg.id = gp.guestId
                WHERE cg.rsvp='ATTENDING'";

        $guests = $this->dbConn->fetchAll($sql);

        usort(
            $guests,
            function ($profileA, $profileB) {
                //surnames, forename
                $nameA = preg_replace('/^(\S+)\s(.*)/', '$2,$1', $profileA['contactName']);
                $nameB = preg_replace('/^(\S+)\s(.*)/', '$2,$1', $profileB['contactName']);
                return strcasecmp($nameA, $nameB);
            }
        );

        return $guests;
    }

    public function storeGuestProfile($profile)
    {
        $profileKeys = ['allowMessages', 'facebook', 'twitter', 'photoUrl', 'otherLinkUrl', 'otherLinkName'];
        $guestId = (int)$profile['guestId'];

        $storable = [];
        foreach ($profile as $k => $v) {
            if (in_array($k, $profileKeys)) {
                $storable[$k] = $v;
            }
        }

        $storable['allowMessages'] = 0;

        $profileExists = $this->guestProfileExists($guestId);

        if ($profileExists) {
            $this->dbConn->update('guest_profile', $storable, ['guestId' => $guestId]);
        } else {
            $storable['guestId'] = $guestId;
            $this->dbConn->insert('guest_profile', $storable);
        }
    }

    /**
     * @param $guestId
     * @return bool
     */
    public function guestProfileExists($guestId)
    {
        $sql = 'SELECT 1 FROM guest_profile WHERE guestId=:guestId';
        $profileExists = (bool)$this->dbConn->fetchColumn($sql, ['guestId' => $guestId]);
        return $profileExists;
    }

}