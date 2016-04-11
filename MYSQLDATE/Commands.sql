#This clears the new date column
UPDATE tblcollections_content SET NewDate = "" , Type = 0;

#This checks for some of the known- valid structures, and automatially moves them to NewDate
UPDATE tblcollections_content SET NewDate = Date , Type = 1 WHERE Date REGEXP 
'^[[:digit:]]{4}$|^[[:digit:]]{4}-[[:digit:]]{4}$|^circa [[:digit:]]{4}$|^circa [[:digit:]]{4}-circa [[:digit:]]{4}$|^[[:digit:]]{4}/[[:digit:]]{2}$|^[[:digit:]]{4}/[[:digit:]]{4}$|^before [[:digit:]]{4}$|^after [[:digit:]]{4}$|^([[:digit:]]{4}(-[[:digit:]]{4})?;)* ?[[:digit:]]{4}(-[[:digit:]]{4})?;?$|^circa [[:digit:]]{4}-[[:digit:]]{4}$|^[[:digit:]]{4} [[.(.]][[.+.]] or - [[:digit:]]{1} years[[.).]]$|^[[:digit:]]{4} [[.(.]][[.+.]] or - [[:digit:]]{1} year[[.).]]$|^[[:digit:]]{4}/[[:digit:]]{2}-[[:digit:]]{4}/[[:digit:]]{2}$|^([[:digit:]]{4}; )+[[:digit:]]{4};?$|^[[:digit:]]{4}-$|^(([[:digit:]]{4};[[. .]])|([[:digit:]]{4}-[[:digit:]]{4}[[. .]]))*[[:digit:]]{4};?$|^((([[:digit:]]{4})|([[:digit:]]{4}-[[:digit:]]{4}));[[. .]]?)+(([[:digit:]]{4})|([[:digit:]]{4}-[[:digit:]]{4}))$';

UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(REPLACE(NewDate, "Before", "before"), "After", "after"), "Circa", "circa");

#----------------------------------circa-----------------------------------------

#Checks for various instances that require circa, and Removes excess letters and Concatenates circa to the beginning
UPDATE tblcollections_content SET NewDate = CONCAT("circa ", 
REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(Date, "?", ""), "(prob.)", ""), "- ", "-"),
 ", ", "-"), "c. ", ""),"c.", ""), "circa", ""),"ca.", "")) , Type = 2 WHERE Date LIKE "%c%" and Date NOT LIKE "%circa%" AND Date NOT LIKE "%touching%"
  AND Date NOT LIKE "%ct%" AND Date NOT LIKE "%ec%";

  #Attaching circa and removing (prob.)
UPDATE tblcollections_content SET NewDate = CONCAT("circa ", REPLACE(Date, ' (prob.)', '')) 
, Type = 2
WHERE Date LIKE '%(prob%' AND NewDate LIKE "";

#More Adding of circas
UPDATE tblcollections_content SET NewDate = CONCAT("circa ", REPLACE(REPLACE(REPLACE(
    Date, "(?)", ""), "?", ""), "likely ", "")) 
, Type = 2
WHERE NewDate LIKE "" AND Date NOT LIKE "%,%" AND Date NOT LIKE "%a%"AND 
(Date LIKE "%?%" OR Date LIKE "%likely%");

UPDATE tblcollections_content SET NewDate = CONCAT("circa ", CONCAT(SUBSTRING(Date, 4,4), CONCAT("-", CONCAT(SUBSTRING(Date, 4, 3), "9")))) 
WHERE Date REGEXP '^(c. )[[:digit:]]{4}s[[.?.]]?$';

UPDATE tblcollections_content SET NewDate = CONCAT("circa ", CONCAT(SUBSTRING(NewDate,7,4), CONCAT("-", CONCAT(SUBSTRING(NewDate, 7,3), "9"))))
WHERE NewDate REGEXP '^circa [[:digit:]]{4}s[[.?.]]?$';

#-------------------------------------Non-circa Approx-----------------------------------------

UPDATE tblcollections_content SET NewDate =  CONCAT( SUBSTRING(Date, 1,4), CONCAT("-", CONCAT(SUBSTRING(Date, 1, 3), "9")))
, Type = 3 
WHERE Date REGEXP '^[[:digit:]]{4}s[[.?.]]?$';

