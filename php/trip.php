<?php
include 'locale.php';
include 'db.php';

$type = $_POST["type"];
$name = $_POST["name"];
$url = $_POST["url"];
$trid = $_POST["trid"];
$privacy = $_POST["privacy"];

if($type != "NEW" and (!$trid or $trid == 0)) {
  die ('0;Trip ID '. $trid . ' invalid');
}

$uid = $_SESSION["uid"];
if(!$uid or empty($uid)) {
  die ('0;' . _("Your session has timed out, please log in again."));
  exit;
}

switch($type) {
 case "NEW":
   // Create new trip
   $sql = sprintf("INSERT INTO trips(name,url,public,uid) VALUES('%s','%s','%s', %s)",
		  mysqli_real_escape_string($db, $name),
		  mysqli_real_escape_string($db, $url),
		  mysqli_real_escape_string($db, $privacy),
		  $uid);
   break;

 case "EDIT":
   // Edit existing trip
   $sql = sprintf("UPDATE trips SET name='%s', url='%s', public='%s' WHERE uid=%s AND trid=%s",
		  mysqli_real_escape_string($db, $name),
		  mysqli_real_escape_string($db, $url),
		  mysqli_real_escape_string($db, $privacy),
		  $uid,
		  mysqli_real_escape_string($db, $trid));
   break;

   // Assign its flights to null and delete trip
 case "DELETE":
   $sql = sprintf("UPDATE flights SET trid=NULL WHERE trid=%s AND uid=%s",
		  mysqli_real_escape_string($db, $trid),
		  mysqli_real_escape_string($db, $uid));
   $db->query($sql) or die ('0;Operation on trip ' . $name . ' failed: ' . $sql . ', error ' . mysqli_error($db));

   $sql = sprintf("DELETE FROM trips WHERE trid=%s AND uid=%s",
		  mysqli_real_escape_string($db, $trid),
		  mysqli_real_escape_string($db, $uid));
   break;

 default:
   die ('0;Unknown operation ' . $type);
}

$db->query($sql) or die ('0;Operation on trip ' . $name . ' failed: ' . $sql . ', error ' . mysqli_error($db));
if(mysqli_affected_rows($db) != 1) {
  die("0;No matching trip found");
}
  
switch($type) {
 case "NEW":
   $trid = mysqli_insert_id($db);
   printf("1;%s;" . _("Trip successfully created"), $trid);
   break;

 case "DELETE":
   printf("100;%s;" . _("Trip successfully deleted"), $trid);
   break;

 default:
   printf("2;%s;" . _("Trip successfully edited."), $trid);
   break;
}
?>
