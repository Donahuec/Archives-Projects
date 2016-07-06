DROP FUNCTION IF EXISTS field_val_cursor;

DELIMITER //

/*
This time we are using a function instead of a procedure. The difference
is that fuctions can have a return value

Documentation:
http://dev.mysql.com/doc/refman/5.7/en/create-procedure.html
*/
CREATE FUNCTION field_val_cursor() RETURNS TEXT

BEGIN
DECLARE retval TEXT DEFAULT "";
DECLARE cursdone INT;
#this time we are seecting into two variables
DECLARE titlefetch TEXT DEFAULT "";
DECLARE valfetch TEXT DEFAULT "";

DECLARE field_cursor CURSOR FOR SELECT title, item FROM UserFieldContent;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET cursdone = 1;

OPEN field_cursor;
get_field:LOOP
    FETCH next FROM field_cursor INTO titlefetch, valfetch;
    IF cursdone = 1 THEN LEAVE get_field;
    END IF;

    #make sure we format the index fields properl
    IF retval NOT LIKE "" AND valfetch NOT LIKE "" THEN 
        SET retval = CONCAT(retval, "; ", titlefetch, ": ", valfetch);
    ELSEIF valfetch NOT LIKE "" THEN
        SET retval = CONCAT(titlefetch, ": ", valfetch);
    END IF;



END LOOP get_field;
CLOSE field_cursor;

RETURN retval;

END //

DELIMITER ;