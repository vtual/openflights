<?php
session_start();
include 'db.php';

// List of all countries
$sql = "SELECT code, name FROM countries ORDER BY name";
$result = $db->query($sql);
$first = true;
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
  if($first) {
    $first = false;
  } else {
    printf("\n");
  }
  printf ("%s;%s", $row["code"], $row["name"]);
}
?>
