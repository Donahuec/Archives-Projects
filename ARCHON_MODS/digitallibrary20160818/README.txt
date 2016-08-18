Digital Library Update
Caleb Braun <calebjbraun@gmail.com>
Carleton College Archives
8/18/2016


What's Changed:
- All updates to the digital library now also update the size of the files.
- Removed incorrect error messages when saving a digital content item from the administrative interface.
- Significantly refactored the code that syncs digital content files with the database upon saving a digital content item. Code is now simpler and more in line with Archon's core functionality.
- Added buttons to update blobs and sync digital content to the Database Management page of the administrative interface.
- Cleaned the format of the report, added a summary, and removed excess information.
- Fixed bug where buttons on the report interface do not respond.
 

Updated Files:
packages/digitallibrary/admin/convertblobs.php
packages/digitallibrary/admin/digitallibrary.php
packages/digitallibrary/admin/updatecontentfiles.php
packages/digitallibrary/lib/digitalcontent.inc.php


Known Issues:
- Deleting a file from the admin interface does not actually delete the file (because the user does not have permission to delete files on the server). It is removed from the database, but because it still exists in the file structure, it will be added back into the database the next time a sync is made.
- If an error is raised saving a file from the admin interface (ex. a file has a bad extension), no further errors will be reported unless the page is refreshed.  Instead, a blank error box appears upon hitting save again, unless the error is immediately fixed.  Looking into it revealed that it would be complicated to fix and not worth it for such a specific bug.
