<?php
/**
 * The CSV template is created by Caitlin Donahue
 * it will give a downloadable csv file containing information about each item in the collection.
 * 
 * 
 * This is based off of the EAD template. The following are the comments for that
 * Collection-level template for finding aid output
 *
 * The variable:
 *
 *  $objCollection
 *
 * is an instance of a Collection object, with its properties
 * already loaded when this template is referenced.
 *
 * Refer to the Collection class definition in lib/collection.inc.php
 * for available properties and methods.
 *
 * The Archon API is also available through the variable:
 *
 *  $_ARCHON
 *
 * Refer to the Archon class definition in lib/archon.inc.php
 * for available properties and methods.
 *
 * @package Archon
 * @author Chris Rishel, Bill Parod, Paul Sorensen, Chris Prom
 */
isset($_ARCHON) or die();
// to avoid issues with getString if repository does not exist
$objCollection->Repository = $objCollection->Repository ? $objCollection->Repository : New Repository();

//start a session to pass userfield data to item.inc.php
session_start();

//this gets which userfields are required for this collecion
$userFieldsResult = mysql_query('SELECT DISTINCT u.Title FROM tblcollections_userfields as u LEFT JOIN tblcollections_content as c ON u.ContentID = c.ID WHERE c.CollectionID ='.$objCollection->getString('ID'));
$ufret = array();
if($userFieldsResult)
{
   
    while($row = mysql_fetch_object($userFieldsResult))
	$ufret[] = $row->Title;   
}
else {
    $ufret = array('ERROR IN USER DEFINED FIELDS');
}

$_SESSION['csvUserFields'] = $ufret;


//creates the title line for the csv file. if you do not want a title row, you can remove this line
//IF YOU DO NOT HAVE A DATEADDED FIELD REMOVE THAT FIELD FROM THIS LIST
echo '"ArchonID","CollectionID","Hierarchy Root->Item","LevelContainerID","LevelContainerIdentifier","Title","PrivateTitle","Date","Description","RootContentID","ParentID","ContainsContent","SortOrder","Enabled","DateAdded","Creators",';
//these are the userfields
foreach ($ufret as $userFieldEntry) {
    echo '"'.$userFieldEntry.'",';
}
?>