<?php
//require_once '../vendor/autoload.php';
require_once '../php/locale.php';
require_once '../php/db.php';
require_once '../php/helper.php';

header("Content-type: text/html");

$airport = $_POST["name"];
$iata = $_POST["iata"];
$icao = $_POST["icao"];
$city = $_POST["city"];
$country = $_POST["country"];
$code = $_POST["code"];
$myX = $_POST["x"];
$myY = $_POST["y"];
$elevation = $_POST["elevation"];
$tz = $_POST["timezone"];
$dst = $_POST["dst"];
$dbname = $_POST["db"];
$iatafilter = $_POST["iatafilter"];
$offset = $_POST["offset"];
$action = $_POST["action"];
$apid = $_POST["apid"];

$uid = $_SESSION["uid"];

if($action == "RECORD") {
  if(!$uid or empty($uid)) {
    json_error("Your session has timed out, please log in again.");
  }

  // Check for potential duplicates (unless admin)
  $duplicates = array();
  if($uid != $OF_ADMIN_UID) {
    $filters = array();
    if($apid && $apid != "") {
      $filters[] = "apid=$apid";
    } 
    if($iata != "") {
      $filters[] = " iata='$iata'";
    }
    if($icao != "") {
      $filters[] = " icao='$icao'";
    }

    $sql = "SELECT * FROM airports WHERE " . implode(" OR ", $filters);
    $result = $db->query($sql);
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
      if($row['uid'] != $uid || $row['apid'] != $apid) {
        $duplicates[] = print_r($row, true);
      }
    }
  }

  if(! $apid || $apid == "") {    
    $sql = sprintf("INSERT INTO airports(name,city,country,iata,icao,x,y,elevation,timezone,dst,uid) VALUES('%s', '%s', '%s', %s, %s, %s, %s, %s, %s, '%s', %s)",
		   mysqli_real_escape_string($db, $airport), 
		   mysqli_real_escape_string($db, $city),
		   mysqli_real_escape_string($db, $country),
		   null_if_empty($iata),
       null_if_empty($icao),
		   mysqli_real_escape_string($db, $myX),
		   mysqli_real_escape_string($db, $myY),
		   mysqli_real_escape_string($db, $elevation),
		   mysqli_real_escape_string($db, $tz),
		   mysqli_real_escape_string($db, $dst),
		   $uid);
  } else {
    // Editing an existing airport
    $sql = sprintf("UPDATE airports SET name='%s', city='%s', country='%s', iata=%s, icao=%s, x=%s, y=%s, elevation=%s, timezone=%s, dst='%s' WHERE apid=%s",
		   mysqli_real_escape_string($db, $airport), 
		   mysqli_real_escape_string($db, $city),
		   mysqli_real_escape_string($db, $country),
       null_if_empty($iata),
       null_if_empty($icao),
		   mysqli_real_escape_string($db, $myX),
		   mysqli_real_escape_string($db, $myY),
		   mysqli_real_escape_string($db, $elevation),
		   mysqli_real_escape_string($db, $tz),
		   mysqli_real_escape_string($db, $dst),
		   mysqli_real_escape_string($db, $apid));
  }
  if(empty($duplicates)) {
    $db->query($sql) or json_error("Adding new airport failed:", $sql);
    if(! $apid || $apid == "") {
      json_success(array("apid" => mysqli_insert_id($db), "message" => "New airport successfully added."));
    } else {
      if(mysqli_affected_rows($db) == 1) {
        json_success(array("apid" => $apid, "message" => "Airport successfully edited."));
      } else {
        json_error("Editing airport failed:", $sql);
      }
    }
  } else {
    $iata = mysqli_real_escape_string($db, $iata);
    $icao = mysqli_real_escape_string($db, $icao);
    $name = $_SESSION['name'];
    $data = print_r(implode("\n", $duplicates), TRUE);
    $subject = sprintf("Update airport %s (%s/%s)",
      mysqli_real_escape_string($db, $airport),
      $iata,
      $icao);
    $body = <<<TXT
New airport edit suggestion submitted by $name:

$sql;

Existing, potentially conflicting airport information:

```
$data
```

Cross-check this edit on other sites with compatible licensing:
- OurAirports: http://ourairports.com/airports/$icao/pilot-info.html
- Wikipedia: http://www.google.com/search?q=wikipedia%20$icao%20airport&btnI
TXT;
    if(isSet($_POST["unittest"])) {
      echo $subject . "\n\n" . $body;
      exit;
    }
    $identifier = ($icao == "") ? $iata : $icao;
    $github = new \Github\Client();
    $github->authenticate($GITHUB_ACCESS_TOKEN, NULL, Github\Client::AUTH_HTTP_TOKEN);

    $issues = $github->api('search')->issues("repo:$GITHUB_USER/$GITHUB_REPO in:title $identifier");
    if(count($issues['items']) > 0) {
      // Existing issue, add comment
      $issue_number = $issues['items'][0]['number'];
      $result = $github->api('issue')->comments()->create($GITHUB_USER, $GITHUB_REPO,
        $issue_number, array('body' => $body));
    } else {
      // New issue
      $result = $github->api('issue')->create($GITHUB_USER, $GITHUB_REPO,
        array('title' => $subject, 'body' => $body, 'labels' => array('airport')));
      $issue_number = $result['number'];
    }
    if (TRUE) {
      $message = "Edit submitted for review on Github: Issue {$issue_number}, {$result['html_url']}";
      json_success(array("apid" => $apid, "message" => $message));
    } else {
      json_error("Could not submit edit for review, please contact <a href='/about'>support</a>.");
    }
  }
  exit;
}

