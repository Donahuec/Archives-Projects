<?php
/**
* Utility for indexing search terms
*
* packages\core\admin\indexutil.php
*
* @package Archon
* @subpackage AdminUI
* @author Caleb Braun, 7/13/2016
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


/*  When this file is called from updating on save in
    packages/collecitons/admin/collectioncontent.php (line 1295), it is submitting
    the form "mainform" defined in packages/core/lib/administrativeinterface.inc.php.
    It expects a response in xml to be displayed upon completion of submitting the
    form (line 366). This check prevents the Admin Response (error) box from showing.
*/
if ($_REQUEST['p'] == 'admin/collections/collectioncontent') {
  header('Content-type: text/xml; charset=UTF-8');
  echo "<?xml version='1.0' encoding='UTF-8'?>\n";
  echo "<archonresponse error='false'><message>Collection Content Database Updated Successfully</message></archonresponse>";
}


ob_start();

/* ~~ Update an individual item ~~ */
if (array_key_exists('itemidnum', $_REQUEST)) {
  $itemID = $_REQUEST['itemidnum'];

  if (!ctype_digit($itemID)) {
    echo "<b>ERROR: </b>Please enter a number.";
    $sessionStats['errors']++;
  } else {
    $sessionStats["indexType"] = 'item';
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
        echo "<br>Successfully indexed item $itemID.</font>";
      } elseif (!$sessionStats['errors']) {
        echo "<br><font color = 'limegreen'>Item $itemID already up to date!</font>";
      }
    }
  }


/* ~~ Update a collection ~~ */
} elseif (array_key_exists('collidnum', $_REQUEST)) {
  $collID = $_REQUEST['collidnum'];

  if (!ctype_digit($collID)) {
    echo "<b>ERROR: </b>Please enter a number.";
    $sessionStats['errors']++;
  } else {
    $sessionStats["indexType"] = 'collection';
    echo "<br>Updating collection $collID...<br><br>";
    indexCollection($collID, $sessionStats);
  }


/* ~~ Update everything ~~ */
} else {
  $sessionStats["indexType"] = 'all';
  $query = "SELECT ID FROM tblCollections_Collections";
  $collectionIDs = runQuery($query, &$sessionStats);

  // Loop through every collection, indexing them one at a time
  foreach ($collectionIDs as $collID) {
    indexCollection($collID['ID'], &$sessionStats);
  }

  if (!$sessionStats['errors']) {
    echo "<br><br><b>Successfully indexed all collections!</font></b><br>";
  }
}

generateStatusReport($sessionStats);


// Expands out date ranges to full four digit years
function explodeDates($term) {
  // Finds dates like 'YYYY (+ or - 2 years)'
  $circaPattern = "/(\d{4})\/?(\d{0,2})\s?I{0,2}\s?\(\s?\+ or \- (\d) year[s]?\)/";
  // Finds first date range in the index field
  $rangePattern = "/(\d{4})\s?[-\/]\s?(\d{2,})\b/";

  // Changes something like 'YYY5 (+ or - 2 years)' to 'YYY3-YYY7'
  if (preg_match($circaPattern, $term, $plusOrMinus)) {
    $extraYear = $plusOrMinus[2] ? 1 : 0;
    $range = ($plusOrMinus[1] - intval($plusOrMinus[3])) . "-" . ($plusOrMinus[1] + intval($plusOrMinus[3]) + $extraYear);
    $term = preg_replace($circaPattern, $range, $term);
  }

  // Explode each date range
  while (preg_match($rangePattern, $term, $matches)) {
    $startYear = $matches[1];
    $endYear = $matches[2];
    $dateRange = '';

    // Check for YYYY-YY or YYYY-YYYY format
    if (strlen($endYear) != 2 && strlen($endYear) != 4) break;

    // Make each end of date range the full year YYYY-YYYY and in order
    if (strlen($endYear) == 2) {
      if (substr($startYear, 2, 2) < $endYear) {
        $endYear = substr($startYear, 0, 2) . $endYear;
      } else {
        $endYear = substr($startYear, 0, 2) + 1 . $endYear;
      }
    } elseif ($endYear < $startYear){
      $startYear = $endYear;
      $endYear = $matches[1];
    }

    // Replace with range of dates, comma separated
    for ($year=$startYear; $year < $endYear; $year++) {
      $dateRange .= "$year, ";
    }
    $dateRange .= "$endYear";
    $term = preg_replace($rangePattern, $dateRange, $term, 1);
  }

  return $term;
}


