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

$sessionStats = array("updates" => 0,
                      "inserts" => 0,
                      "queries" => 0,
                      "numitems" => 0,
                      "skipped" => 0,
                      "duplicates" => 0,
                      "indexType" => NULL);

echo "<h3>Report: </h3><br>";


/* ~~ Update an individual item ~~ */
if ($_REQUEST['itemidnum']) {
  $itemID = $_REQUEST['itemidnum'];
  $sessionStats["indexType"] = 'item';
  testInputValidity($itemID, 'user');
  echo "<br>Updating item $itemID...<br><br>";

  // Gets the source tables/columns for the IndexField terms
  $indexParams = getIndexSources($itemID, $sessionStats);

  if ($indexParams) {
    // Put the item ID in an array (getIndexFieldValues() expects an array)
    $itemIDArray = array($itemID);

    // Gets the new value for the index field for the item
    $indexFieldValue = getIndexFieldValues($itemIDArray, $indexParams, $sessionStats);

    // Updates current value/inserts the new value into the table
    $didUpdate = updateIndexField($indexFieldValue, $sessionStats);

    if ($sessionStats['updates'] || $sessionStats['inserts']) {
      echo "<br>Successfully indexed item $itemID.";
    } else {
      echo "<br>Item $itemID already up to date!";
    }
  }


/* ~~ Update a collection ~~ */
} elseif ($_REQUEST['collidnum']){
  $collID = $_REQUEST['collidnum'];
  testInputValidity($collID, 'user');
  echo "<br>Updating collection $collID...<br><br>";
  indexCollection($collID, $sessionStats);


/* ~~ Update everything ~~ */
} elseif ($_REQUEST['indexall']){
  $query = "SELECT ID, Title FROM tblCollections_Collections";
  $result = runQuery($query, &$sessionStats);
  $collectionIDs = $result -> fetchAll();
  $result -> free();

  // Loop through every collection, indexing them one at a time
  foreach ($collectionIDs as $collID) {
    echo "<br><b>Updating collection ".$collID['Title'];
    echo "</b><br><br>";
    indexCollection($collID['ID'], &$sessionStats);
  }

  echo "<br><br>Successfully indexed all collections!<br>";

} else {
  echo "<b>ERROR: </b> You must select something to index.";
}

// Footer status report
echo "<br><br>==============================<br>";
if ($sessionStats["skipped"]) {
  echo $sessionStats["skipped"] . " items skipped because of null IndexField value.<br>";
}
echo $sessionStats["duplicates"] . " items already up to date.<br>";
echo $sessionStats["inserts"] . " items indexed (new user field created).<br>";
echo $sessionStats["updates"] . " items updated.<br>";
echo "Used " . $sessionStats["queries"] . " queries.";



// Given a collection ID number this updates the index terms for all its items
function indexCollection($collID, &$sessionStats) {
  $sessionStats["indexType"] = 'collection';
  $sessionStats["numitems"] = 0;
  $indexParams = getIndexSources($collID, $sessionStats);

  if ($indexParams) {
    // Get all the item IDs from the collection
    $query = "SELECT ID FROM tblCollections_Content WHERE CollectionID=$collID";
    $result = runQuery($query, $sessionStats);
    $rows = $result -> fetchAll();

    $itemIDArray = array();
    foreach ($rows as $item) {
      $itemIDArray[] = $item['ID'];
    }

    $indexFieldValues = getIndexFieldValues($itemIDArray, $indexParams, $sessionStats);
    updateIndexField($indexFieldValues, $sessionStats);

    echo "<br>Successfully indexed collection $collID (".$sessionStats["numitems"]." items).<br>";
  }
}


// Builds and runs a query that gets each index field and concatenates the
// keywords into one term
function getIndexFieldValues($id, $params, &$sessionStats) {
  $otherTables = $params['tables'];
  $userFields = $params['userFields'];
  $selectOTs = "";
  $selectUFs = "";
  $idList = implode(',', array_map('intval', $id));


  // ----- Start Query ----- //
  if ($otherTables) {
    $selectOTs = "SELECT ID AS ContentID, CONCAT_WS('; ', ";
    $selectOTs .= implode(", ", array_keys($otherTables));
    $selectOTs .= ") AS Value FROM ";
    $selectOTs .= implode(", ", array_values(array_unique($otherTables)));
    $selectOTs .= " WHERE ID IN (" . $idList . ")";
  }

  if ($userFields) {
    $ufs = array();
    foreach ($userFields as $val) {
      $ufs[] = "Title ='" . $val . "'";
    }
    $selectUFs = "SELECT ContentID, Value FROM tblCollections_UserFields WHERE (";
    $selectUFs .= implode("OR ", $ufs);
    $selectUFs .= ") AND ContentID IN (" . $idList . ")";
  }

  $query = "SELECT tmp.ContentID, GROUP_CONCAT(tmp.Value SEPARATOR '; ') AS IndexField FROM ((";
  $query .= $selectOTs . ") UNION (" . $selectUFs . ")) AS tmp GROUP BY ContentID";
  // ----- End Query ----- //


  $result = runQuery($query, $sessionStats);
  $rows = $result -> fetchAll();
  $indexFieldVals = array();

  // Put results into an array
  foreach ($rows as $item) {
    $sessionStats['numitems']++;
    $idxflds = str_replace("'", "''", $item['IndexField']); // Escape apostrophes
    if ($idxflds) {
      $indexFieldVals[$item['ContentID']] = $idxflds;
    } else {
      $sessionStats['skipped']++;
    }
  }

  return $indexFieldVals;
}


