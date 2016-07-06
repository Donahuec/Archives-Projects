Index Search Project
Caleb Braun, Carleton College
7/6/16



Purpose:

To make the database's search engine get more accurate results by adding a searchable field of keywords.


Installation:

1. Add indexutil.php to /packages/core/admin/
2. Replace /packages/core/admin/database.php with the new version
3. Run the query index_search_phrases.sql 


Usage:

The following mods implement a new tool in Archon Administration -> Database Management called "Index search". It gives the user the option to add a searchable user field to an individual item, a whole collection, or the whole database. Given an item or collection ID, it finds the keywords to index in the collection's RevisionHistory field. 