#Concatenates 'After' to any item starting with 'post'
UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(Date, "post-", "after "), "post ", "after ") 
, Type = 3
WHERE Date LIKE "%post-%" OR Date LIKE "%post %";

#Concatenates 'Before' to any item containing 'pre'
UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(Date, "pre-", "before "), "pre ", "before ") 
, Type = 3
WHERE Date LIKE "%pre-%" OR Date LIKE "%pre %";

#sets anything preceded by 'to-present' to "-"
UPDATE tblcollections_content SET NewDate = REPLACE(Date, " to present", "-") 
, Type = 3 WHERE Date LIKE "%to present%";


#------------------------------------(+ or - x year(s))----------------------------------------


#Correcting Date Formats
UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(Date, "I", ""),"( +", "(+") 
    , Type = 4
    WHERE NewDate LIKE "" AND Date NOT LIKE "" AND Date LIKE "%+ or -%";

#Correcting Date Formats
UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(REPLACE(NewDate, "1)", "1 year)"), "yr.", "year"), "- year", "- 1 year") 
, Type = 4
WHERE  Date LIKE "%+ or -%";

#Correcting Date Formats
UPDATE tblcollections_content SET NewDate = REPLACE(Date, "(+ or 1", "(+ or - 1") 
    , Type = 4
    WHERE NewDate LIKE "" AND Date LIKE "(+ or 1";

#Fix case where - is left out of + or -
UPDATE tblcollections_content SET NewDate = REPLACE(Date, "or 1", "or - 1") 
, Type = 4
WHERE Date LIKE "%+ or 1%";


#-----------------------------------Extract Year from dates--------------------------------


#Extract year from dates
UPDATE tblcollections_content SET NewDate = SUBSTRING(Date, 1, 4) 
, Type = 5 WHERE Date REGEXP '^[[:digit:]]{4} ?- ?[[:alpha:]]+$';

#Extract year from dates, extract part two if the second part is bigger
UPDATE tblcollections_content SET NewDate = CONCAT(SUBSTRING(Date, 1, 5),CONCAT(SUBSTRING(Date, 1, 2), SUBSTRING(Date, 6, 7))) 
, Type = 5
WHERE Date REGEXP '^[[:digit:]]{4}-[[:digit:]]{2}$' AND SUBSTRING(Date, 6, 7) > SUBSTRING(Date, 3, 4);

#Extract Year from Dates
UPDATE tblcollections_content SET NewDate = SUBSTRING(Date, 1, 4)
, Type = 5 
WHERE Date REGEXP '^[[:digit:]]{4}-[[:digit:]]{1,2}-[[:digit:]]{1,2}$';

#Extract Year from Dates
UPDATE tblcollections_content SET NewDate = SUBSTRING(Date, 1, 4) 
, Type = 5
WHERE Date REGEXP '^[[:digit:]]{4}-[[:digit:]]{2}$' AND NewDate LIKE "";

#Extract year from Date
UPDATE tblcollections_content SET NewDate = RIGHT(Date, 4) 
, Type = 5
WHERE Date REGEXP '^[[:digit:]]{0,2}[[. .]]?[[:alpha:]]+ [[:digit:]]{4}$' AND NewDate LIKE "";

#Extract Year from date
UPDATE tblcollections_content SET NewDate = LEFT(Date, 4) 
, Type = 5
WHERE Date  REGEXP '^[[:digit:]]{4}s?, [[:alpha:]]+' AND NewDate LIKE "";

#Extract year from date
UPDATE tblcollections_content SET NewDate = RIGHT(Date, 4) 
, Type = 5
WHERE Date REGEXP '^[[:digit:]]{1,2} [[:alpha:]]+.? [[:digit:]]{4}$' AND NewDate LIKE "";

#Extract year from Date
UPDATE tblcollections_content SET NewDate = RIGHT(Date, 4) 
, Type = 5
WHERE Date REGEXP '^[[:digit:]]{1,2}-[[:digit:]]{1,2} [[:alpha:]]+ [[:digit:]]{4}$' AND NewDate LIKE "";


#-----------------------------Correcting Semicolons--------------------------------------------------------


#Correcting to semicolons
UPDATE tblcollections_content SET NewDate = REPLACE(Date, ",", ";") 
, Type = 6
WHERE NewDate LIKE "" AND Date REGEXP '^([[:digit:]]{4}, )+[[:digit:]]{4}$'; 

