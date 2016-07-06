<?php
/**
* Utility for indexing search terms
*
* packages\core\admin\indexutil.php
*
* @package Archon
* @subpackage AdminUI
* @author Caleb Braun, 6/28/2016
*/

isset($_ARCHON) or die();

$qc = 0;  //query counter
$ic = 0;  //item counter

echo "<h3>Report: </h3><br>";


/* ~~ Update an individual item ~~ */
if ($_REQUEST['itemidnum']) {
  $itemID = $_REQUEST['itemidnum'];
  testInputValidity($itemID, 'user');
  echo "<br>Updating item $itemID...<br><br>";

  // Gets the source tables/columns for the IndexField terms
  $indexParams = getIndexSources($itemID, 'item', $qc);

  if ($indexParams) {
    // Gets the new value for the index field for the item
    $indexFieldValue = getIndexFieldValue($itemID, $indexParams, $qc);

    // Updates current value or inserts the new value into the table
    $didUpdate = updateIndexField($itemID, $indexFieldValue, $ic, $qc);

    if ($didUpdate) {
      echo "<br>Successfully indexed item $itemID.";
    } else {
      echo "<br>Item $itemID already up to date!";
    }
  } else {
    testInputValidity($itemID, 'database');
  }


/* ~~ Update a collection ~~ */
} elseif ($_REQUEST['collidnum']){
  $collID = $_REQUEST['collidnum'];
  testInputValidity($collID, 'user');
  echo "<br>Updating collection $collID...<br><br>";
  indexCollection($collID, $ic, $qc);


/* ~~ Update everything ~~ */
} elseif ($_REQUEST['indexall']){
  $query = "SELECT ID, Title FROM tblCollections_Collections";
  $result = runQuery($query, &$qc);
  $collectionIDs = $result -> fetchAll();
  $result -> free();

  // Loop through every collection, indexing them one at a time
  foreach ($collectionIDs as $collID) {
    echo("<br><b>Updating collection ".$collID['Title']);
    echo "</b><br><br>";
    indexCollection($collID['ID'], &$ic, &$qc);
  }

  echo "<br><br>Successfully indexed all collections!<br>";

} else {
  echo "<b>ERROR: </b> You must select something to index.";
}

// Status report
echo "<br><br>==============================<br>";
$s = ($ic == 1) ? '' : 's';
$format = "Updated %d item%s using %d queries.";
echo sprintf($format, $ic, $s, $qc);



// Given a collection ID number this updates the index terms for all its items
function indexCollection($collID, &$ic, &$qc) {
  $indexParams = getIndexSources($collID, 'collection', $qc);

  if ($indexParams) {
    // Get all the item IDs from the collection
    $query = "SELECT ID FROM tblCollections_Content WHERE CollectionID=$collID";
    $result = runQuery($query, $qc);
    $rows = $result -> fetchAll();

    // Loop through IDs
    foreach ($rows as $collectionItem) {
      $itemID = $collectionItem['ID'];
      $indexFieldValue = getIndexFieldValue($itemID, $indexParams, $qc);
      updateIndexField($itemID, $indexFieldValue, $ic, $qc);
    }
    echo "<br>Successfully indexed collection $collID.<br>";

  } else {
    testInputValidity($rows, 'database'); // Throws error
  }
}


// Builds and runs a query that gets each index field and concatenates the
// keywords into one term
function getIndexFieldValue($id, $params, &$qc) {
  $otherTables = $params['tables'];
  $userFields = $params['userFields'];
  $selectOTs = "";
  $selectUFs = "";

  if ($otherTables) {
    $selectOTs = ", (SELECT CONCAT_WS('; ', ";
    $selectOTs .= implode(", ", array_keys($otherTables));
    $selectOTs .= ") FROM ";
    $selectOTs .= implode(", ", array_values(array_unique($otherTables)));
    $selectOTs .= " WHERE ID= $id)";
  }

  if ($userFields) {
    $a = array();
    foreach ($userFields as $val) {
      $a[] = "Title ='" . $val . "'";
    }
    $selectUFs = ", (SELECT GROUP_CONCAT(Value SEPARATOR '; ') FROM tblCollections_UserFields WHERE (";
    $selectUFs .= implode("OR ", $a);
    $selectUFs .= ") AND ContentID= $id)";
  }

  $query = "SELECT CONCAT_WS('; '" . $selectOTs . $selectUFs . ")  AS IndexField";

  $result = runQuery($query, $qc);
  $row = $result -> fetchRow();
  $indexFieldVal = $row["IndexField"];
  $indexFieldVal = str_replace("'", "''", $indexFieldVal); // Escape apostrophes

  return $indexFieldVal;
}


