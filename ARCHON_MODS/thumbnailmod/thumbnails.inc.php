<?php
/**
* packages/digitallibrary/templates/carleton/thumbnails.inc.php
*
* DigitalContent template
*
* The variable:
*
*  $arrFiles  <-- uhh but this doesn't actually exist here... -Caleb
*
* is an array of File objects, with its properties
* already loaded when this template is referenced.
*
* Refer to the File class definition in packages/digitallibrary/lib/file.inc.php
* for available properties and methods.
*
* The Archon API is also available through the variable:
*
*  $_ARCHON
*
* Refer to the Archon class definition in packages/core/lib/archon.inc.php
* for available properties and methods.
*
* @package Archon
* @author Kyle Fox
* Edited by Caleb Braun 8/18/2016
*/
isset($_ARCHON) or die();

echo("<h1 id='titleheader'>" . strip_tags($_ARCHON->PublicInterface->Title) . "</h1>\n");

$objPleaseEnterPhrase = Phrase::getPhrase('digitallibrary_pleaseenter', PACKAGE_DIGITALLIBRARY, 0, PHRASETYPE_PUBLIC);
$strPleaseEnter = $objPleaseEnterPhrase ? $objPleaseEnterPhrase->getPhraseValue(ENCODE_JAVASCRIPTTHENHTML) : 'Please enter search terms.';
$objSearchImagesPhrase = Phrase::getPhrase('digitallibrary_searchimages', PACKAGE_DIGITALLIBRARY, 0, PHRASETYPE_PUBLIC);
$strSearchImages = $objSearchImagesPhrase ? $objSearchImagesPhrase->getPhraseValue(ENCODE_HTML) : 'Search Images';

?>

<!-- VTNM: Put search box at top of page along with radio buttons to select media type -->
<div class='center'>
<form action="index.php" accept-charset="UTF-8" method="get" onsubmit="if(!this.q.value) { alert('<?php echo($strPleaseEnter); ?>'); return false; } else { return true; }">
   <div id="dlsearchblock">
      <input type="hidden" name="p" value="digitallibrary/thumbnails" />
      <input type="text" size="20" title="search" maxlength="150" name="q" value="<?php echo(encode($_ARCHON->QueryString, ENCODE_HTML)); ?>" tabindex="50" />
      <input type="submit" value="<?php echo($strSearchImages); ?>" tabindex="51" id='imagesbutton' class='button' /><br/>
      Showing:
      <div class='radiobuttonset'>
        <input type='radio' class='fieldradiobutton' id='radioVideo' name='media' value='video' /> <label for='radioVid'>Video&nbsp;</label>
        <input type='radio' class='fieldradiobutton' id='radioAudio' name='media' value='audio' /> <label for='radioAudio'>Audio </label>
        <input type='radio' class='fieldradiobutton' id='radioImage' name='media' value='image' /> <label for='radioImage'>Images</label>
        <input type='radio' class='fieldradiobutton' id='radioAll' name='media' value='all' /> <label for='radioAll'>All Media</label>
      </div>
   </div>
</form>
</div>

<script type="text/javascript">
/* <![CDATA[ */
if(window.jQuery !== undefined && jQuery.cluetip !== undefined)
{
    $(document).ready(function () {
        $('#radio<?php echo $in_MediaType ?>').attr('checked', true);
        $('.thumbnailimg .thumbimglink').cluetip({dropShadow: false, width: 520, tracking: true, showTitle: true, local: true, onActivate: function (e) {
            var src = $(e).find('img').attr('src');

            var idExp = /id=(\d+)/;
            var idMatchArray = idExp.exec(src);
            if(idMatchArray !== null)
            {
                var id = idMatchArray[1];
                $('#mediumPreview img').attr('src', '?p=digitallibrary/getfile&id=' + id + '&preview=long');

                return true;
            }

            if(src.indexOf('ps_') != -1){
                var newSrc = src.replace('ps_', 'pl_');
                $('#mediumPreview img').attr('src', newSrc);
                return true;
            }

            return false;
        }});
    })

}
/* ]]> */
</script>
<?php
if($_ARCHON->QueryString)
{
    echo("You searched for \"" . htmlspecialchars($_ARCHON->QueryString) . "\".<br/><br/>");
}