#Change , to ;
UPDATE tblcollections_content SET NewDate = REPLACE(Date, ",", ";") 
, Type = 6
WHERE Date REGEXP '^([[:digit:]]{4}(/[[:digit:]]{2})?, )+[[:digit:]]{4}(/[[:digit:]]{2})?$' AND NewDate LIKE "";

#Change commas into semicolons
UPDATE tblcollections_content SET NewDate = REPLACE(Date, ",", ";") 
, Type = 6
WHERE Date REGEXP '^([[:digit:]]{4}(-[[:digit:]]{4})?, )+[[:digit:]]{4}(-[[:digit:]]{4})?$' AND NewDate LIKE "";


#---------------------------------Other Format Fixes----------------------------------------------------------


#Making sure no question marks sneak into NewDate
UPDATE tblcollections_content SET NewDate = REPLACE(NewDate, "?", "");

#Making sure hyphens are in the correct format
UPDATE tblcollections_content SET NewDate = REPLACE(Date, " -", "-") 
, Type = 7 WHERE Date REGEXP '^[[:digit:]]{4} -$'; 

#Correct dates with n.d.
UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(Date, ", ", ""), "n.d.", "no date") 
, Type = 7
WHERE Date LIKE "%n.d%" AND NewDate LIKE "";

UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(Date, ", ", ""), "Undated", "no date") 
, Type = 7
WHERE Date LIKE "%Undated%" AND NewDate LIKE "";

UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(REPLACE(NewDate, ", ", ""), "Undated", "no date"), "undated", "no date") 
, Type = 7
WHERE NewDate LIKE "%ndated%";

#Remove extra spaces from Dates
UPDATE tblcollections_content SET NewDate = REPLACE(Date, " ", "") 
, Type = 7
WHERE Date REGEXP '^[[:digit:]]{4}[[. .]]?-[[. .]]?[[:digit:]]{4}$' AND NewDate LIKE "";

#Remove 's' from the end of years
UPDATE tblcollections_content SET NewDate = REPLACE(Date, "s", "") 
, Type = 7
WHERE Date REGEXP '^[[:digit:]]{4}s?-[[:digit:]]{4}s?$' AND NewDate LIKE "";


#------------------------DON'T CHANGE ORDER AFTER HERE------------------------


#Fixing up circas and undateds
UPDATE tblcollections_content SET NewDate = REPLACE(Date, 'circa', 'circa') 
, Type = 8
WHERE Date REGEXP ';' >= 1 AND NewDate LIKE "" AND Date NOT LIKE "%to%" AND Date NOT REGEXP '^[[:digit:]]{2}-[[:digit:]]{2}' 
AND Date NOT LIKE  '%; circa%';

#Fixing a few specific cases
UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(REPLACE(REPLACE(Date, '-Jan.', ''), '.', ''), 'app', ''), ' - ', '-')
, Type = 8 
WHERE NewDate LIKE "" AND (DATE LIKE '%-Jan%' OR Date LIKE '%app%');

#Remove excess parens
UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(REPLACE(REPLACE(Date, '(', ''), ')', ''), ' ', '; '), ',', '') 
, Type = 8
WHERE Date REGEXP '^([[.(.]]?[[:digit:]]{4}(-[[:digit:]]{4})?[[.).]]?,?[[. .]])+[[:digit:]]{4}(-[[:digit:]]{4})?$' AND NewDate LIKE "";

#Correct 'or'
UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(Date, ' or ', '-'), '.', '') 
, Type = 8
WHERE (Date LIKE "%or%" OR Date LIKE "%.%") AND NewDate LIKE "" AND Date NOT LIKE '%/%' AND Date NOT LIKE '%before%' AND Date NOT LIKE '%n.d%';

#remove extra spaces
UPDATE tblcollections_content SET NewDate = REPLACE(Date, " - ", "-") 
, Type = 8
WHERE Date REGEXP '^[[:digit:]]{4}/[[:digit:]]{2}' AND NewDate LIKE "" AND Date NOT LIKE "%.%" 
AND Date NOT REGEXP '(/[[:digit:]]{3})|(-[[:digit:]]{2}$)|[[:digit:]]{5,}';

