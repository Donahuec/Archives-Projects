DROP PROCEDURE IF EXISTS rev_hist_cursor;

DELIMITER //

CREATE PROCEDURE rev_hist_cursor(item_id INT)

BEGIN

DECLARE revdone INT;
DECLARE valfetch VARCHAR(50);


DECLARE rev_cursor CURSOR FOR SELECT item FROM RevisionHistory;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET revdone = 1;
SET @temp = "";

#loop through the Revision History table to get the required tables andd query them
OPEN rev_cursor;
get_rev:LOOP
    FETCH rev_cursor INTO valfetch;
    IF revdone = 1 THEN LEAVE get_rev;
    END IF;


    IF valfetch LIKE "tblCollections_UserFields%" THEN
        SET valfetch = REPLACE(valfetch, "tblCollections_UserFields.Title=", "");
        SET valfetch = REPLACE(valfetch, "tblCollections_UserFields.title=", "");
        INSERT INTO UserFieldContent (title, item) SELECT Title, Value FROM tblCollections_UserFields 
        WHERE Title LIKE valfetch AND ContentID = item_id;

    ELSE
        SET valfetch = REPLACE(valfetch, "tblCollections_Content.", "");
        
        /*
        Here we need to run a query using a variable as the table field. To do 
        this we use a Prepare and Execute Statement. This allows us to create a 
        string that is our query, and then run it.

        Documentation:
        http://dev.mysql.com/doc/refman/5.7/en/sql-syntax-prepared-statements.html
        
        Tutorial:
        http://www.mysqltutorial.org/mysql-prepared-statement.aspx
        */
        SET @sql = CONCAT("SELECT `", valfetch, "` INTO @temp FROM `tblCollections_Content` WHERE ID = ", item_id, ";");
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;

        INSERT INTO UserFieldContent (title, item) VALUES (valfetch, @temp);
    END IF;

END LOOP get_rev;
CLOSE rev_cursor;

END //

DELIMITER ;