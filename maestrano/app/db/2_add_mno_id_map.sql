CREATE TABLE IF NOT EXISTS `mno_id_map` (
  `mno_entity_guid` varchar(100) NOT NULL,
  `mno_entity_name` varchar(100) NOT NULL,
  `app_entity_id` varchar(100) NOT NULL,
  `app_entity_name` varchar(100) NOT NULL,
  `db_timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_flag` int(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `unique_mno_key` (`mno_entity_name`, `app_entity_id`, `app_entity_name`),
  UNIQUE KEY `unique_app_key` (`app_entity_name`, `mno_entity_guid`, `mno_entity_name`)
);