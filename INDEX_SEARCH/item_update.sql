DROP PROCEDURE IF EXISTS item_update;

DELIMITER //

CREATE PROCEDURE item_update(item_id INT)
BEGIN

DECLARE usr_fld_content TEXT;
/*
Create a temporary table that will hold all of the values
we collect for the item from the fields defined in revision history
*/
DROP TEMPORARY TABLE IF EXISTS UserFieldContent;
CREATE TEMPORARY TABLE UserFieldContent (title TEXT, item TEXT);

CALL rev_hist_cursor(item_id);

SET usr_fld_content = field_val_cursor();

#Make sure we drop the UserField table before processing the next item.
DROP TEMPORARY TABLE IF EXISTS UserFieldContent;

/*
If the UserField for IndexField does not exist for the item yet, create it.
Otherwise update the value of the existing IndexField
*/
IF NOT EXISTS (SELECT * FROM tblcollections_userfields WHERE ContentID = item_id AND Title LIKE "IndexField") THEN
    INSERT INTO tblcollections_userfields(ContentID, Title, Value, EADElementID) 
    VALUES (item_id, "IndexField", usr_fld_content, 15);
ELSE
    UPDATE tblcollections_userfields SET Value = usr_fld_content WHERE Title LIKE "IndexField" AND ContentID = item_id;
END IF;

END //

DELIMITER ;