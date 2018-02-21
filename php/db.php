<?php
include 'config.php';

// Make the PDO and the legacy database drivers mutually exclusive.
if (isset($dbh)) {
    die('Multiple DB handlers instantiated; aborting.');
}

$db = mysqli_connect($host,$user,$password,$dbname);
if (!$db) {
  die("Error;Unable to connect to MySQL at $host as $user: " . mysqli_error($db));
}
$db->query("SET NAMES 'utf8'");
?>
