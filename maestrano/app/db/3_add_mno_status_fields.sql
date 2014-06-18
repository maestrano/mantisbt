ALTER TABLE  `mantis_project_table` ADD  `mno_status` VARCHAR( 255 ) NOT NULL DEFAULT 'INPROGRESS';
ALTER TABLE  `mantis_project_user_list_table` ADD  `mno_status` VARCHAR( 255 ) NOT NULL DEFAULT 'ACTIVE';
ALTER TABLE  `mantis_bug_table` ADD  `mno_status` VARCHAR( 255 ) NOT NULL DEFAULT 'INPROGRESS';
ALTER TABLE  `mantis_bug_table` ADD  `mno_tasklist_id` VARCHAR( 255 ) DEFAULT NULL;
ALTER TABLE  `mantis_bug_text_table` ADD  `mno_status` VARCHAR( 255 ) NOT NULL DEFAULT  'ACTIVE';

--
-- CREATE mantis_bug_handler_table
--

CREATE TABLE IF NOT EXISTS `mantis_bug_handler_table` (
  `bug_id` int(10) NOT NULL,
  `handler_id` int(10) NOT NULL,
  `status` varchar(255) NOT NULL,
  UNIQUE KEY `unique_user_milestone` (`bug_id`,`handler_id`)
);

--
-- Triggers `mantis_bug_table`
--
DROP TRIGGER IF EXISTS `export_bug_handlers_delete`;
DELIMITER //
CREATE TRIGGER `export_bug_handlers_delete` AFTER DELETE ON `mantis_bug_table`
 FOR EACH ROW BEGIN
    IF EXISTS (SELECT * FROM mantis_bug_handler_table WHERE bug_id=OLD.id and handler_id=OLD.handler_id) AND (OLD.handler_id IS NOT NULL) THEN
	BEGIN
	UPDATE mantis_bug_handler_table SET status='INACTIVE' WHERE bug_id=OLD.id and handler_id=OLD.handler_id;
	END;
    ELSE
	BEGIN
	INSERT INTO mantis_bug_handler_table(bug_id, handler_id, status) VALUES (OLD.id, OLD.handler_id, 'INACTIVE');
	END;
    END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `export_bug_handlers_insert`;
DELIMITER //
CREATE TRIGGER `export_bug_handlers_insert` AFTER INSERT ON `mantis_bug_table`
 FOR EACH ROW BEGIN
    IF EXISTS (SELECT * FROM mantis_bug_handler_table WHERE bug_id=NEW.id and handler_id=NEW.handler_id) AND (NEW.handler_id IS NOT NULL) THEN 
	BEGIN
	UPDATE mantis_bug_handler_table SET status='ACTIVE' WHERE bug_id=NEW.id and handler_id=NEW.handler_id;
	END;
    ELSE
	BEGIN
	INSERT INTO mantis_bug_handler_table(bug_id, handler_id, status) VALUES (NEW.id, NEW.handler_id, 'ACTIVE');
	END;
    END IF;
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `export_bug_handlers_update`;
DELIMITER //
CREATE TRIGGER `export_bug_handlers_update` AFTER UPDATE ON `mantis_bug_table`
 FOR EACH ROW BEGIN
  IF NOT (NEW.handler_id <=> OLD.handler_id) THEN BEGIN
    IF EXISTS (SELECT * FROM mantis_bug_handler_table WHERE bug_id=OLD.id and handler_id=OLD.handler_id) AND (OLD.handler_id IS NOT NULL) THEN
	BEGIN
	UPDATE mantis_bug_handler_table SET status='INACTIVE' WHERE bug_id=OLD.id and handler_id=OLD.handler_id;
	END;
    ELSE
	BEGIN
	INSERT INTO mantis_bug_handler_table(bug_id, handler_id, status) VALUES (OLD.id, OLD.handler_id, 'INACTIVE');
	END;
    END IF;
    IF EXISTS (SELECT * FROM mantis_bug_handler_table WHERE bug_id=NEW.id and handler_id=NEW.handler_id) AND (NEW.handler_id IS NOT NULL) THEN 
	BEGIN
	UPDATE mantis_bug_handler_table SET status='ACTIVE' WHERE bug_id=NEW.id and handler_id=NEW.handler_id;
	END;
    ELSE
	BEGIN
	INSERT INTO mantis_bug_handler_table(bug_id, handler_id, status) VALUES (NEW.id, NEW.handler_id, 'ACTIVE');
	END;
    END IF;
  END;
  END IF;
END
//
DELIMITER ;
