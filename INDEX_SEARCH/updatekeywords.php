<?php
/**
* packages/collections/admin/updatekeywords.php
*
* Description: Updates IndexField for items upon save
*
* @package Archon
* @subpackage AdminUI
* @author Caleb Braun, 7/8/2016
*/

isset($_ARCHON) or die();

$itemID = "$objCollectionContent->ID";

// Gets the source tables/columns for the IndexField terms
$indexParams = getIndexSources($itemID);

if ($indexParams) {
  // Gets the new value for the index field for the item
  $indexFieldValue = getIndexFieldValues($itemID, $indexParams);

  // Updates current value/inserts the new value into the table
  updateIndexField($itemID, $indexFieldValue);
}


// Builds and runs a query that gets each index field and concatenates the
// keywords into one term
function getIndexFieldValues($id, $params) {
  // Determine if the item has a creator
  $query = "SELECT * FROM tblCollections_CollectionContentCreatorIndex WHERE CollectionContentID = $id";
  $row = runQuery($query);
  $creators = $row['CreatorID'];

  $otherTables = $params['tables'];
  $userFields = $params['userFields'];
  $selectOTs = "";
  $selectUFs = "";

  // ----- Start Query ----- //
  if ($otherTables) {
    $selectOTs = " (SELECT ID AS ContentID, CONCAT_WS('; ', ";
    $selectOTs .= implode(", ", array_keys($otherTables));
    $selectOTs .= ") AS Value FROM ";
    $selectOTs .= implode(", ", array_values(array_unique($otherTables)));
    $selectOTs .= " WHERE ID=" . $id . ") ";
    $selectOTs .= ($userFields or $creators) ? "UNION" : "";
  }

  if ($userFields) {
    $ufs = array();
    foreach ($userFields as $val) {
      $ufs[] = "Title ='" . $val . "'";
    }
    $selectUFs = " (SELECT ContentID, Value FROM tblCollections_UserFields WHERE (";
    $selectUFs .= implode("OR ", $ufs);
    $selectUFs .= ") AND ContentID = " . $id . ") ";
    $selectUFs .= ($creators) ? "UNION" : "";
  }

  if ($creators) {
    $selectCRs = " (SELECT a.CollectionContentID ContentID, b.Name Value
    FROM tblCollections_CollectionContentCreatorIndex AS a
    JOIN tblCreators_Creators AS b ON a.CreatorID = b.ID
    AND a.CollectionContentID = $id) ";
  }

  $query = "SELECT tmp.ContentID, GROUP_CONCAT(tmp.Value SEPARATOR '; ') AS IndexField FROM (";
  $query .= $selectOTs . $selectUFs . $selectCRs . ") AS tmp GROUP BY ContentID";
  // ----- End Query ----- //

  $row = runQuery($query);
  $indexFieldVal = str_replace("'", "''", $row['IndexField']);

  return $indexFieldVal;
}


// Fetches the data in the RevisionHistory field of the collection and returns
// an array containing the user fields the other tables
function getIndexSources($idNum) {
  $query = "SELECT RevisionHistory
            FROM tblCollections_Collections, tblCollections_Content
            WHERE tblCollections_Content.CollectionID = tblCollections_Collections.ID
            AND tblCollections_Content.ID=$idNum";
  $row = runQuery($query);

  if (!$row['RevisionHistory']) {
    return false;
  }

  $indexFieldSources = array("userFields" => array(), "tables" => array());
  $revisionList = explode(', ', $row['RevisionHistory']);

  // If there is an '=' character in the string, we know it is a user field.
  // If not, separate out the table and put it in the $tables array.
  foreach ($revisionList as $indexField) {
    $arr = explode('.', $indexField);
    $arr2 = explode('=', $arr[1]);
    $table = $arr[0];
    $field = $arr2[0];

    if ($arr2[1]) {
      $indexFieldSources["userFields"][] = $arr2[1];
    } else {
      $indexFieldSources["tables"][$indexField] = $table;
    }
  }

  return $indexFieldSources;
}


// Runs a query to the database, returns result of query
function runQuery($q) {
  global $_ARCHON;

  $result = $_ARCHON -> mdb2 -> query($q);
  if(PEAR::isError($result)) {
    $_ARCHON->declareError("Error: Invalid index terms for this collection.");
    $err = $_ARCHON->Error;
    $_ARCHON->AdministrativeInterface->sendResponse($err, $arrIDs, $_ARCHON->Error, false, $location);
    return;
  }

  $r = $result -> fetchRow();
  return $r;
}


// Finds whether an item already has an IndexField and either inserts or updates it
function updateIndexField($itemid, $indexFieldValue) {
  $query = "SELECT ID, Value FROM tblCollections_UserFields
            WHERE ContentID = $itemid AND Title LIKE 'IndexField'";
  $row = runQuery($query);

  if (!indexFieldValue);

  if($row['Value']) {
    $idxfld = str_replace("'", "''", $row['Value']);
    if ($idxfld != $indexFieldValue) {
      $query = "UPDATE tblCollections_UserFields SET Value='$indexFieldValue'
                WHERE ID=" . $row['ID'];
      runQuery($query);
    }
  } else {
    $query = "INSERT INTO tblCollections_UserFields (ContentID, Title, Value, EADElementID)
              VALUES($itemid, 'IndexField', '$indexFieldValue', 15)";
    runQuery($query);
  }
}

?>
