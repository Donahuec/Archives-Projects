DROP PROCEDURE IF EXISTS collection_update;

DELIMITER //

CREATE PROCEDURE collection_update(col_id INT)
BEGIN

/*
Here we are going to use our first cursor. Cursors are essentially MySQL's 
equivalent to loops. They loop through the table and grab a piece of information
from it, dropping it into a defined variable. Make sure you declare any variables
not related to the cursor before you start declaring the variables for the cursor.

Documentation:
http://www.mysqltutorial.org/mysql-stored-procedure-tutorial.aspx
*/


DECLARE done INT; -- variable that tells us when we hit the end of the table
DECLARE idfetch INT; --variable that we want to drop our cursor value into

#create the cursor that loops through all items in the select statement
DECLARE tbl_cursor CURSOR FOR SELECT ID FROM tblcollections_content WHERE CollectionID = col_id;
#When the Cursor does not find another value, set done to 1
DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;


OPEN tbl_cursor; --start the cursor
get_row:LOOP -- get a row from the loop
    FETCH tbl_cursor INTO idfetch; -- select it into idfetch
    IF done = 1 THEN LEAVE get_row; -- if there are not items left, leave loop
    END IF;
    
    CALL item_update(idfetch);

END LOOP get_row; -- end of loop contents
CLOSE tbl_cursor; -- make sure to close the cursor

#we are done with the collection, so delete Revision History
DROP TEMPORARY TABLE IF EXISTS RevisionHistory; 


END //

DELIMITER ;