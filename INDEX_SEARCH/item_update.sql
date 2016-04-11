DROP PROCEDURE IF EXISTS item_update;

DELIMITER //

CREATE PROCEDURE item_update(item_id INT)
BEGIN

DECLARE usr_fld_content TEXT;
DROP TEMPORARY TABLE IF EXISTS UserFieldContent;
CREATE TEMPORARY TABLE UserFieldContent (title TEXT, item TEXT);
CALL rev_hist_cursor(item_id);
SET usr_fld_content = field_val_cursor();
DROP TEMPORARY TABLE IF EXISTS UserFieldContent;


IF NOT EXISTS (SELECT * FROM tblcollections_userfields WHERE ContentID = item_id AND Title LIKE "IndexField") THEN
    INSERT INTO tblcollections_userfields(ContentID, Title, Value, EADElementID) 
    VALUES (item_id, "IndexField", usr_fld_content, 15);
ELSE
    UPDATE tblcollections_userfields SET Value = usr_fld_content WHERE Title LIKE "IndexField" AND ContentID = item_id;
END IF;

END //

DELIMITER ;