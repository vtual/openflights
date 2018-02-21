<?php
session_start();
$uid = $_SESSION["uid"];

include 'helper.php';
include 'filter.php';
include 'db.php';
include 'greatcircle.php';

// Logged in?
if(!$uid or empty($uid)) {

  // Viewing an "open" user's flights, or an "open" flight?
  // (will be previously set in map.php)
  $uid = $_SESSION["openuid"]; 
  if($uid && !empty($uid)) {
    // Yes we are, so check if we're limited to a single trip
    $openTrid = $_SESSION["opentrid"];
    if($openTrid) {
      if($openTrid == $trid) {
	// This trip's OK
      } else {
	// Naughty naughty, back to demo mode
	$uid = 1;
      }
    } else {
      // No limit, do nothing
    }
  } else {
    // Nope, default to demo mode
    $uid = 1;
  }
}

//get order by and get table to use

$sql = "SELECT a.name, a.iata, COALESCE(t.flights_count, 0) AS flights_count
FROM airports_uk u
LEFT JOIN airports AS a ON u.iata=a.iata
LEFT JOIN
(
   SELECT apid, COUNT(fid) AS flights_count FROM
  (
    SELECT src_apid AS apid, fid FROM flights AS f WHERE uid = $uid
    UNION ALL
    SELECT dst_apid AS apid, fid FROM flights AS f WHERE uid = $uid
  ) f
  GROUP BY apid
) t
ON t.apid = a.apid
ORDER BY u.yearly_pax DESC";

// Execute!
$result = $db->query($sql) or die ('Error;Query ' . print_r($_GET, true) . ' caused database error ' . $sql . ', ' . mysqli_error($db));
$first = true;

while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
  if($first) {
    $first = false;
  } else {
    printf("\n");
  }

  printf ("%s\t%s\t%s", $row["name"], $row['iata'], $row["flights_count"]);
}

?>