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
                      "warnings" => 0,
                      "errors" => 0,
                      "indexType" => NULL);

ob_start();

/* ~~ Update an individual item ~~ */
if ($_REQUEST['itemidnum']) {
  $itemID = $_REQUEST['itemidnum'];
  $sessionStats["indexType"] = 'item';
  echo "<br>Updating item $itemID...<br><br>";

  if (!(testInputValidity($itemID, 'user'))) {
    generateStatusReport($sessionStats);
    exit(1);
  }

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
      echo "<br>Successfully indexed item $itemID.</font>";
    } elseif (!$sessionStats['errors']) {
      echo "<br><font color = 'limegreen'>Item $itemID already up to date!</font>";
    }
  }


/* ~~ Update a collection ~~ */
} elseif ($_REQUEST['collidnum']){
  $collID = $_REQUEST['collidnum'];
  $sessionStats["indexType"] = 'collection';
  echo "<br>Updating collection $collID...<br><br>";

  if (!(testInputValidity($collID, 'user'))) {
    echo $collID;
    generateStatusReport($sessionStats);
    exit(1);
  }

  indexCollection($collID, $sessionStats);


/* ~~ Update everything ~~ */
} elseif ($_REQUEST['indexall']){
  $sessionStats["indexType"] = 'all';
  $query = "SELECT ID, Title FROM tblCollections_Collections";
  $collectionIDs = runQuery($query, &$sessionStats);

  // Loop through every collection, indexing them one at a time
  foreach ($collectionIDs as $collID) {
    indexCollection($collID['ID'], &$sessionStats);
  }

  if (!$sessionStats['errors']) {
    echo "<br><br><b>Successfully indexed all collections!</font></b><br>";
  }

} else {
  echo "ERROR: You must select something to index.";
  $sessionStats['errors']++;
}

generateStatusReport($sessionStats);



// Given a collection ID number this updates the index terms for all its items
function indexCollection($collID, &$sessionStats) {
  $sessionStats["numitems"] = 0;
  $indexParams = getIndexSources($collID, $sessionStats);

  if ($indexParams) {
    // Get all the item IDs from the collection
    $query = "SELECT ID FROM tblCollections_Content WHERE CollectionID=$collID";
    $rows = runQuery($query, $sessionStats);

    $itemIDArray = array();
    foreach ($rows as $item) {
      $itemIDArray[] = $item['ID'];
    }
    if (!$itemIDArray) return false;

    $indexFieldValues = getIndexFieldValues($itemIDArray, $indexParams, $sessionStats);
    updateIndexField($indexFieldValues, $sessionStats);

    echo "Successfully indexed collection $collID (";
    echo $sessionStats["numitems"]." items).</font><br>";

    return true;
  } else {
    return false;
  }
}


// Prints out report displaying the stats for this script
function generateStatusReport(&$sessionStats) {
  // Put status report at top
  $report = "<h3>Report: </h3><br><br>";
  if ($sessionStats["skipped"]) {
    $report .= plural($sessionStats["skipped"], "item");
    $report .= " skipped because of null IndexField value.<br>";
  }
  $report .= plural($sessionStats["duplicates"], "item") . " already up to date.<br>";
  $report .= plural($sessionStats["inserts"], "new item") . " indexed.<br>";
  $report .= plural($sessionStats["updates"], "item") . " updated.<br>";
  if ($sessionStats["warnings"]) {
    $report .= "<font color='DarkOrange'>";
    $report .= plural($sessionStats["warnings"], "warning") . ".</font><br>";
  }
  if ($sessionStats["errors"]) {
    $report .= "<font color='Red'><b>" . plural($sessionStats["errors"], "error");
    $report .= "</b></font>. Please correct the errors below and try again.<br>";
  }
  $report .= str_repeat("_", 50)."<br><br>";

  // Add the rest of the output after (with color!)
  $output = $report . ob_get_clean();
  $output = str_replace("Successfully", "<font color = 'limegreen'>Successfully", $output);
  $output = str_replace("Warning:", "<b><font color = 'DarkOrange'>Warning: </font></b>", $output);
  $output = str_replace("ERROR:", "<b><font color = 'Red'>ERROR: </font></b>", $output);
  echo $output;

  // Footer
  echo "<br><br>".str_repeat("_", 50)."<br>";
  echo "Used " . $sessionStats["queries"] . " queries.";
}