// Fetches the data in the RevisionHistory field of the collection and returns
// an array of the user fields and one of the other tables
function getIndexSources($idNum, $idType, &$qc) {
  if ($idType == 'item') {
    $query = "SELECT RevisionHistory
              FROM tblCollections_Collections, tblCollections_Content
              WHERE tblCollections_Content.CollectionID = tblCollections_Collections.ID
              AND tblCollections_Content.ID=$idNum";
  } else {
    $query = "SELECT RevisionHistory
              FROM tblCollections_Collections
              WHERE ID=$idNum";
  }

  $result = runQuery($query, $qc);
  $row = $result -> fetchRow();
  $result -> free();
  if ($row['RevisionHistory'] == NULL) return false;

  echo "Indexing fields: <br>";
  $revisionList = explode(', ', $row['RevisionHistory']);
  $indexFieldSources = array("userFields" => array(), "tables" => array());

  // If there is an '=' character in the string, we know it is a user field.
  // If not, separate out the table and put it in the $tables array.
  foreach ($revisionList as $indexField) {
    echo "$indexField<br>";
    $arr = explode('.', $indexField);
    $table = $arr[0];
    $arr2 = explode('=', $arr[1]);
    if ($arr2[1]) {
      $indexFieldSources["userFields"][] = $arr2[1];
    } else {
      $indexFieldSources["tables"][$indexField] = $table;
    }
  }

  return $indexFieldSources;
}


// Runs a query to the database, returns result of query
function runQuery($q, &$qc) {
  global $_ARCHON;

  $result = $_ARCHON -> mdb2 -> query($q);
  if(PEAR::isError($result)) {
    trigger_error($result->getMessage(), E_USER_ERROR);
  }
  $qc++;
  return $result;
}


// Makes sure user input and database results are valid
function testInputValidity($input, $inputSource) {
  $err = false;
  $msg = '';

  if ($inputSource == 'user') {
    // Make sure they entered a number
    if (!ctype_digit($input)) {
      $err = true;
      $msg = "Please enter a number.";
    }
  }

  if ($inputSource == 'database') {
    // Make sure it returned a value
    if ($input == NULL) {
      $err = true;
      $msg = "Unable to retrieve indexing parameters for the given ID number: $input";
    }
  }

  if ($err) {
    echo "<b>ERROR: </b>$msg<br>";
    exit(1);
  }
}


// Finds whether an item already has an IndexField and either inserts or updates it
function updateIndexField($idNumber, $indexFieldValue, &$ic, &$qc) {
  $query = "SELECT ID, Value FROM tblCollections_UserFields
            WHERE ContentID=$idNumber AND Title LIKE 'IndexField'";
  $result = runQuery($query, $qc);
  $entryID = $result -> fetchRow();
  $exists = $entryID['ID'];

  if($exists) {
    // Check if values are identical and if so don't perform update query
    if ($entryID['Value'] == str_replace("''", "'", $indexFieldValue)) return false;

    $updateQ = "UPDATE tblCollections_UserFields SET Value='$indexFieldValue'
                WHERE ID=$exists";
  } else {
    $updateQ = "INSERT INTO tblCollections_UserFields (ContentID, Title, Value, EADElementID)
                VALUES($idNumber, 'IndexField', '$indexFieldValue', 15)";
  }

  $updateResult = runQuery($updateQ, $qc);
  $ic++;

  return true;
}


?>
