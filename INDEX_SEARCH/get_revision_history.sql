DROP PROCEDURE IF EXISTS get_revision_history;

DELIMITER //

CREATE PROCEDURE get_revision_history(collectionNumber INT)
BEGIN
DROP TEMPORARY TABLE IF EXISTS RevisionHistory;
CREATE TEMPORARY TABLE RevisionHistory (id INT, item TEXT);

SELECT @revList := tblcollections_collections.RevisionHistory FROM tblcollections_collections WHERE ID LIKE collectionNumber;
SET @newString = "";
SET @idNum = 1;

WHILE @revList != "" DO 

    SET @newString = SUBSTRING_INDEX(@revList, ',', 1);
    SET @revList = SUBSTRING(REPLACE(@revList, @newString, "") FROM 2);
    INSERT INTO RevisionHistory(id, item) VALUES (@idNum, @newString);

    SET @idNum = @idNum + 1;

END WHILE;

END //

DELIMITER ;