// Builds and runs a query that gets each index field and concatenates the
// keywords into one term
function getIndexFieldValues($id, $params, &$sessionStats) {
  $otherTables = $params['tables'];
  $userFields = $params['userFields'];
  $creators = $params['hasCreators'];
  $selectOTs = ""; // Other tables, usually tblCollections_Content
  $selectUFs = ""; // User fields, always tblCollections_UserFields

  $idList = implode(',', array_map('intval', $id));

  // ----- Start Query ----- //
  if ($otherTables) {
    $selectOTs = " (SELECT ID AS ContentID, CONCAT_WS('; ', ";
    $selectOTs .= implode(", ", array_keys($otherTables));
    $selectOTs .= ") AS Value FROM ";
    $selectOTs .= implode(", ", array_values(array_unique($otherTables)));
    $selectOTs .= " WHERE ID IN (" . $idList . ")) ";
    $selectOTs .= ($userFields or $creators) ? "UNION" : "";
  }

  if ($userFields) {
    $ufs = array();
    foreach ($userFields as $val) {
      $ufs[] = "Title ='" . $val . "'";
    }
    $selectUFs = " (SELECT ContentID, Value FROM tblCollections_UserFields WHERE (";
    $selectUFs .= implode("OR ", $ufs);
    $selectUFs .= ") AND ContentID IN (" . $idList . "))";
    $selectUFs .= ($creators) ? "UNION" : "";
  }

  if ($creators) {
    $selectCRs = " (SELECT a.CollectionContentID ContentID, b.Name Value
    FROM tblCollections_CollectionContentCreatorIndex AS a
    JOIN tblCreators_Creators AS b ON a.CreatorID = b.ID
    AND a.CollectionContentID IN (" . $idList . ")) ";
  }

  $query = "SELECT tmp.ContentID, GROUP_CONCAT(tmp.Value SEPARATOR '; ') AS IndexField FROM (";
  $query .= $selectOTs . $selectUFs . $selectCRs . ") AS tmp GROUP BY ContentID";
  // ----- End Query ----- //


  $rows = runQuery($query, $sessionStats);
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
  $collectionID = $idNum;
  $verbose = ($sessionStats['indexType'] != 'all');
  $indexFieldSources = array( "userFields" => array(),
                              "tables" => array(),
                              "hasCreators" => false);

  // Query depends on if we are givin collection id or item id
  if ($sessionStats["indexType"] == 'item') {
    $query = "SELECT RevisionHistory, CollectionID
              FROM tblCollections_Collections, tblCollections_Content
              WHERE tblCollections_Content.CollectionID = tblCollections_Collections.ID
              AND tblCollections_Content.ID=$idNum";
  } else {
    $query = "SELECT RevisionHistory
              FROM tblCollections_Collections
              WHERE ID=$idNum";
  }

  $row = runQuery($query, $sessionStats, true);

  if (!$row) {
    echo "ERROR: " . ucfirst($sessionStats['indexType']) . " does not exist.";
    $sessionStats['errors']++;
    return false;

  } elseif ($row['RevisionHistory']) {
    if ($sessionStats['indexType'] == 'item') {
      $collectionID = $row['CollectionID'];
    }

    $revisionList = explode(', ', $row['RevisionHistory']);
    if ($verbose) echo "Indexing: <br>";

    // If there is an '=' character in the string, we know it is a user field.
    // If not, we are only accepting tblCollections_Content.
    foreach ($revisionList as $indexField) {
      $arr = explode('.', $indexField);
      $arr2 = explode('=', $arr[1]);
      $table = $arr[0];
      $field = $arr2[0];

      $valid = verifyParameter($table, $field, &$sessionStats, $arr2[1]);

      if ($valid) {
        if ($verbose)  {
          echo "$indexField<br>";
        }
        if ($arr2[1]) {
          $indexFieldSources["userFields"][] = $arr2[1];
        } else {
          $indexFieldSources["tables"][$indexField] = $table;
        }
      } else {
        echo "Skipping index term: \"$indexField\" in collection $collectionID.<br>";
        $sessionStats['warnings']++;
      }
    }

  } else {
    if ($verbose) {
      echo "ERROR: No index terms specified for " . $sessionStats['indexType'];
      echo " $idNum. Please add terms to the Revision History field of this
            collection and try again.";
      $sessionStats["errors"]++;
    }
    return false;
  }

  if (!($indexFieldSources["userFields"] or $indexFieldSources["tables"])) {
      echo "ERROR: The index terms for collection $collectionID are invalid.
            Please correct and try again.<br>";
      $sessionStats["errors"]++;
      return false;
  }

  // Determine if any items in this collection have creators
  $query = "SELECT DISTINCT b.CollectionID creators
            FROM tblCollections_Content b, tblCollections_CollectionContentCreatorIndex c
            WHERE b.CollectionID = $collectionID
            AND c.CollectionContentID = b.ID";

  $row = runQuery($query, $sessionStats, true);
  if ($row) {
    $indexFieldSources["hasCreators"] = ($row['creators'] != NULL);
  }

  return $indexFieldSources;
}


