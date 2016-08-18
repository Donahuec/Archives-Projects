<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * change copied file's DirectLink (nothing new inserted into Files)
 */
isset($_ARCHON) or die();
require_once("header.inc.php");
?>
<div style="display: block; z-index: 1002; outline: 0px; height: auto; width: 600px; top: 28px; left: 306px; " class="ui-dialog ui-widget ui-widget-content ui-corner-all " tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-response">
  <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
    <span class="ui-dialog-title" id="ui-dialog-title-response">Convert Blobs</span>
    <a href="#" class="ui-dialog-titlebar-close ui-corner-all" role="button" onclick="window.close();return false;">
      <span class="ui-icon ui-icon-closethick">close</span>
    </a>
  </div>
  <div id="response" style="width: auto; min-height: 31.266666889190674px; height: auto; " class="ui-dialog-content ui-widget-content">
    <div style="padding: 6px; height: 400px; overflow: auto; color: white; background-color: rgb(51, 51, 51); font-size: 12px; font-weight: normal; background-position: initial initial; background-repeat: initial initial; ">
<?php

function suffix($string) {

        preg_match("/\/files.*/",$string,$matches);
        $string = $matches[0];
        if (strrpos($string,'/') == strlen($string) - 1) {
             $string = substr($string,0,-1);
        }
        return $string;

}

function downloadFile($id,$path) {
    $objFile = New File($id);
    $objFile -> dbLoad();
    if(!file_exists($path))
    {
        $fp = fopen($path, 'wb');
        if($objFile->fopen(DIGITALLIBRARY_FILE_FULL))
        {

            while(!$objFile->feof())
            {
               fwrite($fp, $objFile->fread(1048576));
            }
            $objFile->fclose();
        }
        fclose($fp);
        return true;
    }
    echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>File Already Exists:</b> '" . suffix($path) . "'";
    return false;
}

$root = substr(dirname(__FILE__),0,-1 * strlen("/packages/digitallibrary/admin"));

