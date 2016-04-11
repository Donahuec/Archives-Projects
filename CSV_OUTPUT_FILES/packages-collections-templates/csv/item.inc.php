<?php
/**
 * @author Caitlin Donahue
 * @package Archon
 * 
 * This goes through and creates a csv row for an object
 * it uses two queries to the database
 * and the $objCollection item, as well as the current $Content array. 
 * to learn about these reference packages->Collections->lib->collections.inc.php
 * There is also information passed to this page from packages->colections->templates->csv->collection.inc.php via a $_SESSION item
 * NOTE: If you do not have a dateadded field in tblcollections_content (which is something that we added) there are two lines that you need to modify/remove 
 * These lines are marked in the comments
 */

isset($_ARCHON) or die();

//These variables wil be referenced when prining the row
$userfields = $_SESSION['csvUserFields'];
$collectionID = $objCollection->getString('CollectionIdentifier');
$strCreators = "";
$hierarchyToPrint = ""; 
$hiddenInfoArray = array();

//Query to get information no contained within the $Content array
//IF YOU DO NOT HAVE A DATEADDED FIELD REMOVE dateadded FROM THIS QUERY
$hiddenInfoQuery = mysql_query('SELECT LevelContainerID, RootContentID, ContainsContent, SortOrder, dateadded FROM tblCollections_Content WHERE ID = '.$Content['ID']);
if($hiddenInfoQuery)
{
    $hiddenInfoArray = mysql_fetch_array($hiddenInfoQuery);		
}
else {
    $hiddenInfoArray = array('ERROR','ERROR','ERROR','ERROR','ERROR');
}

//Query that gets all of the information relating to IDs for items that share a RootContentID with the current item.
$parentIDQuery = mysql_query('SELECT ID, ParentID, LevelContainerIdentifier FROM tblcollections_content WHERE RootContentID = '.$hiddenInfoArray[1].' OR RootContentID = 0');
$parentIDArray = array();
if($parentIDQuery){
    while($row = mysql_fetch_object($parentIDQuery)){
        $rowID = $row->ID;
        $parentIDArray[$rowID] = $row;
    }
}

//Gets a hierarcy of the item's position in the collection, from the root Content ID to the current item. Will print the LevelContainerIdentiier
$curID = $Content['ID'];
$hierarchy = array();
while($parentIDArray[$curID]->ParentID != ""){
    $parent = $parentIDArray[$curID]->ParentID;
    //if you want it to print the ID instead, remove this line, and change $parentlcid with $parent in the next line.
    $parentlcid = $parentIDArray[$curID]->LevelContainerIdentifier;
    array_push($hierarchy, $parentlcid);
    $curID = $parent;
}

//turns the hierarchy array into a printable string with the hierarchy   
while($hierarchy){
    if ($hierarchyToPrint != ""){
        $hierarchyToPrint = $hierarchyToPrint . "/";
    }
    $hierarchyToPrint = $hierarchyToPrint . array_pop($hierarchy);
}

//here we start printing to the csv file 

echo '
';
echo '"'.$Content['ID'].'",';
echo '"'.$collectionID.'",';
echo '"'.$hierarchyToPrint.'",';
echo '"'.$Content['LevelContainer'].'",';
echo '"'.$Content['LevelContainerIdentifier'].'",';
echo '"'.$Content['Title'].'"'.',';
echo '"'.$Content['PrivateTitle'].'",';
echo '"'.$Content['Date'].'"'.',';
echo '"'.$Content['Description'].'",';
//RootContentID
echo '"'.$hiddenInfoArray[1].'"'.',';
echo '"'.$Content['ParentID'].'",';
//ContainsContent
echo '"'.$hiddenInfoArray[2].'",';
//SortOrder
echo '"'.$hiddenInfoArray[3].'",';
echo '"'.$Content['Enabled'].'",';
//dateadded -- IF YOU DO NOT HAVE A DATE ADDED FIELD, REMOVE THE FOLLOWING LINE
echo '"'.$hiddenInfoArray[4].'",';
//loops through and makes sure the creators are formatted correctly
if ($Content['Creators']){
    foreach($Content['Creators'] as $c){
        if ($strCreators != "") {
            $strCreators = $strCreators."; ";
        }
        $strCreators = $strCreators . $c->Name;
    }
}
echo '"'.$strCreators.'",';

//Makes sure userfields are formatted
foreach ($userfields as $fieldTitle) {
    echo '"';
    foreach($Content['UserFields'] as $contentuf){
        if ($contentuf['Title'] == $fieldTitle){
            echo $contentuf['Value'];
        }
    }
    echo '",';
}
?>