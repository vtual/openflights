<?php
require_once("../php/locale.php");
require_once("../php/db.php");

include 'helper.php';

$name = $_POST["name"];
$alias = $_POST["alias"];
$mode = $_POST["mode"];
if(! $mode || $mode == 'F') {
  $iata = $_POST["iata"];
  $icao = $_POST["icao"];
  $callsign = $_POST["callsign"];
  $mode = "F";
} else {
  $iata = "";
  $icao = "";
  $callsign = "";
}
$country = $_POST["country"];
$offset = $_POST["offset"];
$active = $_POST["active"];
$iatafilter = $_POST["iatafilter"];
$action = $_POST["action"];
$alid = $_POST["alid"];

$uid = $_SESSION["uid"];
if($action == "RECORD") {
  if(!$uid or empty($uid)) {
    printf("0;" . _("Your session has timed out, please log in again."));
    exit;
  }

  // Check for duplicates
  $sql = "SELECT * FROM airlines WHERE mode='" . mysqli_real_escape_string($db, $mode) . "' AND (name LIKE '" . mysqli_real_escape_string($db, $name) . "' OR alias LIKE '" . mysqli_real_escape_string($db, $name) . "')";
  // Editing an existing entry, so make sure we're not overwriting something else
  if($alid && $alid != "") {
    $sql .= " AND alid != " . mysqli_real_escape_string($db, $alid);
  }
  $result = $db->query($sql) or die('0;Duplicate check failed ' . $sql);
  if($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
    printf("0;" ."A " . $modeOperators[$mode] . " using the name or alias " . $name . " exists already.");
    exit;
  }

  if($alias != "") {
    $sql = "SELECT * FROM airlines WHERE mode='" . mysqli_real_escape_string($db, $mode) . "' AND (name LIKE '" . mysqli_real_escape_string($db, $alias) . "' OR alias LIKE '" . mysqli_real_escape_string($db, $alias) . "')";
    // Editing an existing entry, so make sure we're not overwriting something else
    if($alid && $alid != "") {
      $sql .= " AND alid != " . mysqli_real_escape_string($db, $alid);
    }
    $result = $db->query($sql) or die('0;Duplicate check failed ' . $sql);
    if($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
      printf("0;"."A " . $modeOperators[$mode] . " using the name or alias " . $alias . " exists already.");
      exit;
    }
  }

  // ICAO duplicates are not
  if($icao != "") {
    $sql = "SELECT * FROM airlines WHERE icao='" . mysqli_real_escape_string($db, $icao) . "'";
    // Editing an existing entry, so make sure we're not overwriting something else
    if($alid && $alid != "") {
      $sql .= " AND alid != " . mysqli_real_escape_string($db, $alid);
    }
    $result = $db->query($sql) or die('0;Duplicate check failed ' . $sql);
    if($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
      printf("0;An airline using the ICAO code " . $icao . " exists already.");
      exit;
    }
  }

  if(! $alid || $alid == "") {    
    // Adding new airline
    $sql = sprintf("INSERT INTO airlines(name,alias,country,iata,icao,callsign,mode,active,uid) VALUES('%s', '%s', '%s', '%s', %s, '%s', '%s', '%s', %s)",
		   mysqli_real_escape_string($db, $name), 
		   mysqli_real_escape_string($db, $alias),
		   mysqli_real_escape_string($db, $country),
		   mysqli_real_escape_string($db, $iata),
		   $icao == "" ? "NULL" : "'" . mysqli_real_escape_string($db, $icao) . "'",
		   mysqli_real_escape_string($db, $callsign),
		   mysqli_real_escape_string($db, $mode),
		   $active,
		   $uid);
  } else {
    // Editing an existing airline
    $sql = sprintf("UPDATE airlines SET name='%s', alias='%s', country='%s', iata='%s', icao=%s, callsign='%s', mode='%s', active='%s' WHERE alid=%s AND (uid=%s OR %s=%s)",
		   mysqli_real_escape_string($db, $name), 
		   mysqli_real_escape_string($db, $alias),
		   mysqli_real_escape_string($db, $country),
		   mysqli_real_escape_string($db, $iata),
		   $icao == "" ? "NULL" : "'" . mysqli_real_escape_string($db, $icao) . "'",
		   mysqli_real_escape_string($db, $callsign),
		   mysqli_real_escape_string($db, $mode),
		   mysqli_real_escape_string($db, $active),
		   $alid,
		   $uid,
		   $uid,
		   $OF_ADMIN_UID);
  }
  $db->query($sql) or die('0;Adding new ' . $modeOperators[$mode] . ' failed' . $sql);
  if(! $alid || $alid == "") {
    printf('1;' . mysqli_insert_id($db) . ';New ' . $modeOperators[$mode] . ' successfully added.');
  } else {
    if(mysqli_affected_rows($db) == 1) {
      printf('1;' . $apid . ';' . _("Airline successfully edited."));
    } else {
      printf('0;' . _("Editing airline failed:") . ' ' . $sql);
    }
  }
  exit;
}

$sql = "SELECT * FROM airlines WHERE ";

// Build filter
if($name) {
  $sql .= " (name LIKE '" . mysqli_real_escape_string($db, $name) . "%' OR alias LIKE '" . mysqli_real_escape_string($db, $name) . "%') AND";
}
if($alias) {
  $sql .= " (name LIKE '" . mysqli_real_escape_string($db, $alias) . "%' OR alias LIKE '" . mysqli_real_escape_string($db, $alias) . "%') AND";
}
if($callsign) {
  $sql .= " callsign LIKE '" . mysqli_real_escape_string($db, $callsign) . "%' AND";
}

if($iata) {
  $sql .= " iata='" . mysqli_real_escape_string($db, $iata) . "' AND";
}
if($icao) {
  $sql .= " icao='" . mysqli_real_escape_string($db, $icao) . "' AND";
}
if($country != "ALL") {
  if($country) {
    $sql .= " country='" . mysqli_real_escape_string($db, $country) . "' AND";
  }
}
if($mode) {
  $sql .= " mode='" . mysqli_real_escape_string($db, $mode) . "' AND";
}
if($active != "") {
  $sql .= " active='" . mysqli_real_escape_string($db, $active) . "' AND";
}

if($mode != "F" || $iatafilter == "false") {
  $sql .= " 1=1"; // dummy
 } else {
  $sql .= " iata != '' AND iata != 'N/A'";
}
if(! $offset) {
  $offset = 0;
}
$sql .= " ORDER BY name";

$result = $db->query($sql . " LIMIT 10 OFFSET " . $offset) or die ('0;Operation ' . $param . ' failed: ' . $sql);
$result2 = $db->query(str_replace("*", "COUNT(*)", $sql));
if($row = mysqli_fetch_array($result2, MYSQLI_NUM)) {
  $max = $row[0];
}
printf("%s;%s;%s", $offset, $max, $sql);

while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
  if($row["uid"] || $uid == $OF_ADMIN_UID ) {
    if($row["uid"] == $uid || $uid == $OF_ADMIN_UID) {
      $row["al_uid"] = "own"; // editable
    } else {
      $row["al_uid"] = "user"; // added by another user
    }
  } else {
    $row["al_uid"] = null; // in DB
  }
  unset($row["uid"]);
  $row["al_name"] = format_airline($row);
  print "\n" . json_encode($row);
}

?>