$query = 'SELECT ID,DigitalContentID,Title,Size,Filename,FileTypeID FROM tblDigitalLibrary_Files
WHERE DigitalContentID IN (SELECT DISTINCT DigitalContentID FROM tblDigitalLibrary_Files
WHERE FileContents != "" AND DefaultAccessLevel > 0 AND DirectLink IS NULL)
AND FileContents != ""
AND DefaultAccessLevel > 0
AND DirectLink IS NULL
ORDER BY DigitalContentID ASC';
$result = $_ARCHON -> mdb2 -> query($query);
if(PEAR::isError($result)) {
    echo($query);
    trigger_error($result->getMessage(), E_USER_ERROR);
}
$rows = $result -> fetchAll();
$result -> free();
$digitalContentArray = array();
$curDigitalID = NULL;
foreach ($rows as $file) {
    if ($curDigitalID != $file[DigitalContentID]) {
        $curDigitalID = $file[DigitalContentID];
        $digitalContentArray[$curDigitalID] = array();
        $digitalContentArray[$curDigitalID][DigitalContentID] = $curDigitalID;
    }
    $digitalContentArray[$file[DigitalContentID]][$file[ID]] = $file;
}
$allChanges = array();
foreach ($digitalContentArray as $digitalContent) {
    $objDigitalContent = New DigitalContent($digitalContent[DigitalContentID]);
    $objDigitalContent -> dbLoad();
    $dcChanges = array('Blobs' => array(),
                       'NewContentURL'    => "",
                       'OriginalFile'     => "",
                       'NewDirectory'     => "",
                       'DigitalContentID' => $objDigitalContent -> ID,
                       'Title'            => $objDigitalContent -> Title,
                       'ContentURL'       => $objDigitalContent -> ContentURL);
   if ($objDigitalContent -> ContentURL) {
       preg_match("/files.*/",$objDigitalContent -> ContentURL,$matches);
       $contentURLFilePath = $matches[0];
       if ($contentURLFilePath) {
        if (file_exists($contentURLFilePath)) {
            if (is_dir($contentURLFilePath)) {
                //Directory exists, we can add new files to it.
                foreach (array_slice($digitalContent,1) as $dlfile) {
                    if (strrpos($contentURLFilePath,'/') < strlen($contentURLFilePath) - 1) {
                        $contentURLFilePath .= '/';
                    }
                    //$path = $_SERVER['DOCUMENT_ROOT'] . "/Archon/" . $contentURLFilePath . $dlfile[Filename];
                    $path = $root . '/' . $contentURLFilePath . $dlfile[Filename];
                    $dcChanges['Blobs'][$dlfile[ID]] = array();
                    $dcChanges['Blobs'][$dlfile[ID]]['ID'] = $dlfile[ID];
                    $dcChanges['Blobs'][$dlfile[ID]]['Path'] = $path;
                    $dcChanges['Blobs'][$dlfile[ID]]['DigitalContentID'] = $objDigitalContent->ID;
                    $dcChanges['Blobs'][$dlfile[ID]]['Title'] = $dlfile[Title];
                    $dcChanges['Blobs'][$dlfile[ID]]['Filename'] = $dlfile[Filename];
                    $dcChanges['Blobs'][$dlfile[ID]]['FileTypeID'] = $dlfile[FileTypeID];
                    $dcChanges['Blobs'][$dlfile[ID]]['DirectLink'] = $objDigitalContent -> ContentURL ."/". $dlfile[Filename];

                }
//            print_r($dcChanges['Blobs']);
            $allChanges[$objDigitalContent -> ID] = $dcChanges;
            continue;
            }
            //Links to a single item in files/
            else {
                if (strrpos($contentURLFilePath,'/') < strlen($contentURLFilePath) - 1) {
                    $contentURLFilePath .= '/';
                }
                if (strrpos($objDigitalContent->ContentURL,'/') < strlen($objDigitalContent->ContentURL) - 1) {
                    $objDigitalContent->ContentURL .= '/';
                }
               //$OriginalFile = $_SERVER['DOCUMENT_ROOT'] . "/Archon/" . $contentURLFilePath;
                $OriginalFile = $root . '/' . $contentURLFilePath;
                //echo "orig: " . $OriginalFile . "<br>";
                //$suffix = $objDigitalContent -> ID . "_$objDigitalContent->Title/";
                $suffix = $objDigitalContent -> Identifier;
                //$NewDirectory = substr($_SERVER['DOCUMENT_ROOT'] . "/Archon/" . $contentURLFilePath,0,-1 * (strlen(basename($OriginalFile)) + 1)) . $suffix;
                $NewDirectory = substr($root . '/' . $contentURLFilePath,0,-1 * (strlen(basename($OriginalFile)) + 1)) . $suffix;
                $NewContentURL = substr($objDigitalContent->ContentURL,0,-1 * (strlen(basename($OriginalFile)) + 1)) . $suffix;
                $dcChanges['OriginalFile'] = $OriginalFile;
                $dcChanges['NewDirectory'] = $NewDirectory;
                $dcChanges['NewContentURL'] = $NewContentURL;
                foreach (array_slice($digitalContent,1) as $dlfile) {
                    $path = $NewDirectory . '/' . $dlfile[Filename];
                    $dcChanges['Blobs'][$dlfile[ID]] = array();
                    $dcChanges['Blobs'][$dlfile[ID]]['ID'] = $dlfile[ID];
                    $dcChanges['Blobs'][$dlfile[ID]]['Path'] = $path;
                    $dcChanges['Blobs'][$dlfile[ID]]['DigitalContentID'] = $objDigitalContent->ID;
                    $dcChanges['Blobs'][$dlfile[ID]]['Title'] = $dlfile[Title];
                    $dcChanges['Blobs'][$dlfile[ID]]['Filename'] = $dlfile[Filename];
                    $dcChanges['Blobs'][$dlfile[ID]]['FileTypeID'] = $dlfile[FileTypeID];
                    $dcChanges['Blobs'][$dlfile[ID]]['DirectLink'] = $NewContentURL ."/". $dlfile[Filename];
                }

                $allChanges[$objDigitalContent -> ID] = $dcChanges;
                continue;
            }
         }
        }
     }
   //No relevant pre-existing info, just make a new directory in /archon/files and add blobs.
   //$suffix = $objDigitalContent -> ID . "_$objDigitalContent->Title/";
   $suffix = $objDigitalContent -> Identifier;
   //$NewDirectory = $_SERVER['DOCUMENT_ROOT'] . "/Archon/" . "files/" . $suffix;
   $NewDirectory = $root . "/files/" . $suffix;
   //fix!!!!
   //$NewContentURL = 'http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['SERVER_NAME'] . "/Archon/" . "files/" . $suffix;
   $NewContentURL = 'http'.(empty($_SERVER['HTTPS'])?'':'s').'://'.$_SERVER['SERVER_NAME'] . "/files/" . $suffix;

   $dcChanges['NewDirectory'] = $NewDirectory;
   $dcChanges['NewContentURL'] = $NewContentURL;
   foreach (array_slice($digitalContent,1) as $dlfile) {
       $path = $NewDirectory . '/' . $dlfile[Filename];
       $dcChanges['Blobs'][$dlfile[ID]] = array();
       $dcChanges['Blobs'][$dlfile[ID]]['ID'] = $dlfile[ID];
       $dcChanges['Blobs'][$dlfile[ID]]['Path'] = $path;
       $dcChanges['Blobs'][$dlfile[ID]]['DigitalContentID'] = $objDigitalContent->ID;
       $dcChanges['Blobs'][$dlfile[ID]]['Title'] = $dlfile[Title];
       $dcChanges['Blobs'][$dlfile[ID]]['Filename'] = $dlfile[Filename];
       $dcChanges['Blobs'][$dlfile[ID]]['FileTypeID'] = $dlfile[FileTypeID];
       $dcChanges['Blobs'][$dlfile[ID]]['DirectLink'] = $NewContentURL ."/". $dlfile[Filename];
   }
   $allChanges[$objDigitalContent -> ID] = $dcChanges;
}
$NewDirectories = array();
$Downloads = array();
$Copies = array();
$Deletes = array();
$Queries = array();
foreach (array_slice($allChanges,0) as $dcChanges) {
    echo "<b>Digital Content Item ".$dcChanges['DigitalContentID'] . ": " . $dcChanges['Title'] .":<br></b><ul>";
    //Need to determine if we should delete the original file: do any other Digital Content Items use it?
    if ($dcChanges['NewDirectory']) {
        $NewDirectories[] = $dcChanges['NewDirectory'];
        echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; New Directory '" . suffix($dcChanges['NewDirectory']) . "/' will be created<br><br>";
    }
    if ($dcChanges['OriginalFile']) {
        $OriginalFileSuffix = suffix($dcChanges['OriginalFile']);
        $query = "SELECT COUNT(ID) FROM `tblDigitalLibrary_Files`
                  WHERE DirectLink LIKE '%$OriginalFileSuffix%'
                  AND DigitalContentID > 0";
        $result = $_ARCHON -> mdb2 -> query($query);
        if(PEAR::isError($result)) {
            echo($query);
            trigger_error($result->getMessage(), E_USER_ERROR);
        }
        $rows = $result -> fetchAll();
        $result -> free();
        $newDest = $dcChanges['NewDirectory'] . basename($OriginalFileSuffix);
        echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '" . $OriginalFileSuffix . "' will be copied to " . suffix($dcChanges['NewDirectory']) . "/" . basename($OriginalFileSuffix) . "<br><br>";
        if (strrpos($dcChanges['OriginalFile'],'/') == strlen($dcChanges['OriginalFile']) - 1) {
           $dcChanges['OriginalFile'] = substr($dcChanges['OriginalFile'],0,-1);
        }
        if (strrpos($newDest,'/') == strlen($newDest) - 1) {
           $newDest = substr($newDest,0,-1);
        }
        //No one else is using this file, we can delete it.
        if ($rows[0]['COUNT(ID)'] <= 1) {
            $Deletes[] = $dcChanges['OriginalFile'];
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'" . $OriginalFileSuffix . "' will be deleted<br><br>";
        }
         $Copies[] = array('Original' => $dcChanges['OriginalFile'],
                           'New' => $newDest);

    }
    if ($dcChanges['NewContentURL']) {
        $Queries[$dcChanges['DigitalContentID']]['Query'] = "UPDATE tblDigitalLibrary_DigitalContent SET ContentURL='" . $dcChanges['NewContentURL'] . "'WHERE ID =" . $dcChanges['DigitalContentID'];
        $Queries[$dcChanges['DigitalContentID']]['OldContentURL'] = $dcChanges['ContentURL'];
        $Queries[$dcChanges['DigitalContentID']]['NewContentURL'] = $dcChanges['NewContentURL'];
        $Queries[$dcChanges['DigitalContentID']]['DigitalContentID'] = $dcChanges['DigitalContentID'];
        echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ContentURL " . $dcChanges['ContentURL'] . " will be changed to " . $dcChanges['NewContentURL'] . "<br><br>";
    }
    foreach ($dcChanges['Blobs'] as $blob) {
        $Downloads[] = $blob;
        echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'" . basename($blob['Path']) . "' will be downloaded to '" . suffix($blob['Path']) . "'<br>";
    }
    echo "</ul><br>";
}
    /*echo"<br>";
    echo dirname(__FILE__);
    echo "<br>";
    echo $_SERVER['DOCUMENT_ROOT'];
    echo "<br>";
    echo $root;*/

