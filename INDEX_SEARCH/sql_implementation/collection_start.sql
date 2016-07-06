DROP PROCEDURE IF EXISTS collection_start; -- you want to drop the procedure if it exist before you try to create it. be careful with your naming of procedures

DELIMITER // -- set the delimiter to something else. Technically creating the procedure is one statement

/*
Stored Procedures are like functions (athough there are also stored functons, which you wil see later)
that are stored in the database (like a table). This allows you to call functions within them, as
well as use some other tools that an only be used in stored procedures. In our case, cursors are one of
these tools. Stored Procedures also let us save the code for future use. 

Documentation:
http://dev.mysql.com/doc/refman/5.7/en/create-procedure.html

Good Tutorial:
http://www.mysqltutorial.org/mysql-stored-procedure-tutorial.aspx
*/
CREATE PROCEDURE collection_start(col_id INT)
BEGIN -- you have to use BEGIN and END to mark what code is in your stored proc.
#variables starting with @ are session variables, and can be accessed for the
#entire query session. This way we can use the variable across multiple functions
SET @count = 0; --count will be used to keep track of what item we are on

CALL get_revision_history(col_id);

CALL collection_update(col_id);

END //

DELIMITER ;