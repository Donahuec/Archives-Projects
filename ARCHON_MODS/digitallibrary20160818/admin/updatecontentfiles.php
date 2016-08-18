<?php

/*
    packages/digitallibrary/admin/updatecontentfiles.php
    Last Edited by Caleb Braun
    7/25/16
*/

isset($_ARCHON) or die();
require_once("header.inc.php");
?>

<div style="display: block; z-index: 1002; outline: 0px; height: auto; width: 600px; top: 28px; left: 306px; " class="ui-dialog ui-widget ui-widget-content ui-corner-all " tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-response">
  <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
    <span class="ui-dialog-title" id="ui-dialog-title-response">Digital Library Update</span>
    <a href="#" class="ui-dialog-titlebar-close ui-corner-all" role="button" onclick="window.close();return false;">
      <span class="ui-icon ui-icon-closethick">close</span>
    </a>
  </div>
  <div id="response" style="width: auto; min-height: 31.266666889190674px; height: auto; " class="ui-dialog-content ui-widget-content">
    <div style="padding: 6px; height: 400px; overflow: auto; color: white; background-color: rgb(51, 51, 51); font-size: 12px; font-weight: normal; background-position: initial initial; background-repeat: initial initial; ">

<?php

// Stats for report
$warnings = 0;
$numerrs = 0;
$deletes = 0;
$updates = 0;
$uploads = 0;

ob_start();

// Make sure the column DirectLink exists in the database
addDirectLinkColumn();

// Get all digital content metadata from database
$query = "SELECT * FROM tblDigitalLibrary_DigitalContent";
$result = $_ARCHON->mdb2->query($query);
if(PEAR::isError($result)) {
    echo($query);
    trigger_error($result->getMessage(), E_USER_ERROR);
}
$rows = $result->fetchAll();
$result->free();

foreach ($rows as $objDigitalContent) {
    $_ARCHON->clearError();
    ob_start();

    // Get all of the files associated with each digital content item
    $objDigitalContent = New DigitalContent($objDigitalContent);
    $objDigitalContent->dbLoadFiles();
    $newFileArr = $objDigitalContent->getContentURLFilePaths();
    $errors = $newFileArr["Errors"];
    unset($newFileArr["Errors"]);

    if (!$newFileArr) {
        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font color='orange'>";
        echo "No digital content associated with this record.</font><br>";
    }

    // Delete db records that no longer exists, update size, or do nothing
    foreach ($objDigitalContent->Files as $objFile) {
        $link = $objFile->DirectLink;

        if (!isset($newFileArr[$link])) {
            $objFile->dbDelete();
            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"' . $objFile->Title . '" Deleted Successfully<br>';
            $deletes++;
        } elseif ($newFileArr[$link] != $objFile->Size) {
            $objFile->Size = $newFileArr[$link];
            $objFile->dbStore();
            $updates++;
            unset($newFileArr[$link]);
        } else {
            unset($newFileArr[$link]);
        }
    }

    // Anything left in $newFileArr is a new file that needs to be stored
    foreach($newFileArr as $url => $size) {
        $file = New File();
        $file->DirectLink = $url;
        $file->DigitalContentID = $objDigitalContent->ID;
        $file->Title = basename($url);
        $file->Filename = basename($url);
        $file->Size = $size;

        if ($file->dbStore()) {
            $uploads++;
            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"' . $file->Title . '" Uploaded Successfully<br>';
        } else {
            $numerrs++;
            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"' . $file->Title . '" could not be uploaded. ' . $_ARCHON->Error . '<br>';
        }
    }

    foreach ($errors as $e) {
        if (!$warnings) echo '<a id="warn"></a>';
        $warnings++;
        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font color="orangered">Warning: </font>';
        echo 'Item "' . basename($e) . '" has an unrecognized extension and will not be uploaded.<br>';
    }

    // Only send output if there is something to report
    if (ob_get_contents()) {
        echo 'Updating Digital Content Item: "'. $objDigitalContent->toString() . '"<br>' . ob_get_clean() . '<br>';
    } else {
        ob_end_clean();
    }
}

$output = ob_get_clean();
echo "<h3>Digital Content Updates Report: </h3><br><br>";
if ($warnings) echo "$warnings <a href='#warn' style='color:lightblue'>warning" . str_repeat('s', $warnings != 1) . ".</a><br>";
echo "$numerrs error" . str_repeat('s', $numerrs != 1) . ".<br>";
echo "$deletes file" . str_repeat('s', $deletes != 1) . " deleted.<br>";
echo "$uploads file" . str_repeat('s', $uploads != 1) . " uploaded.<br>";
if ($updates) echo "Updated the size for $updates file" . str_repeat('s', $updates != 1) . ".<br>";
echo str_repeat("_", 50);
echo "<br><br>" . $output;

echo "</div>";



//Adds "DirectLink" to tbl_DigitalLibrary_Files if it does not already exist
function addDirectLinkColumn() {
    global $_ARCHON;

    $query = "SHOW COLUMNS FROM tblDigitalLibrary_Files LIKE 'DirectLink'";
    $result = $_ARCHON->mdb2->query($query);
    if(PEAR::isError($result)) {
        echo($query);
        trigger_error($result->getMessage(), E_USER_ERROR);
    }
    $rows = $result->fetchAll();
    $result->free();

    if (count($rows) < 1) {
        $query = "ALTER TABLE tblDigitalLibrary_Files ADD DirectLink varchar(500) AFTER FileTypeID";
        $resolution =& $_ARCHON->mdb2->exec($query);
        if(PEAR::isError($resolution)) {
            echo($query);
            trigger_error($resolution->getMessage(), E_USER_ERROR);
            $resolution->free();
        }
        echo "Added 'DirectLink' Column to tblDigitalLibrary_Files<br>";
    }
}



?>

<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
    <div class="ui-dialog-buttonset">
        <button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false" onclick="window.close()">
            <span class="ui-button-text">Ok</span>
        </button>
    </div>
</div>

<?
require_once("footer.inc.php");
?>
