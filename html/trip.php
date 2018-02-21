<?php
require_once("../php/locale.php");
require_once("../php/db.php");

if(isSet($_GET["trid"])) {
  $trid = $_GET["trid"];
} else {
  $trid = null;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>OpenFlights: <?php if($trid) {
  echo _("Edit trip");
} else {
  echo _("Add trip");
} ?></title>
    <link rel="stylesheet" href="/css/style_reset.css" type="text/css">
    <link rel="stylesheet" href="/openflights.css" type="text/css">
    <link rel="gettext" type="application/x-po" href="/locale/<?php echo $locale?>/LC_MESSAGES/messages.po" />
    <script type="text/javascript" src="/js/Gettext.js"></script>
    <script type="text/javascript" src="/js/trip.js"></script>
  </head>

  <body>
    <div id="contexthelp">
      <FORM name="tripform">
	<div id="title"><h1>OpenFlights: <?php if($trid) {
  echo _("Edit trip");
} else {
  echo _("Add trip");
} ?></h1></div>

<?php
$uid = $_SESSION["uid"];
if(!$uid or empty($uid)) {
  die(_("Your session has timed out, please log in again."));
}

if($trid) {
  $sql = "SELECT * FROM trips WHERE trid=" . mysqli_real_escape_string($db, $trid) . " AND uid=" . mysqli_real_escape_string($db, $uid);
  $result = $db->query($sql);
  if(! $trip = mysqli_fetch_array($result)) {
    die(_("Could not load trip data.") . $sql);
  }
} else {
  $trip = array("name" => "",
		"url" => "",
		"public" => "Y");
}
?>
	<div id="miniresultbox"></div>
	<table>
	    <tr>
              <td><?php echo _("Name") ?></td>
	      <td><INPUT type="text" name="name" size="40" value="<?php echo $trip["name"] ?>"></td>
	    </tr><tr>
              <td><?php echo _("Web address <i>(optional)</i>") ?>&nbsp;</td>
	      <td><INPUT type="text" name="url" size="40" value="<?php echo $trip["url"] ?>"></td>
	    </tr><tr>
	      <td style="vertical-align: top"><?php echo _("Trip privacy") ?></td>
	      <td><input type="radio" name="privacy" value="N" <?php if($trip["public"] == "N") { echo "CHECKED"; } echo ">" . _("Private (visible only to you)") ?><br>
	      <input type="radio" name="privacy" value="Y" <?php if($trip["public"] == "Y") { echo "CHECKED"; } echo ">" . _("Public (map and stats shared)") ?><br>
	      <input type="radio" name="privacy" value="O" <?php if($trip["public"] == "O") { echo "CHECKED"; } echo ">" . _("Open (all flight data shared)") ?></td>
	    </tr><tr>
	      <td><?php echo _("OpenFlights URL") ?></td>
	      <td><input type="text" value="<?php
if($trid) {
  echo "http://openflights.org/trip/" . $trid;
} else {
  echo _("Not assigned yet");
}?>" name="puburl" style="border:none" size="40" readonly></td>
	    </tr>
	</table><br>

<?php if($trid) {
  echo "<INPUT type='button' value='" . _("Save") . "' onClick='validate(\"EDIT\")'>\n";
  echo "<INPUT type='hidden' name='trid' value='" . $trid . "'>\n";
  echo "<INPUT type='button' value='" . _("Delete") . "' onClick='deleteTrip()'>\n";
} else {
  echo "<INPUT type='button' value='" . _("Add") . "' onClick='validate(\"NEW\")'";
}
?>
	<INPUT type="button" value="<?php echo _("Cancel") ?>" onClick="window.close()">
      </FORM>

    </div>

  </body>
</html>
