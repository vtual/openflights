<?php
include_once('../php/helper.php');
include_once('../php/simple_html_dom.php');

// Request timezone for airports and add it to DB
// <timezone xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.earthtools.org/timezone.xsd">
//     <version>1.1</version>
//     <location>
//         <latitude>40.71417</latitude>
//         <longitude>-74.00639</longitude>
//     </location>
//     <offset>-5</offset>
//     <suffix>R</suffix>
//     <localtime>4 Dec 2005 12:06:56</localtime>
//     <isotime>2005-12-04 12:06:56 -0500</isotime>
//     <utctime>2005-12-04 17:06:56</utctime>
//     <dst>False</dst>
// </timezone>

$db = mysqli_connect("localhost", "openflights", "flightdb2");
$sql = "select * from airports where timezone is null";
$result = $db->query($sql) or die ('Database error: ' . $sql . ', error ' . mysqli_error($db));
while($row = mysqli_fetch_assoc($result)) {
  $name = format_airport($row);
  $lon = $row["x"];
  $lat = $row["y"];
  $apid = $row["apid"];

  $html = file_get_html("http://www.earthtools.org/timezone/$lat/$lon");
  $tz = $html->find('offset', 0)->plaintext;
  $dst = $html->find('dst', 0)->plaintext;
  $count++;
  print "$count: $name ($lon, $lat): Timezone $tz, DST $dst";
  switch($dst) {
  case "True":
    $dstcode = "E"; // European
    break;
  case "False":
    $dstcode = "N"; // None
    break;
  case "Unknown":
    $dstcode = "U";
    break;
  }
  $sql = "UPDATE airports SET timezone=$tz, dst='$dstcode' WHERE apid=$apid";
  $update = $db->query($sql) or die ('Database error: ' . $sql . ', error ' . mysqli_error($db));
  if(mysqli_affected_rows($db) != 1) {
    die ('Database error: ' . $sql . ', error ' . mysqli_error($db));
  }
  print " [OK]\n";

  // Don't spam service (max 1 request/sec allowed)
  sleep(5);
}

?>