if($in_CollectionID)
{
    $objCollection = New Collection($in_CollectionID);

    if(!$objCollection->dbLoad())
    {
        return;
    }

    echo("Searching within the finding aid for " . $objCollection->toString() . "<br/><br/>");
}
else if($in_CollectionContentID)
{
    $objCollectionContent = New CollectionContent($in_CollectionContentID);

    if(!$objCollectionContent->dbLoad())
    {
        return;
    }

    echo("Searching for Item " . $objCollectionContent->toString(LINK_NONE) . "<br/><br/>");
}

if($in_CreatorID && defined('PACKAGE_CREATORS'))
{
    $objCreator = New Creator($in_CreatorID);

    if(!$objCreator->dbLoad())
    {
        return;
    }

    echo("Searching for Creator: " . $objCreator->toString() . "<br/><br/>\n");
}

if($in_SubjectID && defined('PACKAGE_SUBJECTS'))
{
    $objSubject = New Subject($in_SubjectID);

    if(!$objSubject->dbLoad())
    {
        return;
    }

    echo("Searching for Subject: " . $objSubject->toString(LINK_NONE, true) . "<br/><br/>\n");
}

if(!empty($arrDigitalContent))
{
    ?>
    <div id="mediumPreview" style="display: none;">
        <img id="mediumpreviewimg" src="" alt="Medium Preview" />
    </div>
    <?php
    // VTNM: Changes to layout with video thumbnails - refer to themes/carleton/style.css
    foreach($arrDigitalContent as $objDigitalContent)
    {
        $count = 0;
        $media = $in_MediaType;
        foreach($objDigitalContent->Files as $objFile)
        {
            $count++;
            $cssClass = "thumbnailimg";
            $mediaURL = $objFile->getFileURL(DIGITALLIBRARY_FILE_PREVIEWSHORT);

            // Slightly different style for the video thumbnail page
            if ($in_MediaType == 'Video')
            {
                $cssClass = "thumbnailvid";
            }

            if ($in_MediaType == 'All')
            {
              $media = $objFile->FileType->MediaType->MediaType;
            }

            if ($media == 'Image')
            {
                $content = "<img class='digcontentfile' src='" . $mediaURL . "' alt='" . $objFile->getString('Title') . "'/>";
            }
            elseif ($media == 'Audio') {
                $content  = "<img src='" . $_ARCHON->PublicInterface->ImagePath . "/thumbnail-icons/audioicon.png' style='border:none'/></a>";
                $content .= "<img src='" . $_ARCHON->PublicInterface->ImagePath . "/thumbnail-icons/listen-play.png' class='play'/> ";
                // $content .= "<p style='border:none'>" . $objFile->getString('Title') . "</p>";
                $content .= "<audio preload='metadata'>";
                $content .= "<source src='".$mediaURL."'>";
                $content .= "Your browser does not support the audio element. </audio><a>";
            }
            elseif ($media == 'Video') {
                $content = "<video class='videothumbnail' muted preload='metadata' onmouseover='this.play()' onmouseout='this.pause()'>";
                // Remove the #t=8 if you want the page to load faster.
                $content .= "<source src='" . $mediaURL . "#t=8' />";
                $content .= "<p>Your browser does not support the video tag.</p> </video>";
                $content .= "<div class='videocaption'>";
                $content .= "<img src='" . $_ARCHON->PublicInterface->ImagePath . "/thumbnail-icons/video.png' />";
                $content .= "</div>";
            }
            elseif ($media == 'Document') {
                $content = "<img class='digcontentfile' style='border:none' src='" . $_ARCHON->PublicInterface->ImagePath . "/thumbnail-icons/documenticon.png'/>";
                $content .= "<p style='border:none'>" . $objFile->getString('Title') . "</p>";
            }
            else {
                $content = "<img class='digcontentfile' style='border:none' src='" . $_ARCHON->PublicInterface->ImagePath . "/thumbnail-icons/othermediaicon.png'/>";
                $content .= "<p style='border:none'>" . $objFile->getString('Title') . "</p>";
            }

            ?>

            <div class="<?php echo($cssClass); ?>">
                <div class="<?php echo($cssClass); ?>wrapper">
                    <a class="thumbimglink" href="?p=digitallibrary/digitalcontent&amp;id=<?php echo($objFile->DigitalContentID); ?>" title="<?php echo($objDigitalContent->getString('Title', 30)); ?>" rel="#mediumPreview">
                        <?php echo($content); ?>
                    </a>
                </div>
                <div class="thumbnailcaption">
                    <a href="?p=digitallibrary/digitalcontent&amp;id=<?php echo($objFile->DigitalContentID); ?>"><?php echo($objDigitalContent->getString('Title', 30)); ?></a>
                    <?php
                    if(count($objDigitalContent->Files) > 1)
                    {
                        echo '<br/>(' . $count . ' out of ' . count($objDigitalContent->Files) . ')';
                    }
                    ?>
                </div>
            </div>
            <?php
        }
    }
}
else
{
    // VTNM: Made results message more general – uses var $in_MediaType and adds 's'
    if($in_MediaType == 'All') {
      echo "No media found!";
    } else {
      echo("No " . strtolower($in_MediaType) . (($in_MediaType == 'Audio') ? '' : 's') . " found!");
    }
}