?>
        </div>
    <div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
        <a class="helplink {phrasename: &quot;header&quot;, packageid: 6, moduleid: 81} active" title="Click for help" id="headerhelplink" style="font-size:32px;color:#005e89">?</a>

        <div class="ui-dialog-buttonset"><button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false"><span class="ui-button-text" onclick="window.close();return false;">Cancel</span></button></div>
        <!-- <div class="ui-dialog-buttonset"><button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="true"><span class="ui-button-text" onClick="location.href='?p=admin/digitallibrary/convertblobs&commit=1'">Commit Changes, Retain BLOBS</span></button></div>
        -->
        <div class="ui-dialog-buttonset"><button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false"><span class="ui-button-text" onClick="location.href='?p=admin/digitallibrary/convertblobs&commit=1;&delete=1'">Commit Changes (Deletes Blobs)</span></button></div></div>
    <?


if ($_GET['commit']) {
    echo "</div></div></div>";
        ?>
        <script type="text/javascript"> document.getElementById("main").style.display  = 'none'; </script>
        <div style="display: block; z-index: 1002; outline: 0px; height: auto; width: 600px; top: 28px; left: 306px; " class="ui-dialog ui-widget ui-widget-content ui-corner-all " tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-response"><div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix"><span class="ui-dialog-title" id="ui-dialog-title-response">Admin Response</span><a href="#" class="ui-dialog-titlebar-close ui-corner-all" role="button"><span class="ui-icon ui-icon-closethick">close</span></a></div><div id="response" style="width: auto; min-height: 31.266666889190674px; height: auto; " class="ui-dialog-content ui-widget-content"><div style="padding: 6px; height: 400px; overflow: auto; color: white; background-color: rgb(51, 51, 51); font-size: 12px; font-weight: normal; background-position: initial initial; background-repeat: initial initial; "><br><br>
        <?
    echo "<b>Creating New Directories:</b><ul><br>";
    foreach($NewDirectories as $dir) {
        $oldumask = umask(0);
        if (mkdir($dir, 0777, true)) {
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Created '" . suffix($dir) . "'<br>";
        }
        else {
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Failed to create</b> '" . suffix($dir) . "'<br>";
        }
        umask($oldumask);
    }
    echo "</ul><br><b>Moving Files:</b><ul><br>";
    foreach($Copies as $copy) {
        if (copy($copy['Original'],$copy['New'])) {
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Copied '" . suffix($copy['Original']) . "' to '" . suffix($copy['New']) . "'<br>";
            if (in_array($copy['Original'],$Deletes)) {
                if (unlink($copy['Original'])) {
                    echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Deleted '" . suffix($copy['Original']) . "'<br><br>";
                }
                else {
                    echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Failed to delete</b> '" . suffix($copy['Original']) . "'<br>";
                }
            }
        }
        else {
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Failed to copy</b> '" . suffix($copy['Original']) . "' to '" . suffix($copy['New']) . "'<br>";
        }

    }
    echo "</ul><br><b>Downloading Files From Database:</b><ul><br>";
    foreach($Downloads as $blob) {
        if (downloadFile($blob['ID'],$blob['Path'])) {
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Downloaded '" . basename(suffix($blob['Path'])) . "' to '" . suffix($blob['Path']) . "'<br>";
        }
    }
    echo "</ul><br><b>Inserting Into Table tblDigitalLibrary_Files:</b><ul><br>";
    foreach($Downloads as $blob) {
        $query = "INSERT INTO tblDigitalLibrary_Files (DigitalContentID,Title,Filename,FileTypeID,DirectLink)
                  VALUES (" . $blob['DigitalContentID'] . ",'" . $blob['Title'] . "','" . $blob['Filename'] . "'," . $blob['FileTypeID'] . ",'" . $blob['DirectLink'] . "')";
        //echo "<li><br><br>" . $query . "<br><br>";
        $resolution =& $_ARCHON -> mdb2 -> exec($query);
        if(PEAR::isError($deletion)) {
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Failed to Insert row for file:</b> '" . suffix($blob['Path']) . "' and DigitalContentID '" . $blob['DigitalContentID'] . "'<br>";
            trigger_error($deletion->getMessage(), E_USER_ERROR);
        }
        else {
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Inserted row for file: '" . suffix($blob['Path']) . "' and DigitalContentID '" . $blob['DigitalContentID'] . "'<br>";
        }
    }
    if ($_GET['delete']) {
        echo "</ul><br><b>Deleting Records From Database:</b><ul><br>";
        $query = "DELETE FROM tblDigitalLibrary_Files WHERE ID = ";
        $success = "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Deleted record ";
        $failure = "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Failed to delete record</b> ";
    }
    else {
        echo "</ul><br><b>Hiding Records In Database:</b><ul><br>";
        $query = "UPDATE tblDigitalLibrary_Files SET DefaultAccessLevel = 0 WHERE ID = ";
        $success = "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hid record ";
        $failure = "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Failed to hide record</b> ";
    }
    foreach($Downloads as $blob) {
        $queryBlob = $query . $blob['ID'];
        $successBlob = $success . "for file '" . suffix($blob['Path']) . "' with ID '" . $blob['ID'] . "'<br>";
        $failureBlob = $failure . "for file '" . suffix($blob['Path']) . "' with ID '" . $blob['ID'] . "'<br>";
        $resolution =& $_ARCHON -> mdb2 -> exec($queryBlob);
        if(PEAR::isError($deletion)) {
            echo($failureBlob);
            trigger_error($deletion->getMessage(), E_USER_ERROR);
        }
        else {
            echo($successBlob);
        }
    }
    echo "</ul><br><b>Updating ContentURL's in tblDigitalLibrary_DigitalContent:</b><ul><br>";
    foreach($Queries as $Query) {
        $resolution =& $_ARCHON -> mdb2 -> exec($Query['Query']);
        if(PEAR::isError($deletion)) {
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Failed To Changed ContentURL</b> " . $Query['OldContentURL'] . " to " . $Query['NewContentURL'] . " on record with ID " . $Query['DigitalContentID'] . "<br>";
            trigger_error($deletion->getMessage(), E_USER_ERROR);
        }
        else {
            echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Changed ContentURL " . $Query['OldContentURL'] . " to " . $Query['NewContentURL'] . " on record with ID " . $Query['DigitalContentID'] . "<br>";
        }
    }
}


require_once("footer.inc.php");
?>
