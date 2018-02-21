<?php
include 'locale.php';
include 'db.php';

$name = $_POST["name"];
// pw is hashed from lowercased username, legacypw is not
$pw = $_POST["pw"];
$legacypw = $_POST["lpw"];
$challenge = $_POST["challenge"];

if($challenge && $challenge != $_SESSION["challenge"]) {
  $myrow = array("status" => 0, "message" => "Session expired. Please <a href='/'>refresh</a> and try again.");
  die(json_encode($myrow));
} else {
  $challenge = $_SESSION["challenge"];
}

// Log in user
if($name) {
  // CHAP: Use random challenge key in addition to password
  // user_pw == MD5(challenge, db_pw)
  $sql = "SELECT uid,name,email,editor,elite,units,locale FROM users WHERE name='" . $db->real_escape_string($name) .
    "' AND ('" . $db->real_escape_string($pw) . "' = MD5(CONCAT('" .
    $db->real_escape_string($challenge) . "',password)) OR " .
    " '" . $db->real_escape_string($legacypw) . "' = MD5(CONCAT('" .
    $db->real_escape_string($challenge) . "',password)))";
  $result = $db->query($sql);
  if ($myrow = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $uid = $myrow["uid"];
    $_SESSION['uid'] = $uid;
    $_SESSION['name'] = $myrow["name"];
    $_SESSION['email'] = $myrow["email"];
    $_SESSION['editor'] = $myrow["editor"];
    $_SESSION['elite'] = $myrow["elite"];
    $_SESSION['units'] = $myrow["units"];
    if($myrow["locale"] != "en_GB" && $_SESSION['locale'] != $myrow["locale"]) {
      $myrow['status'] = 2; // force reload, so UI is changed into user's language
    } else {
      $myrow['status'] = 1;
    }
    $_SESSION['locale'] = $myrow["locale"];
  } else {
    $message = sprintf(_("Login failed. <%s>Create account</a> or <%s>reset password</a>?"), "a href='/html/settings.html?new=yes'", "a href='#' onclick='JavaScript:help(\"resetpw\")'");
    $myrow = array("status" => 0, "message" => $message);
  }
  print json_encode($myrow);
}
?>