if($in_ThumbnailPage > 1 || $_ARCHON->MoreThumbnailPages)
{
    echo("<div id='thumbnailnav'>");

    if($in_ThumbnailPage > 1)
    {
        $prevPage = $in_ThumbnailPage - 1;
        // VTNM: Added '&media=$in_MediaType' to URL
        $prevURL = encode($_ARCHON->ThumbnailURL . "&media=$in_MediaType&thumbnailpage=$prevPage", ENCODE_HTML);
        echo("<span id='thumbnailprevlink'><a href='$prevURL'>Prev</a></span>");
    }
    if($_ARCHON->MoreThumbnailPages)
    {
        $nextPage = $in_ThumbnailPage + 1;
        // VTNM: Added '&media=$in_MediaType' to URL
        $nextURL = encode($_ARCHON->ThumbnailURL . "&media=$in_MediaType&thumbnailpage=$nextPage", ENCODE_HTML);
        echo("<span id='thumbnailnextlink'><a href='$nextURL'>Next</a></span>");
    }
    echo("</div>");
}

?>

<script>

/* ~~ VTNM scripts ~~ */

// Reload the page with the new selected thumbnail type
(function () {
    $(".radiobuttonset").change(function() {
        window.location = '<?php echo $_ARCHON->ThumbnailURL . "&media=";?>' + $('input[name=media]:checked').val();
    });
})();

// Adds timestamp to the caption each video
$(document).ready(function(){
  var captions = $(".videocaption");
  $(".videothumbnail").each(function(i, vid) {
    vid.addEventListener('loadedmetadata', function() {
      var duration = '';
      var hours = Math.floor(vid.duration / 3600);
      var minutes = Math.floor(vid.duration / 60);
      var seconds = Math.floor(vid.duration % 60);
      seconds = ("0" + seconds).slice(-2);
      if (hours) {
        minutes -= 60 * hours;
        minutes = ("0" + minutes).slice(-2);
        duration += hours + ":";
      }
      duration += minutes + ":" + seconds;
      captions[i].innerHTML = "<span>" + duration + "</span>" + captions[i].innerHTML;
    });
  });
})

// Plays the track associated with an audio play button when clicked
$(".play").click(function(e){
    var clicked = e.target;
    var audio = $(clicked).next().get(0);
    if (audio.paused) {
      audio.play()
      $(clicked).attr('src', '<?php echo $_ARCHON->PublicInterface->ImagePath . "/thumbnail-icons/listen-pause.png";?>');
    } else {
      audio.pause()
      $(clicked).attr('src', '<?php echo $_ARCHON->PublicInterface->ImagePath . "/thumbnail-icons/listen-play.png";?>');
    }
});

</script>