if(! $dbname) {
  $dbname = "airports";
}
$sql = "SELECT * FROM " . mysqli_real_escape_string($db, $dbname) . " WHERE ";

if($action == "LOAD") {
  // Single-airport fetch
  $sql .= " apid=" . mysqli_real_escape_string($db, $apid);
  $offset = 0;

 } else {
  // Real search, build filter
  if($airport) {
    $sql .= " name LIKE '%" . mysqli_real_escape_string($db, $airport) . "%' AND";
  }
  if($iata) {
    $sql .= " iata='" . mysqli_real_escape_string($db, $iata) . "' AND";
  }
  if($icao) {
    $sql .= " icao='" . mysqli_real_escape_string($db, $icao) . "' AND";
  }
  if($city) {
    $sql .= " city LIKE '" . mysqli_real_escape_string($db, $city) . "%' AND";
  }
  if($country != "ALL") {
    if($dbname == "airports_dafif" || $dbname == "airports_oa") {
      if($code) {
	$sql .= " code='" . mysqli_real_escape_string($db, $code) . "' AND";
      }
    } else {
      if($country) {
	$sql .= " country='" . mysqli_real_escape_string($db, $country) . "' AND";
      }
    }
  }
  
  // Disable this filter for DAFIF (no IATA data)
  if($iatafilter == "false" || $dbname == "airports_dafif") {
    $sql .= " 1=1"; // dummy
  } else {
    $sql .= " iata != '' AND iata != 'N/A'";
  }
}
if(! $offset) {
  $offset = 0;
}

// Check result count
$sql2 = str_replace("*", "COUNT(*)", $sql);
$result2 = $db->query($sql2) or json_error('Operation ' . $param . ' failed: ' . $sql2);
if($row = mysqli_fetch_array($result2, MYSQLI_NUM)) {
  $max = $row[0];
}
$response = array("status" => 1, "offset" => $offset, "max" => $max);

// Fetch airport data
$sql .= " ORDER BY name LIMIT 10 OFFSET " . $offset;
$result = $db->query($sql) or die (json_encode(array("status" => 0, "message" => 'Operation ' . $param . ' failed: ' . $sql)));
while ($rows[] = mysqli_fetch_assoc($result));
array_pop($rows);
foreach($rows as &$row) {
  if($dbname == "airports_dafif" || $dbname == "airports_oa") {
    $row["country"] = $row["code"];
  } 
  if($row["uid"] || $uid == $OF_ADMIN_UID ) {
    if($row["uid"] == $uid || $uid == $OF_ADMIN_UID) {
      $row["ap_uid"] = "own"; // editable
    } else {
      $row["ap_uid"] = "user"; // added by another user
    }
  } else {
    $row["ap_uid"] = null; // in DB
  }
  $row["ap_name"] = format_airport($row);
  unset($row["uid"]);
}
$response['airports'] = $rows;
print json_encode($response);
?>