// Fetches the data in the RevisionHistory field of the collection and returns
// an array containing the user fields the other tables
function getIndexSources($idNum, &$sessionStats) {
  if ($sessionStats["indexType"] == 'item') {
    $query = "SELECT RevisionHistory
              FROM tblCollections_Collections, tblCollections_Content
              WHERE tblCollections_Content.CollectionID = tblCollections_Collections.ID
              AND tblCollections_Content.ID=$idNum";
  } else {
    $query = "SELECT RevisionHistory
              FROM tblCollections_Collections
              WHERE ID=$idNum";
  }

  $result = runQuery($query, $sessionStats);
  $row = $result -> fetchRow();
  $result -> free();

  if (!$row['RevisionHistory']) {
    echo "No indexing parameters specified for " . $sessionStats["indexType"] . " $idNum<br>";
    return false;
  }

  $indexFieldSources = array("userFields" => array(), "tables" => array());
  $revisionList = explode(', ', $row['RevisionHistory']);
  echo "Indexing fields: <br>";

  // If there is an '=' character in the string, we know it is a user field.
  // If not, separate out the table and put it in the $tables array.
  foreach ($revisionList as $indexField) {
    echo "$indexField<br>";
    $arr = explode('.', $indexField);
    $arr2 = explode('=', $arr[1]);
    $table = $arr[0];
    $field = $arr2[0];

    $valid = verifyParameter($table, $field, &$sessionStats, $arr2[1]);

    if ($valid) {
      if ($arr2[1]) {
        $indexFieldSources["userFields"][] = $arr2[1];
      } else {
        $indexFieldSources["tables"][$indexField] = $table;
      }
    }
  }

  return $indexFieldSources;
}


// Runs a query to the database, returns result of query
function runQuery($q, &$sessionStats) {
  global $_ARCHON;

  // echo "<br><br>".$q."<br>";

  $result = $_ARCHON -> mdb2 -> query($q);
  if(PEAR::isError($result)) {
    echo "<br>Unable to index ".$sessionStats["indexType"];
    echo ", make sure that you are defining fields correctly in the
          collection's Revision History field.<br>";
    trigger_error($result->getMessage(), E_USER_ERROR);
  }

  $sessionStats["queries"]++;
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
      $msg = "Unable to retrieve indexing parameters for the given ID number.";
    }
  }

  if ($err) {
    echo "<b>ERROR: </b>$msg<br>";
    exit(1);
  }
}


// Finds whether an item already has an IndexField and either inserts or updates it
function updateIndexField($indexFieldValues, &$sessionStats) {
  $idList = implode(',', array_keys($indexFieldValues));
  $query = "SELECT ID, ContentID, Value FROM tblCollections_UserFields
            WHERE ContentID IN ($idList) AND Title LIKE 'IndexField'";
  $result = runQuery($query, $sessionStats);
  $rows = $result -> fetchAll();

  $currentVals = array();
  $contentID2ufID = array();
  foreach ($rows as $r) {
    $idxflds = str_replace("'", "''", $r['Value']);
    $currentVals[$r['ContentID']] = $idxflds;
    $contentID2ufID[$r['ContentID']] = $r['ID'];
  }

  // Don't update identical values
  $uniqueIFVs = array_diff_assoc($indexFieldValues, $currentVals);
  $sessionStats["duplicates"] += count($indexFieldValues) - count($uniqueIFVs);

  // Update where there is already an entry
  $update = array_intersect_key($uniqueIFVs, $currentVals);
  $sessionStats["updates"] += count($update);

  // Insert where there isn't an entry
  $insert = array_diff_key($uniqueIFVs, $update);
  $sessionStats["inserts"] += count($insert);

  foreach ($update as $id => $val) {
    $query = "UPDATE tblCollections_UserFields SET Value='$val'
              WHERE ID=" . $contentID2ufID[$id];
    runQuery($query, $sessionStats);
  }

  foreach ($insert as $id => $val) {
    $query = "INSERT INTO tblCollections_UserFields (ContentID, Title, Value, EADElementID)
              VALUES($id, 'IndexField', '$val', 15)";
    runQuery($query, $sessionStats);
  }
}


// Checks to see whether the parameters entered in RevisionHistory are valid
function verifyParameter($table, $field, &$sessionStats, $value = NULL) {
  // Check if the table exists
  $result = runQuery("SHOW TABLES LIKE '$table'", $sessionStats);
  $row = $result->fetchRow();
  if (count($row) == 0) {
    echo "<b>Warning: </b>Table \"$table\" does not exist.<br>";
    return false;
  }

  // Check if the column exists
  $result = runQuery("SHOW COLUMNS FROM $table LIKE '$field'", $sessionStats);
  $row = $result->fetchRow();
  if (count($row) == 0) {
    echo "<b>Warning: </b>Column \"$field\" does not exist.<br>";
    return false;
  }

  // Check if the user field exists
  if ($value) {
    $result =  runQuery("SELECT ID FROM $table WHERE Title = '$value'", $sessionStats);
    $row = $result->fetchRow();
    if (count($row) == 0) {
      echo "<b>Warning: </b>User field \"$value\" does not exist.<br>";
      return false;
    }
  }

  return true;
}

?>
