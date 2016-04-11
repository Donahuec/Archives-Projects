<?php
/**
 * Short file to send page to csv.php file in template. Here you will need to change the path to the csv.php file
 * to whatever template you are using.
 *
 * @package Archon
 * @author Caitlin Donahue
 * 

 */
isset($_ARCHON) or die();
include('packages/collections/templates/carleton/csv.php');
include('packages/collections/pub/findingaid.php');
?>