DROP PROCEDURE IF EXISTS get_revision_history;

DELIMITER //

CREATE PROCEDURE get_revision_history(collectionNumber INT)
BEGIN
/*
Create a temporary table that holds the information from revision history. 
A temporary table is a table that does not actually get stored in/ modify the database.
It gets deleted at the end of the session (but it is better to drop it by hand.)

Documentation:
http://dev.mysql.com/doc/refman/5.7/en/create-table.html

Tutorial:
http://www.mysqltutorial.org/mysql-temporary-table/
*/
DROP TEMPORARY TABLE IF EXISTS RevisionHistory;
CREATE TEMPORARY TABLE RevisionHistory (id INT, item TEXT);

#select statement to set session variable. Make sure it can only return one val.
SELECT @revList := tblcollections_collections.RevisionHistory FROM tblcollections_collections WHERE ID LIKE collectionNumber;
SET @newString = ""; -- string to hold each value of revhist as it is put in the table
SET @idNum = 1; 

WHILE @revList != "" DO --> while there are still items in the revlist

	#revlist is separated by commas, get everything up to first comma
    SET @newString = SUBSTRING_INDEX(@revList, ',', 1);
    #remove everything before first comma in revlist 
    SET @revList = SUBSTRING(REPLACE(@revList, @newString, "") FROM 2);
    #insert the new string into revision history table
    INSERT INTO RevisionHistory(id, item) VALUES (@idNum, @newString);

    SET @idNum = @idNum + 1;

END WHILE;

END //

DELIMITER ;
