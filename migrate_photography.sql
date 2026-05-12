-- ============================================================
-- Migration: photography table content -> images
-- MySQL 5.7 compatible
-- Run step by step, confirm each step before proceeding
-- ============================================================

-- Step 1: Add `images` column
ALTER TABLE `photography`
  ADD COLUMN `images` LONGTEXT COMMENT 'Album images JSON' AFTER `cover`;

-- Step 2: Migrate data from `content` HTML to `images` JSON
-- MySQL 5.7 stored procedure to extract <img src> from HTML
DELIMITER $$

DROP PROCEDURE IF EXISTS `migrate_photography_images`$$

CREATE PROCEDURE `migrate_photography_images`()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_id INT;
  DECLARE v_content LONGTEXT;
  DECLARE v_images JSON DEFAULT JSON_ARRAY();
  DECLARE v_remaining LONGTEXT;
  DECLARE v_pos INT;
  DECLARE v_url VARCHAR(500);

  DECLARE cur CURSOR FOR SELECT `id`, `content` FROM `photography` WHERE `content` IS NOT NULL AND `content` != '';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;

  read_loop: LOOP
    FETCH cur INTO v_id, v_content;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SET v_images = JSON_ARRAY();
    SET v_remaining = v_content;

    -- Extract each <img src="...">
    extract_loop: LOOP
      SET v_pos = LOCATE('<img', v_remaining);
      IF v_pos = 0 THEN
        LEAVE extract_loop;
      END IF;

      SET v_remaining = SUBSTRING(v_remaining, v_pos);

      -- Find src="
      SET v_pos = LOCATE('src="', v_remaining);
      IF v_pos = 0 THEN
        LEAVE extract_loop;
      END IF;

      SET v_remaining = SUBSTRING(v_remaining, v_pos + 5);

      -- Find closing "
      SET v_pos = LOCATE('"', v_remaining);
      IF v_pos = 0 THEN
        LEAVE extract_loop;
      END IF;

      SET v_url = SUBSTRING(v_remaining, 1, v_pos - 1);

      -- Convert ../uploads/ to /uploads/
      SET v_url = REPLACE(v_url, '../uploads/', '/uploads/');

      SET v_images = JSON_ARRAY_APPEND(v_images, '$', v_url);

      SET v_remaining = SUBSTRING(v_remaining, v_pos + 1);
    END LOOP;

    -- Update row
    IF JSON_LENGTH(v_images) > 0 THEN
      UPDATE `photography` SET `images` = JSON_UNQUOTE(JSON_EXTRACT(v_images, '$')) WHERE `id` = v_id;
    END IF;

  END LOOP;

  CLOSE cur;
  SELECT 'Migration done' AS result;
END$$

DELIMITER ;

-- Run the migration
CALL `migrate_photography_images`();

-- Verify results
SELECT `id`, `title`, `images` FROM `photography`;

-- Step 3: After confirming data is correct, drop `content` column
-- ALTER TABLE `photography` DROP COLUMN `content`;

-- Step 4: Clean up procedure
DROP PROCEDURE IF EXISTS `migrate_photography_images`;
