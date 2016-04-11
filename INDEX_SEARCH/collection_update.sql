DROP PROCEDURE IF EXISTS collection_update;

DELIMITER //

CREATE PROCEDURE collection_update(col_id INT)
BEGIN


DECLARE done INT;
DECLARE idfetch INT;

DECLARE tbl_cursor CURSOR FOR SELECT ID FROM tblcollections_content WHERE CollectionID = col_id;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

OPEN tbl_cursor;
get_row:LOOP
    FETCH tbl_cursor INTO idfetch;
    IF done = 1 THEN LEAVE get_row;
    END IF;
    
    CALL item_update(idfetch);

END LOOP get_row;
CLOSE tbl_cursor;


DROP TEMPORARY TABLE IF EXISTS RevisionHistory;


END //

DELIMITER ;