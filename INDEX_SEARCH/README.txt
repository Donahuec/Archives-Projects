Index Search Project
Caleb Braun, Carleton College
7/7/16



Purpose:

To make the database's search engine get more accurate results by adding a searchable field of keywords.


Installation:

-- Utility --
1. Add indexutil.php to /packages/core/admin/
2. Replace /packages/core/admin/database.php with the new version
3. Run the query index_search_phrases.sql 
4. In /packages/collections/admin/collectioncontent.php, insert the lines:
	$_REQUEST['itemidnum'] = $_REQUEST['id'];
	include "packages/core/admin/indexutil.php";
right after "$location = ($count > 1) ? ..." within the "if($_REQUEST['f'] == 'store')" statement at the end of the file.


Usage:

The following mods implement a new tool in Archon Administration -> Database Management called "Index search". It gives the user the option to add a searchable user field to an individual item, a whole collection, or the whole database. Given an item or collection ID, it indexes the collection title, date, and creators associated with item.  Date ranges are exploded out to include each year.  Any keywords from user fields can be specified in the collection's RevisionHistory field.  These fields should be written in a comma separated list, for example:
Narrator, Interviewer, Subjects



Email Caleb Braun (calebjbraun@gmail.com) with any questions.
