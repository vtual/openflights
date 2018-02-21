<?php
include 'locale.php';
include 'db.php';

$type = $_POST["type"];
$name = $_POST["name"];
$pw = $_POST["pw"];
$oldpw = $_POST["oldpw"];
$oldlpw = $_POST["oldlpw"];
$email = $_POST["email"];
$privacy = $_POST["privacy"];
$editor = $_POST["editor"];
$units = $_POST["units"];
$guestpw = $_POST["guestpw"];
$startpane = $_POST["startpane"];
$locale = $_POST["locale"]; // override any value in URL/session

// 0 error
// 1 new
// 2 edited
// 10 reset

// Create new user
switch($type) {
 case "NEW":
   $sql = "SELECT * FROM users WHERE name='" . mysqli_real_escape_string($db, $name) . "'";
   $result = $db->query($sql);
   if (mysqli_fetch_array($result)) {
     die("0;" . _("Sorry, that name is already taken, please try another."));
   }
   break;
   
 case "EDIT":
 case "RESET":
  $uid = $_SESSION["uid"];
  $name = $_SESSION["name"];
  if(!$uid or empty($uid)) {
    die("0;" . _("Your session has timed out, please log in again."));
  }

  if($type == "RESET") {
    $sql = "DELETE FROM flights WHERE uid=" . $uid;
    $result = $db->query($sql);
    printf("10;" . _("Account reset, %s flights deleted."), mysqli_affected_rows($db));
    exit;
  }

  // EDIT
  if($oldpw && $oldpw != "") {
    $sql = "SELECT * FROM users WHERE name='" . mysqli_real_escape_string($db, $name) .
      "' AND (password='" . mysqli_real_escape_string($db, $oldpw) . "' OR " .
      "password='" . mysqli_real_escape_string($db, $oldlpw) . "')";
    $result = $db->query($sql);
    if(! mysqli_fetch_array($result)) {
      die("0;" . _("Sorry, current password is not correct."));
    }
  }
  break;

 default:
   die("0;Unknown action $type");
}

// Note: Password is actually an MD5 hash of pw and username
if($type == "NEW") {
  $sql = sprintf("INSERT INTO users(name,password,email,public,editor,locale,units) VALUES('%s','%s','%s','%s','%s','%s','%s')",
		 mysqli_real_escape_string($db, $name),
		 mysqli_real_escape_string($db, $pw),
		 mysqli_real_escape_string($db, $email),
		 mysqli_real_escape_string($db, $privacy),
		 mysqli_real_escape_string($db, $editor),
		 mysqli_real_escape_string($db, $locale),
		 mysqli_real_escape_string($db, $units));
} else {
  // Only change password if old password matched and a new one was given
  if($oldpw && $oldpw != "" && $pw && $pw != "") {
    $pwsql = sprintf("password='%s', ", mysqli_real_escape_string($db, $pw));
  } else {
    $pwsql = "";
  }
  if(! $guestpw) $guestpw = "";
  $sql = sprintf("UPDATE users SET %s email='%s', public='%s', editor='%s', guestpw=%s, startpane='%s', locale='%s', units='%s' WHERE uid=%s",
		 $pwsql,
		 mysqli_real_escape_string($db, $email),
		 mysqli_real_escape_string($db, $privacy),
		 mysqli_real_escape_string($db, $editor),
		 $guestpw == "" ? "NULL" : "'" . mysqli_real_escape_string($db, $guestpw) . "'",
		 mysqli_real_escape_string($startpane),
		 mysqli_real_escape_string($db, $locale),
		 mysqli_real_escape_string($db, $units),
		 $uid);
}
$db->query($sql) or die ('0;Operation on user ' . $name . ' failed: ' . $sql . ', error ' . mysqli_error($db));

// In all cases change locale and units to user selection
$_SESSION['locale'] = $locale;
$_SESSION['units'] = $units;

if($type == "NEW") {
  printf("1;" . _("Successfully signed up, now logging in..."));

  // Log in the user
  $uid = mysqli_insert_id($db);
  $_SESSION['uid'] = $uid;
  $_SESSION['name'] = $name;
  $_SESSION['editor'] = $editor;
  $_SESSION['elite'] = $elite;
  $_SESSION['units'] = $units;
} else {
  printf("2;" . _("Settings changed successfully, returning..."));
}
?>
