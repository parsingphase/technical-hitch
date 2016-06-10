CREATE TABLE IF NOT EXISTS `fos_user` (
  `id`                    INT(11)                 NOT NULL AUTO_INCREMENT,
  `username`              VARCHAR(255)
                          COLLATE utf8_unicode_ci NOT NULL,
  `username_canonical`    VARCHAR(255)
                          COLLATE utf8_unicode_ci NOT NULL,
  `email`                 VARCHAR(255)
                          COLLATE utf8_unicode_ci NOT NULL,
  `email_canonical`       VARCHAR(255)
                          COLLATE utf8_unicode_ci NOT NULL,
  `enabled`               TINYINT(1)              NOT NULL,
  `salt`                  VARCHAR(255)
                          COLLATE utf8_unicode_ci NOT NULL,
  `password`              VARCHAR(255)
                          COLLATE utf8_unicode_ci NOT NULL,
  `last_login`            DATETIME                         DEFAULT NULL,
  `locked`                TINYINT(1)              NOT NULL,
  `expired`               TINYINT(1)              NOT NULL,
  `expires_at`            DATETIME                         DEFAULT NULL,
  `confirmation_token`    VARCHAR(255)
                          COLLATE utf8_unicode_ci          DEFAULT NULL,
  `password_requested_at` DATETIME                         DEFAULT NULL,
  `roles`                 LONGTEXT
                          COLLATE utf8_unicode_ci NOT NULL
  COMMENT '(DC2Type:array)',
  `credentials_expired`   TINYINT(1)              NOT NULL,
  `credentials_expire_at` DATETIME                         DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_957A647992FC23A8` (`username_canonical`),
  UNIQUE KEY `UNIQ_957A6479A0D96FBF` (`email_canonical`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE `contacts_guest` (
  `id`           INT(11)    NOT NULL AUTO_INCREMENT,
  `userId`       INT(11)             DEFAULT NULL,
  `isPrimary`      TINYINT(4) NOT NULL DEFAULT '0',
  `contactName`  TEXT
                 COLLATE utf8_bin,
  `contactEmail` TEXT
                 COLLATE utf8_bin,
  `rsvp`         ENUM('ATTENDING', 'NOT ATTENDING')
                 COLLATE utf8_bin    DEFAULT NULL,
  `requirements` TEXT
                 COLLATE utf8_bin,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8
  COLLATE = utf8_bin;

CREATE TABLE `contacts_update` (
  `id`      INT(11)   NOT NULL AUTO_INCREMENT,
  `userId`  INT(11)   NOT NULL,
  `data`    TEXT
            COLLATE utf8_unicode_ci,
  `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


CREATE TABLE `notifications_sent` (
  `id`         INT(11)   NOT NULL AUTO_INCREMENT,
  `userId`     INT(11)   NULL,
  `email`      TEXT,
  `identifier` TEXT
               COLLATE utf8_unicode_ci,
  `sent`       TIMESTAMP NOT NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


CREATE TABLE `notification_emails` (
  `id`         INT(11)   NOT NULL AUTO_INCREMENT,
  `identifier` TEXT
               COLLATE utf8_unicode_ci,
  `subject`    TEXT
               COLLATE utf8_unicode_ci,
  `body`       TEXT
               COLLATE utf8_unicode_ci,
  `sent`       TIMESTAMP NULL     DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE `menu_selection` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `guestId`     INT(11) NOT NULL,
  `userId`      INT(11) NOT NULL,
  `menu`        TEXT
                COLLATE utf8_bin,
  `starter`     TEXT
                COLLATE utf8_bin,
  `main`        TEXT
                COLLATE utf8_bin,
  `dessert`     TEXT
                COLLATE utf8_bin,
  `preferences` TEXT
                COLLATE utf8_bin,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  AUTO_INCREMENT = 1
  DEFAULT CHARSET = utf8
  COLLATE = utf8_bin;

CREATE TABLE `menu_selection_update` (
  `id`      INT(11)   NOT NULL AUTO_INCREMENT,
  `userId`  INT(11)   NOT NULL,
  `data`    TEXT
            COLLATE utf8_unicode_ci,
  `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE settings (
  id      INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  settingKey TEXT,
  settingValue TEXT
);