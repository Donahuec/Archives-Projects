-- Digital Library Thumbnail Mod --
Caleb Braun <calebjbraun@gmail.com>
Carleton College Archives
8/18/2016


-- About --
This mod adds views for video and audio files to the default image thumbnail viewer. A view for a specific media type can be selected from the collection level, or by clicking the corresponding radio button under the search bar at the top center of the page. The default view is of all media types.


-- Installation --
Replace the following files with the ones in this folder:
	- themes/carleton/style.css
	- packages/digitallibrary/templates/carleton/thumbnails.inc.php
	- packages/digitallibrary/pub/thumbnails.php
	- packages/collections/templates/carleton/controlcard.inc.php
	- packages/collections/pub/controlcard.php
	- OPTIONAL: Change themes/carleton/header.inc.php to direct the digital content link directly to thumbnail page:
		Find: ?p=digitallibrary/digitallibrary
		Replace: ?p=digitallibrary/thumbnails

Alternatively, the specific changes within each of these files are marked by a comment containing "VTNM" (Video ThumbNail Mod), so it would not be difficult to insert those changes in existing files without complete replacement.

Using the phrase manager, update the phrase digitallibrary_browsethumbnails to "Browse Thumbnails" or "Browse All Thumbnails", to reflect the change.


-- Details --
The majority of changes are in thumbnails.inc.php and are mainly HTML or JavaScript additions.

Most of the changes were related to the HTML/CSS required for embedding the different media types. It works and looks pretty good if we use the same general template as the image thumbnails, and simplifies things to use the same file but just add on another tag to the URL.

Thumbnail generation is done by loading the metadata for the video and audio, rather than loading the entire thing. Part of the metadata for videos is the first frame, so the thumbnail can be simply the first frame of the video. It is currently set up, however to skip a number of seconds into the video and thereby show a more significant thumbnail. This slows things down and if it makes things too slow, removing this feature is simple (line 185). The videos are set to load and play when the user hovers their mouse over the thumbnail.

Because the default HTML5 audio player does not fit well in a thumbnail sized frame, audio thumbnails use a simple, custom play/pause button. There are a couple designs for the look of the button in the image files, and new ones could be easily created.

The radio buttons and search bar at the top of the thumbnail page are intended to make finding desired thumbnails quick and easy. Although the only options are video, audio, images, and all, it could also be set up to include documents or other media.  Buttons for those can be added in the same way.

The changes in pub/controlcard.php and controlcard.inc.php are for clicking directly to a different media format from a collection's controlcard page (rather than only having the choice to browse image thumbnails).  This could also be done as a result of searching the digital library, such is already done with images (digitallibrary/pub/search.php line 115), but I think this would not look good because it might place too many "browse x thumbnails" links in the results list of the search.

Several other files refer to the digital library thumbnails and could potentially be updated to represent that it now shows more media types.  These files are:
	creators/pub/creator/php
	creators/templates/carleton/creator.inc.php
	digitallibrary/pub/digitallibrary.php
	digitallibrary/pub/search.php

Stylistic changes can be made by modifying style.css.  All changes for this mod are in the section labeled "VTNM".