#Extract Year from date
UPDATE tblcollections_content SET NewDate = RIGHT(Date, 4) , Type = 5 WHERE Date REGEXP '^[[:digit:]]{1,2}/[[:digit:]]{1,2}/[[:digit:]]{4}$';

#Replace 'I'
UPDATE tblcollections_content SET NewDate = REPLACE(Date, 'I', ' (+ or - 1 year)') 
, Type = 4
WHERE NewDate LIKE "" AND Date LIKE "%I%" AND Date NOT LIKE "%circa%" AND Date REGEXP '^[[:digit:]]{4}I';

#More paren handling
UPDATE tblcollections_content SET NewDate = REPLACE(REPLACE(REPLACE(Date, '?)', ''), '(', ''), ',', ';') 
, Type = 8
WHERE  Date LIKE "%,%" AND NewDate LIKE "" AND Date NOT REGEXP '[[:alpha:]]';

#Handle or before
UPDATE tblcollections_content SET NewDate = CONCAT('before ', REPLACE(Date, ' or before', '')) 
, Type = 3
WHERE NewDate LIKE "" AND Date LIKE '%or before%';

#Handle and later
UPDATE tblcollections_content SET NewDate = REPLACE(Date, ' and later', '-')
, Type = 3 
WHERE NewDate LIKE "" AND Date LIKE '%and later%';

#Trim any extra spaces
UPDATE tblcollections_content SET NewDate = REPLACE(TRIM(NewDate), "  ", " ");

UPDATE tblcollections_content SET NewDate = REPLACE(NewDate, ' or ', '-'), Type = 8 
WHERE NewDate LIKE '% or %' AND NewDate NOT LIKE '%(+ or -%';

UPDATE tblcollections_content SET Type = 9 WHERE NewDate LIKE "%;%" AND NewDate REGEXP '[[:digit:]]{4}-[[:digit:]]{4}';

UPDATE tblcollections_content SET Type = 10 WHERE Type = 0 and Date NOT LIKE "";

UPDATE tblcollections_content SET NewDate = "", Type = 10 WHERE NewDate LIKE "%+ or -%" AND NewDate NOT REGEXP '[[:digit:]]{4}';

UPDATE tblcollections_content SET NewDate = REPLACE(NewDate, "C", "c") WHERE NewDate LIKE "%Circa%";

UPDATE tblcollections_content SET NewDate = REPLACE(NewDate, "no date", "; no date") WHERE NewDate REGEXP '[[:digit:]]no date';

UPDATE tblcollections_content SET NewDate = "", Type = 10 WHERE NewDate LIKE '%-%' AND NewDate LIKE '%s%' AND NewDate NOT LIKE '%+%';



SELECT * FROM tblcollections_content WHERE NewDate NOT REGEXP 
'^[[:digit:]]{4}$|^[[:digit:]]{4}-[[:digit:]]{4}$|^circa [[:digit:]]{4}$|^circa [[:digit:]]{4}-circa [[:digit:]]{4}$|^[[:digit:]]{4}/[[:digit:]]{2}$|^[[:digit:]]{4}/[[:digit:]]{4}$|^before [[:digit:]]{4}$|^after [[:digit:]]{4}$|^([[:digit:]]{4}(-[[:digit:]]{4})?;)* ?[[:digit:]]{4}(-[[:digit:]]{4})?;?$|^circa [[:digit:]]{4}-[[:digit:]]{4}$|^[[:digit:]]{4} [[.(.]][[.+.]] or - [[:digit:]]{1} years[[.).]]$|^[[:digit:]]{4} [[.(.]][[.+.]] or - [[:digit:]]{1} year[[.).]]$|^[[:digit:]]{4}/[[:digit:]]{2}-[[:digit:]]{4}/[[:digit:]]{2}$|^([[:digit:]]{4}; )+[[:digit:]]{4};?$|^[[:digit:]]{4}-$|^(([[:digit:]]{4};[[. .]])|([[:digit:]]{4}-[[:digit:]]{4}[[. .]]))*[[:digit:]]{4};?$|^((([[:digit:]]{4})|([[:digit:]]{4}-[[:digit:]]{4}));[[. .]]?)+(([[:digit:]]{4})|([[:digit:]]{4}-[[:digit:]]{4}))$'
AND Date NOT LIKE ""






