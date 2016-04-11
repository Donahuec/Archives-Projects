DROP PROCEDURE IF EXISTS collection_start;

DELIMITER //

CREATE PROCEDURE collection_start(col_id INT)
BEGIN
SET @count = 0;
CALL get_revision_history(col_id);

CALL collection_update(col_id);


END //

DELIMITER ;