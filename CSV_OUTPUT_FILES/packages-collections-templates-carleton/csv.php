<?php
/**
 * Output file for CSV finding aids
 *
 * @package Archon
 * @author Chris Rishel
 * 
 * modified for CSV use by Caitlin Donahue
 */

isset($_ARCHON) or die();

$filename = ($_REQUEST['output']) ? $_REQUEST['output'] : 'csv';

header('Content-type: text; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'.csv"');

$_REQUEST['templateset'] = "EAD";

$_ARCHON->PublicInterface->DisableTheme = true;

?>