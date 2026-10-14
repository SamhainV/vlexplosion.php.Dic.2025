-- Estructura vacía de la versión anterior a 005. SOLO para una base NUEVA.
-- Sin datos, cuentas, contraseñas ni contadores procedentes de una colección.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `AI_SAVE_KEYS_TBL` (
  `User_Id` int(11) NOT NULL,
  `Request_Key` char(64) NOT NULL,
  `Payload_Hash` char(64) NOT NULL,
  `Vinyl_Id` int(11) DEFAULT NULL,
  `Completed` tinyint(4) NOT NULL DEFAULT 0,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`User_Id`,`Request_Key`),
  KEY `Vinyl_Id` (`Vinyl_Id`),
  CONSTRAINT `AI_SAVE_KEYS_TBL_ibfk_1` FOREIGN KEY (`User_Id`) REFERENCES `Users_TBL` (`id`),
  CONSTRAINT `AI_SAVE_KEYS_TBL_ibfk_2` FOREIGN KEY (`Vinyl_Id`) REFERENCES `VINYLS_TBL` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `AUTHORS_TBL` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Author_Name` varchar(50) NOT NULL,
  `Debut_album_release` year(4) DEFAULT NULL,
  `Original_members` int(11) DEFAULT NULL,
  `Breakup_date` year(4) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `AUTOR_VINYLS_TBL` (
  `Autor_Id` int(11) NOT NULL,
  `Vinilo_Id` int(11) NOT NULL,
  PRIMARY KEY (`Autor_Id`,`Vinilo_Id`),
  KEY `fk_vinilo` (`Vinilo_Id`),
  CONSTRAINT `fk_autor` FOREIGN KEY (`Autor_Id`) REFERENCES `AUTHORS_TBL` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vinilo` FOREIGN KEY (`Vinilo_Id`) REFERENCES `VINYLS_TBL` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `CONDITION_TBL` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Condition_Name` varchar(30) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `EDITION_TBL` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Edition_Name` varchar(30) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_ALBUMS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `original_date` varchar(10) DEFAULT NULL,
  `release_type` varchar(80) DEFAULT NULL,
  `language` varchar(32) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `musicbrainz_group_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `discogs_master_id` bigint(20) unsigned DEFAULT NULL,
  `draft_owner_id` int(11) DEFAULT NULL,
  `legacy_vinyl_id` int(11) DEFAULT NULL,
  `imported_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `musicbrainz_group_id` (`musicbrainz_group_id`),
  UNIQUE KEY `discogs_master_id` (`discogs_master_id`),
  UNIQUE KEY `legacy_vinyl_id` (`legacy_vinyl_id`),
  KEY `album_title` (`title`),
  KEY `album_draft_owner` (`draft_owner_id`),
  CONSTRAINT `ENC_ALBUMS_ibfk_1` FOREIGN KEY (`draft_owner_id`) REFERENCES `Users_TBL` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`legacy_vinyl_id` is null or `draft_owner_id` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_ALBUM_ARTISTS` (
  `album_id` bigint(20) unsigned NOT NULL,
  `artist_id` bigint(20) unsigned NOT NULL,
  `position` smallint(5) unsigned NOT NULL,
  `credited_as` varchar(255) DEFAULT NULL,
  `role` varchar(80) NOT NULL DEFAULT 'artist',
  PRIMARY KEY (`album_id`,`position`),
  KEY `artist_albums` (`artist_id`,`album_id`),
  CONSTRAINT `ENC_ALBUM_ARTISTS_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `ENC_ALBUMS` (`id`),
  CONSTRAINT `ENC_ALBUM_ARTISTS_ibfk_2` FOREIGN KEY (`artist_id`) REFERENCES `ENC_ARTISTS` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_ALBUM_TERMS` (
  `album_id` bigint(20) unsigned NOT NULL,
  `term_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`album_id`,`term_id`),
  KEY `term_albums` (`term_id`,`album_id`),
  CONSTRAINT `ENC_ALBUM_TERMS_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `ENC_ALBUMS` (`id`),
  CONSTRAINT `ENC_ALBUM_TERMS_ibfk_2` FOREIGN KEY (`term_id`) REFERENCES `ENC_TERMS` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_ARTISTS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `kind` enum('unknown','person','group') NOT NULL DEFAULT 'unknown',
  `musicbrainz_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `discogs_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `musicbrainz_id` (`musicbrainz_id`),
  UNIQUE KEY `discogs_id` (`discogs_id`),
  KEY `artist_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_ASSERTIONS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` bigint(20) unsigned DEFAULT NULL,
  `edition_id` bigint(20) unsigned DEFAULT NULL,
  `field_name` varchar(80) NOT NULL,
  `value_json` longtext NOT NULL CHECK (json_valid(`value_json`)),
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `author_user_id` int(11) DEFAULT NULL,
  `status` enum('proposed','accepted','rejected','conflict') NOT NULL DEFAULT 'proposed',
  `manually_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `source_id` (`source_id`),
  KEY `author_user_id` (`author_user_id`),
  KEY `album_field_status` (`album_id`,`field_name`,`status`),
  KEY `edition_field_status` (`edition_id`,`field_name`,`status`),
  CONSTRAINT `ENC_ASSERTIONS_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `ENC_ALBUMS` (`id`),
  CONSTRAINT `ENC_ASSERTIONS_ibfk_2` FOREIGN KEY (`edition_id`) REFERENCES `ENC_EDITIONS` (`id`),
  CONSTRAINT `ENC_ASSERTIONS_ibfk_3` FOREIGN KEY (`source_id`) REFERENCES `ENC_SOURCES` (`id`),
  CONSTRAINT `ENC_ASSERTIONS_ibfk_4` FOREIGN KEY (`author_user_id`) REFERENCES `Users_TBL` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK ((`album_id` is not null) + (`edition_id` is not null) = 1),
  CONSTRAINT `CONSTRAINT_2` CHECK (`source_id` is not null or `author_user_id` is not null),
  CONSTRAINT `CONSTRAINT_3` CHECK (`manually_locked` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_COPIES` (
  `vinyl_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `album_id` bigint(20) unsigned NOT NULL,
  `edition_id` bigint(20) unsigned DEFAULT NULL,
  `sleeve_condition` varchar(80) DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `currency` char(3) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `seller` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rating` tinyint(3) unsigned DEFAULT NULL,
  `added_at` datetime DEFAULT NULL,
  `legacy_snapshot` longtext DEFAULT NULL CHECK (`legacy_snapshot` is null or json_valid(`legacy_snapshot`)),
  `overrides_json` longtext DEFAULT NULL CHECK (`overrides_json` is null or json_valid(`overrides_json`)),
  PRIMARY KEY (`vinyl_id`),
  KEY `vinyl_id` (`vinyl_id`,`owner_id`),
  KEY `album_id` (`album_id`),
  KEY `edition_id` (`edition_id`,`album_id`),
  KEY `owner_album` (`owner_id`,`album_id`),
  KEY `owner_edition` (`owner_id`,`edition_id`),
  CONSTRAINT `ENC_COPIES_ibfk_1` FOREIGN KEY (`vinyl_id`, `owner_id`) REFERENCES `VINYLS_TBL` (`Id`, `User_Id`) ON DELETE CASCADE,
  CONSTRAINT `ENC_COPIES_ibfk_2` FOREIGN KEY (`album_id`) REFERENCES `ENC_ALBUMS` (`id`),
  CONSTRAINT `ENC_COPIES_ibfk_3` FOREIGN KEY (`edition_id`, `album_id`) REFERENCES `ENC_EDITIONS` (`id`, `album_id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`purchase_price` is null or `purchase_price` >= 0),
  CONSTRAINT `CONSTRAINT_2` CHECK (`rating` is null or `rating` between 1 and 5),
  CONSTRAINT `CONSTRAINT_3` CHECK (`purchase_price` is null and `currency` is null or `purchase_price` is not null and `currency` is not null)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_CREDITS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` bigint(20) unsigned DEFAULT NULL,
  `edition_id` bigint(20) unsigned DEFAULT NULL,
  `work_id` bigint(20) unsigned DEFAULT NULL,
  `recording_id` bigint(20) unsigned DEFAULT NULL,
  `track_id` bigint(20) unsigned DEFAULT NULL,
  `artist_id` bigint(20) unsigned DEFAULT NULL,
  `organisation` varchar(255) DEFAULT NULL,
  `role` varchar(120) NOT NULL,
  `credited_as` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `credit_key` char(64) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_key` (`credit_key`),
  KEY `album_id` (`album_id`),
  KEY `edition_id` (`edition_id`),
  KEY `work_id` (`work_id`),
  KEY `recording_id` (`recording_id`),
  KEY `track_id` (`track_id`),
  KEY `source_id` (`source_id`),
  KEY `participant_role` (`artist_id`,`role`),
  CONSTRAINT `ENC_CREDITS_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `ENC_ALBUMS` (`id`),
  CONSTRAINT `ENC_CREDITS_ibfk_2` FOREIGN KEY (`edition_id`) REFERENCES `ENC_EDITIONS` (`id`),
  CONSTRAINT `ENC_CREDITS_ibfk_3` FOREIGN KEY (`work_id`) REFERENCES `ENC_WORKS` (`id`),
  CONSTRAINT `ENC_CREDITS_ibfk_4` FOREIGN KEY (`recording_id`) REFERENCES `ENC_RECORDINGS` (`id`),
  CONSTRAINT `ENC_CREDITS_ibfk_5` FOREIGN KEY (`track_id`) REFERENCES `ENC_TRACKS` (`id`),
  CONSTRAINT `ENC_CREDITS_ibfk_6` FOREIGN KEY (`artist_id`) REFERENCES `ENC_ARTISTS` (`id`),
  CONSTRAINT `ENC_CREDITS_ibfk_7` FOREIGN KEY (`source_id`) REFERENCES `ENC_SOURCES` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK ((`album_id` is not null) + (`edition_id` is not null) + (`work_id` is not null) + (`recording_id` is not null) + (`track_id` is not null) = 1),
  CONSTRAINT `CONSTRAINT_2` CHECK ((`artist_id` is not null) + (`organisation` is not null) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_EDITIONS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `album_id` bigint(20) unsigned NOT NULL,
  `country` varchar(80) DEFAULT NULL,
  `manufacture_country` varchar(80) DEFAULT NULL,
  `release_date` varchar(10) DEFAULT NULL,
  `barcode` varchar(64) DEFAULT NULL,
  `packaging` varchar(255) DEFAULT NULL,
  `edition_type` varchar(120) DEFAULT NULL,
  `remastered` tinyint(1) DEFAULT NULL,
  `pressing_plant` varchar(255) DEFAULT NULL,
  `musicbrainz_release_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `discogs_release_id` bigint(20) unsigned DEFAULT NULL,
  `imported_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `edition_album` (`id`,`album_id`),
  UNIQUE KEY `musicbrainz_release_id` (`musicbrainz_release_id`),
  UNIQUE KEY `discogs_release_id` (`discogs_release_id`),
  KEY `edition_album_date` (`album_id`,`release_date`),
  KEY `edition_barcode` (`barcode`),
  CONSTRAINT `ENC_EDITIONS_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `ENC_ALBUMS` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`remastered` is null or `remastered` in (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_EDITION_LABELS` (
  `edition_id` bigint(20) unsigned NOT NULL,
  `label_id` bigint(20) unsigned NOT NULL,
  `catalogue_number` varchar(120) NOT NULL DEFAULT '',
  PRIMARY KEY (`edition_id`,`label_id`,`catalogue_number`),
  KEY `catalogue_search` (`catalogue_number`),
  KEY `label_editions` (`label_id`,`edition_id`),
  CONSTRAINT `ENC_EDITION_LABELS_ibfk_1` FOREIGN KEY (`edition_id`) REFERENCES `ENC_EDITIONS` (`id`),
  CONSTRAINT `ENC_EDITION_LABELS_ibfk_2` FOREIGN KEY (`label_id`) REFERENCES `ENC_LABELS` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_LABELS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `musicbrainz_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `discogs_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `musicbrainz_id` (`musicbrainz_id`),
  UNIQUE KEY `discogs_id` (`discogs_id`),
  KEY `label_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_MEDIA` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `edition_id` bigint(20) unsigned NOT NULL,
  `position` smallint(5) unsigned NOT NULL,
  `format` varchar(80) DEFAULT NULL,
  `rpm` decimal(6,3) DEFAULT NULL,
  `diameter_inches` decimal(5,2) DEFAULT NULL,
  `color` varchar(120) DEFAULT NULL,
  `weight_grams` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medium_position` (`edition_id`,`position`),
  CONSTRAINT `ENC_MEDIA_ibfk_1` FOREIGN KEY (`edition_id`) REFERENCES `ENC_EDITIONS` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`position` > 0),
  CONSTRAINT `CONSTRAINT_2` CHECK (`rpm` is null or `rpm` > 0),
  CONSTRAINT `CONSTRAINT_3` CHECK (`diameter_inches` is null or `diameter_inches` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_PHOTOS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vinyl_id` int(11) NOT NULL,
  `storage_key` varchar(255) NOT NULL,
  `kind` enum('cover','back','label','barcode','runout','other') NOT NULL,
  `mime_type` varchar(32) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `storage_key` (`storage_key`),
  KEY `vinyl_id` (`vinyl_id`),
  CONSTRAINT `ENC_PHOTOS_ibfk_1` FOREIGN KEY (`vinyl_id`) REFERENCES `ENC_COPIES` (`vinyl_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_RECORDINGS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `duration_ms` int(10) unsigned DEFAULT NULL,
  `musicbrainz_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `musicbrainz_id` (`musicbrainz_id`),
  KEY `recording_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_RECORDING_WORKS` (
  `recording_id` bigint(20) unsigned NOT NULL,
  `work_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`recording_id`,`work_id`),
  KEY `work_recordings` (`work_id`,`recording_id`),
  CONSTRAINT `ENC_RECORDING_WORKS_ibfk_1` FOREIGN KEY (`recording_id`) REFERENCES `ENC_RECORDINGS` (`id`),
  CONSTRAINT `ENC_RECORDING_WORKS_ibfk_2` FOREIGN KEY (`work_id`) REFERENCES `ENC_WORKS` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_RUNOUTS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medium_id` bigint(20) unsigned NOT NULL,
  `side` varchar(16) DEFAULT NULL,
  `inscription` text NOT NULL,
  `variant` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medium_id` (`medium_id`),
  CONSTRAINT `ENC_RUNOUTS_ibfk_1` FOREIGN KEY (`medium_id`) REFERENCES `ENC_MEDIA` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_SOURCES` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(32) NOT NULL,
  `entity_type` varchar(32) NOT NULL,
  `external_id` varchar(100) NOT NULL,
  `source_url` varchar(1000) DEFAULT NULL,
  `fetched_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `license_note` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source_identity` (`provider`,`entity_type`,`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_TERMS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kind` enum('genre','style') NOT NULL,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `term_name` (`kind`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_TRACKS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `medium_id` bigint(20) unsigned NOT NULL,
  `position` smallint(5) unsigned NOT NULL,
  `printed_number` varchar(32) DEFAULT NULL,
  `side` varchar(16) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `duration_ms` int(10) unsigned DEFAULT NULL,
  `recording_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `track_position` (`medium_id`,`position`),
  KEY `recording_id` (`recording_id`),
  CONSTRAINT `ENC_TRACKS_ibfk_1` FOREIGN KEY (`medium_id`) REFERENCES `ENC_MEDIA` (`id`),
  CONSTRAINT `ENC_TRACKS_ibfk_2` FOREIGN KEY (`recording_id`) REFERENCES `ENC_RECORDINGS` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`position` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ENC_WORKS` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `musicbrainz_id` char(36) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `musicbrainz_id` (`musicbrainz_id`),
  KEY `work_title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `FORMAT_TBL` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Format_Name` varchar(30) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `GENRES_TBL` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Genre_Name` varchar(30) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `RECORD_LABEL_TBL` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Record_Label_Name` varchar(40) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Roles_TBL` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rol_name` (`rol_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Users_Roles` (
  `User_Id` int(11) NOT NULL,
  `Role_Id` int(11) NOT NULL,
  PRIMARY KEY (`User_Id`,`Role_Id`),
  KEY `fk_role_id` (`Role_Id`),
  CONSTRAINT `fk_role_id` FOREIGN KEY (`Role_Id`) REFERENCES `Roles_TBL` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_id` FOREIGN KEY (`User_Id`) REFERENCES `Users_TBL` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Users_TBL` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(254) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  UNIQUE KEY `unique_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `VINYLS_TBL` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Title` varchar(65) NOT NULL,
  `Genres_Id` int(11) DEFAULT NULL,
  `Format_Id` int(11) DEFAULT NULL,
  `Condition_Id` int(11) DEFAULT NULL,
  `Record_Label_Id` int(11) DEFAULT NULL,
  `Producer` varchar(50) DEFAULT NULL,
  `Release_date` year(4) DEFAULT NULL,
  `Edition_Id` int(11) DEFAULT NULL,
  `User_Id` int(11) NOT NULL,
  `Is_Favorite` tinyint(1) DEFAULT 0,
  `Is_Desired` tinyint(1) DEFAULT 0,
  `Image_Path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `enc_copy_owner` (`Id`,`User_Id`),
  KEY `fk_genero` (`Genres_Id`),
  KEY `fk_formato` (`Format_Id`),
  KEY `fk_condition` (`Condition_Id`),
  KEY `fk_record_label` (`Record_Label_Id`),
  KEY `fk_edition` (`Edition_Id`),
  KEY `fk_user` (`User_Id`),
  CONSTRAINT `fk_condition` FOREIGN KEY (`Condition_Id`) REFERENCES `CONDITION_TBL` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `fk_edition` FOREIGN KEY (`Edition_Id`) REFERENCES `EDITION_TBL` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `fk_formato` FOREIGN KEY (`Format_Id`) REFERENCES `FORMAT_TBL` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `fk_genero` FOREIGN KEY (`Genres_Id`) REFERENCES `GENRES_TBL` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `fk_record_label` FOREIGN KEY (`Record_Label_Id`) REFERENCES `RECORD_LABEL_TBL` (`Id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user` FOREIGN KEY (`User_Id`) REFERENCES `Users_TBL` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