// Prints out report displaying the stats for this script
function generateStatusReport(&$sessionStats) {
  // Put status report at top
  $report = "<h3>Report: </h3><br><br>";
  $report .= plural($sessionStats["inserts"], "new item") . " indexed.<br>";
  $report .= plural($sessionStats["updates"], "item") . " updated.<br>";
  $report .= plural($sessionStats["duplicates"], "item") . " already up to date.<br>";
  if ($sessionStats["skipped"]) {
      $report .= plural($sessionStats["skipped"], "item");
      $report .= " skipped because of null IndexField value.<br>";
  }
  if ($sessionStats["warnings"]) {
    $report .= "<font color='DarkOrange'>";
    $report .= plural($sessionStats["warnings"], "warning") . ".</font><br>";
  }
  if ($sessionStats["errors"]) {
    $report .= "<font color='Red'><b>" . plural($sessionStats["errors"], "error");
    $report .= ".</b></font> Please correct the errors below and try again.<br>";
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
// keywords into one term. Returns an array of ids => indexfields
function getIndexFieldValues($id, $userFields, &$sessionStats) {
  $defaultValues = array('Title', 'Date', 'Description');
  $collectionTitle = $userFields['collectionTitle'];
  $creators = $userFields['hasCreators'];
  unset($userFields['collectionTitle']);
  unset($userFields['hasCreators']);
  $selectDVs = ""; // Select default values from tblCollections_Content
  $selectUFs = ""; // Select user fields from tblCollections_UserFields

  $idListAsString = implode(',', array_map('intval', $id));

  // ----- Start Query ----- //
  $selectDVs = " (SELECT ID AS ContentID, CONCAT_WS('; ', ";
  $selectDVs .= implode(", ", $defaultValues) . ") AS Value FROM ";
  $selectDVs .= "tblCollections_Content WHERE ID IN (" . $idListAsString . ")) ";
  $selectDVs .= ($userFields or $creators) ? "UNION" : "";

  if ($userFields) {
    $ufs = array();
    foreach ($userFields as $val) {
      $ufs[] = "Title ='" . $val . "'";
    }
    $selectUFs = " (SELECT ContentID, Value FROM tblCollections_UserFields WHERE (";
    $selectUFs .= implode("OR ", $ufs);
    $selectUFs .= ") AND ContentID IN (" . $idListAsString . "))";
    $selectUFs .= ($creators) ? "UNION" : "";
  }

  if ($creators) {
    $selectCRs = " (SELECT a.CollectionContentID ContentID, b.Name Value
    FROM tblCollections_CollectionContentCreatorIndex AS a
    JOIN tblCreators_Creators AS b ON a.CreatorID = b.ID
    AND a.CollectionContentID IN (" . $idListAsString . ")) ";
  }

  $query = "SELECT tmp.ContentID, GROUP_CONCAT(tmp.Value SEPARATOR '; ') AS IndexField FROM (";
  $query .= $selectDVs . $selectUFs . $selectCRs . ") AS tmp GROUP BY ContentID";
  // ----- End Query ----- //


  $rows = runQuery($query, $sessionStats);
  $indexFieldVals = array();

  // Put results into an array
  foreach ($rows as $item) {
    $sessionStats['numitems']++;
    $idxflds = $collectionTitle . "; " . $item['IndexField'];
    $idxflds = str_replace("'", "''", $idxflds); // Escape apostrophes
    if ($idxflds) {
      $idxflds = explodeDates($idxflds, $item['ContentID']);
      $indexFieldVals[$item['ContentID']] = $idxflds;
    } else {
      $sessionStats['skipped']++;
    }
  }

  return $indexFieldVals;
}


// Fetches the data in the RevisionHistory field of the collection and returns
// an array containing the collection title, user fields, and a bool $hasCreators
// representing whether there are creators associated with this record
function getIndexSources($idNum, &$sessionStats) {
  $verbose = ($sessionStats['indexType'] != 'all');
  $indexFieldSources = array();
  $hasCreators = false;

  // Query depends on if we are given collection id or item id
  if ($sessionStats["indexType"] == 'item') {
    $query = "SELECT tblCollections_Collections.Title, RevisionHistory, CollectionID
              FROM tblCollections_Collections, tblCollections_Content
              WHERE tblCollections_Content.CollectionID = tblCollections_Collections.ID
              AND tblCollections_Content.ID=$idNum";
  } else {
    $query = "SELECT Title, RevisionHistory
              FROM tblCollections_Collections
              WHERE ID=$idNum";
  }

  $row = runQuery($query, $sessionStats, true);

  $collectionID = $row['CollectionID'] ? $row['CollectionID'] : $idNum;
  $indexFieldSources['collectionTitle'] = $row['Title'];

  if (!$row) {
    echo "ERROR: " . ucfirst($sessionStats['indexType']) . " does not exist.";
    $sessionStats['errors']++;
    return false;

  } elseif ($row['RevisionHistory']) {
    $revisionList = explode(', ', $row['RevisionHistory']);
    if ($verbose) echo "Indexing user fields: <br>";

    foreach ($revisionList as $indexField) {
      // Make sure user field is valid
      $row =  runQuery("SELECT ID FROM tblCollections_UserFields WHERE Title = '$indexField'", $sessionStats, true);
      if (count($row) == 0) {
        echo "Warning: User field \"$indexField\" does not exist for any item in
              <a target=\"_blank\" href= ?p=admin/collections/collections&id=$collectionID
              style=\"color:#B5EFFF\">collection $collectionID</a><br>";
        $sessionStats['warnings']++;
      } elseif (!strcasecmp($indexField, "IndexField")) {
        echo "Warning: You cannot index the current index field, stop trying to break Archon.<br>";
      } else {
        $indexFieldSources[] = $indexField;
        if ($verbose) echo "<li>$indexField</li>";
      }
    }

  } else {
    if ($verbose) {
      echo "Warning: No index terms specified for " . $sessionStats['indexType'];
      echo " $idNum. Indexing will continue with default values.<br>";
      $sessionStats["warnings"]++;
    }
  }

  // Determine if any items in this collection have creators
  $query = "SELECT DISTINCT b.CollectionID creators
            FROM tblCollections_Content b, tblCollections_CollectionContentCreatorIndex c
            WHERE b.CollectionID = $collectionID
            AND c.CollectionContentID = b.ID";

  $row = runQuery($query, $sessionStats, true);
  $indexFieldSources['hasCreators'] = $row['creators'];
  return $indexFieldSources;
  if ($row) {
    $hasCreators = ($row['creators'] != NULL);
  }

}


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


// Adds an s if plural.  Ex. plural(3, "hat") returns "3 hats".
function plural($n, $s) {
  return (($n != 1) ? "$n ".$s."s" :  "1 $s");
}


// Runs a query to the database, returns result of query
function runQuery($q, &$sessionStats, $getOnlyFirstRow = false) {
  global $_ARCHON;

  // Security Note:
  // It would be more secure to use the mdb2->prepare() function followed by
  // exec(), however with the checks already in place, it did not seem
  // completely necessary to be extra security conscience about this utility.
  $result = $_ARCHON -> mdb2 -> query($q);
  $sessionStats["queries"]++;
  if(PEAR::isError($result)) {
    echo "ERROR: Unable to index ".$sessionStats["indexType"];
    echo ". There is a problem with the following query:<br><br>";
    echo "$q<br><br>  Make sure you have defined the index terms correctly in
          the collection's Revision History field.";
    $sessionStats["errors"]++;
    generateStatusReport($sessionStats);
    exit(1);
  }

  if ($getOnlyFirstRow) {
    $res = $result -> fetchRow();
  } else {
    $res = $result -> fetchAll();
  }

  $result -> free();

  return $res;
}


// Finds whether an item already has an IndexField and either inserts or updates it
function updateIndexField($indexFieldValues, &$sessionStats) {
  $idList = implode(',', array_keys($indexFieldValues));
  $query = "SELECT ID, ContentID, Value FROM tblCollections_UserFields
            WHERE ContentID IN ($idList) AND Title LIKE 'IndexField'";
  $rows = runQuery($query, $sessionStats);

  // Make an array of the current IndexField values
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



?>