// Adds an s if plural.  Ex. plural(3, "hat") returns "3 hats".
function plural($n, $s) {
  if ($n != 1) {
    return "$n ".$s."s";
  } else {
    return "1 $s";
  }
}


// Runs a query to the database, returns result of query
function runQuery($q, &$sessionStats, $getRow = false) {
  global $_ARCHON;

  $result = $_ARCHON -> mdb2 -> query($q);
  $sessionStats["queries"]++;
  if(PEAR::isError($result)) {
    echo "ERROR: Unable to index ".$sessionStats["indexType"];
    echo ". There is a problem with the following query:<br><br>";
    echo "$q<br><br>  Make sure have defined the index terms correctly in
          the collection's Revision History field.";
    $sessionStats["errors"]++;
    generateStatusReport($sessionStats);
    exit(1);
  }

  if ($getRow) {
    $res = $result -> fetchRow();
  } else {
    $res = $result -> fetchAll();
  }

  $result -> free();

  return $res;
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
    return false;
  }

  return true;
}


// Finds whether an item already has an IndexField and either inserts or updates it
function updateIndexField($indexFieldValues, &$sessionStats) {
  $idList = implode(',', array_keys($indexFieldValues));
  $query = "SELECT ID, ContentID, Value FROM tblCollections_UserFields
            WHERE ContentID IN ($idList) AND Title LIKE 'IndexField'";
  $rows = runQuery($query, $sessionStats);

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

  if (!empty($update)) {
    // Break data into smaller chunks (512 index fields) before sending to db
    $updateChunks = array_chunk($update, 512, true);

    foreach ($updateChunks as $chunk) {
      $query = "UPDATE tblCollections_UserFields SET Value = CASE ContentID ";
      foreach ($chunk as $id => $val) {
        $query .= "WHEN " . $id . " THEN '$val' ";
      }
      $query .= "END WHERE (ContentID IN (" . implode(',', array_keys($chunk))
              . ") AND Title LIKE 'IndexField')";
      runQuery($query, $sessionStats);
    }
  }

  if (!empty($insert)) {
    $insertChunks = array_chunk($insert, 512, true);
    foreach ($insertChunks as $chunk) {
      $query = "INSERT INTO tblCollections_UserFields (ContentID, Title, Value, EADElementID) VALUES";
      foreach ($chunk as $id => $val) {
        $query .= " ($id, 'IndexField', '$val', 15),";
      }
      $query = rtrim($query, ","); // remove last comma
      runQuery($query, $sessionStats);
    }
  }
}


// Checks to see whether the parameters entered in RevisionHistory are valid
function verifyParameter($table, $field, &$sessionStats, $value = NULL) {
  $validTables = array("tblCollections_Content", "tblCollections_UserFields");
  $warning = "Warning: %s \"%s\" does not exist.  ";

  // Check if the table exists
  $t_exists = false;
  $tableList = runQuery("SHOW TABLES", $sessionStats);
  foreach ($tableList as $tbl) {
    if ($table == $tbl['Tables_in_archon']) $t_exists = true;
  }
  if (!$t_exists) {
    echo sprintf($warning, "Table", $table);
    return false;
  }

  // Check if the column exists
  $c_exists = false;
  $columnList = runQuery("SHOW COLUMNS FROM $table", $sessionStats);
  foreach ($columnList as $col) {
    if (ucfirst($field) == $col['Field']) $c_exists = true;
  }
  if (!$c_exists) {
    echo sprintf($warning, "Field", $field);
    return false;
  }

  // Check if the user field exists
  if ($value) {
    $row =  runQuery("SELECT ID FROM $table WHERE Title = '$value'", $sessionStats, true);
    if (count($row) == 0) {
      echo sprintf($warning, "User field", $value);
        return false;
    }
  }

  // Check if they are indexing from a valid tables
  if (!in_array($table, $validTables)) {
    echo "<br>Warning: You cannot select index terms from this table.<br>";
    return false;
  }

  // Don't let them put IndexField as a user field to indexing
  if (!strcasecmp($value, "IndexField")) {
    echo "<br>Warning: You cannot index the current index field, stop trying to break Archon.<br>";
    return false;
  }

  return true;
}

?>
